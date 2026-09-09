<?php

namespace Odb\SmartExportBundle\Services;

use DateTimeImmutable;
use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Odb\SmartExportBundle\Enum\FilterWidget;
use Odb\SmartExportBundle\Model\SmartExportEngineImportPreview;
use Odb\SmartExportBundle\Repository\SmartExportColumnRepository;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;
use Symfony\Component\Uid\UuidV7;

/**
 * Backs the edit page's "Exporter" button and the admin index's "Importer"
 * flow: a minimal JSON snapshot of one engine (header + columns) meant to be
 * saved, versioned, and re-applied on another instance (e.g. dev -> recette).
 *
 * Import is a strict three-step flow, never a one-shot upload-and-commit:
 * parseImport() only parses/validates (plus a read-only lookup for an engine
 * to update), the controller renders that as a confirmation screen, and only
 * commitImport() — called from a second, explicit POST — actually writes to
 * the database. See AdminController::importForm()/importCommit().
 */
class SmartExportEngineTransfer implements SmartExportEngineTransferInterface
{
    private const FORMAT_VERSION = 1;

    public function __construct(
        private readonly SmartExportEngineRepository $engineRepository,
        private readonly SmartExportColumnRepository $columnRepository,
        private readonly SmartExportQueryInterface $smartExportQuery,
    ) {
    }

    public function export(SmartExportEngine $engine): array
    {
        $columns = [];
        foreach ($engine->getColumns() as $column) {
            $columns[] = [
                'choicePosition' => $column->getChoicePosition(),
                'classProperty' => $column->getClassProperty(),
                'label' => $column->getLabel(),
                'cellGroupIndex' => $column->getCellGroupIndex(),
                'interpreter' => $column->getInterpreter(),
                'enabled' => $column->isEnabled(),
                'columnDisplay' => $column->isColumnDisplay(),
                'selectedByDefault' => $column->isSelectedByDefault(),
                'filterable' => $column->isFilterable(),
                'filterDisplay' => $column->isFilterDisplay(),
                'filterDefaultValue' => $column->getFilterDefaultValue(),
                'filterWidget' => $column->getFilterWidget()->value,
            ];
        }

        return [
            'formatVersion' => self::FORMAT_VERSION,
            'exportedAt' => (new DateTimeImmutable())->format(DATE_ATOM),
            'header' => [
                'uuid' => $engine->getUuid()->toRfc4122(),
                'code' => $engine->getCode(),
                'name' => $engine->getName(),
                'description' => $engine->getDescription(),
                'className' => $engine->getClassName(),
                'enabled' => $engine->isEnabled(),
            ],
            'columns' => $columns,
        ];
    }

    public function parseImport(string $json): SmartExportEngineImportPreview
    {
        $preview = new SmartExportEngineImportPreview($json);

        $data = json_decode($json, true);
        if (!is_array($data) || JSON_ERROR_NONE !== json_last_error()) {
            return $preview->addError('seb.import.error.invalid_json');
        }

        if (!isset($data['header']) || !is_array($data['header'])) {
            $preview->addError('seb.import.error.missing_header');
        }
        if (!isset($data['columns']) || !is_array($data['columns'])) {
            $preview->addError('seb.import.error.missing_columns');
        }
        if (!$preview->isValid()) {
            return $preview;
        }

        $this->parseHeader($data['header'], $preview);
        $this->parseColumns($data['columns'], $preview);
        if (!$preview->isValid()) {
            return $preview;
        }

        $uuid = $preview->getHeader()['uuid'];
        if (null !== $uuid) {
            $preview->setExistingEngine($this->engineRepository->findOneBy(['uuid' => $uuid]));
        }

        return $preview;
    }

    public function commitImport(SmartExportEngineImportPreview $preview): SmartExportEngine
    {
        $header = $preview->getHeader();
        $engine = $preview->getExistingEngine() ?? new SmartExportEngine();

        // Only set on CREATE: an update keeps the target's own existing uuid
        // untouched (it's how it was matched in the first place).
        if (!$preview->isUpdate() && null !== $header['uuid']) {
            $engine->setUuid(new UuidV7($header['uuid']));
        }

        $engine->setCode($header['code']);
        $engine->setName($header['name']);
        $engine->setDescription($header['description']);
        $engine->setClassName($header['className']);
        $engine->setEnabled($header['enabled']);

        // Import is a full snapshot, not a merge: every existing column is
        // replaced, never partially updated/diffed — same "remove what's no
        // longer there" mechanics as the normal edit form
        // (SmartExportAdmin::handleFormEditEngine()), just unconditional here
        // since there is no "posted" side to diff against.
        foreach ($engine->getColumns()->toArray() as $existingColumn) {
            $engine->removeColumn($existingColumn);
            $this->columnRepository->remove($existingColumn, false);
        }

        foreach ($preview->getColumns() as $columnData) {
            $column = new SmartExportColumn();
            $column->setChoicePosition($columnData['choicePosition']);
            $column->setClassProperty($columnData['classProperty']);
            $column->setLabel($columnData['label']);
            $column->setCellGroupIndex($columnData['cellGroupIndex']);
            $column->setInterpreter($columnData['interpreter']);
            $column->setEnabled($columnData['enabled']);
            $column->setColumnDisplay($columnData['columnDisplay']);
            $column->setSelectedByDefault($columnData['selectedByDefault']);
            $column->setFilterable($columnData['filterable']);
            $column->setFilterDisplay($columnData['filterDisplay']);
            $column->setFilterDefaultValue($columnData['filterDefaultValue']);
            $column->setFilterWidget($columnData['filterWidget']);
            $engine->addColumn($column);
        }

        $this->engineRepository->save($engine);

        return $engine;
    }

    private function parseHeader(array $header, SmartExportEngineImportPreview $preview): void
    {
        $className = trim((string) ($header['className'] ?? ''));
        if ('' === $className) {
            $preview->addError('seb.import.error.missing_class_name');
        } elseif (!in_array($className, $this->smartExportQuery->getAdminSelectClasses(), true)) {
            // NOT class_exists(): SmartExportEngine::className is, by this bundle's
            // own convention (see SmartExportQuery::getMetaFromEntityClass()), the
            // entity's SHORT class name (e.g. "Customer"), never a FQCN — the exact
            // same short-name list the admin's own "Relatif à" dropdown is built from.
            $preview->addWarning(sprintf('seb.import.warning.class_not_found|%s', $className));
        }

        $uuid = isset($header['uuid']) ? (string) $header['uuid'] : '';
        if ('' !== $uuid && !UuidV7::isValid($uuid)) {
            $preview->addWarning('seb.import.warning.invalid_uuid');
            $uuid = '';
        }

        $code = isset($header['code']) ? trim((string) $header['code']) : '';

        $preview->setHeader([
            'uuid' => '' !== $uuid ? $uuid : null,
            'code' => '' !== $code ? $code : null,
            'name' => isset($header['name']) ? (string) $header['name'] : null,
            'description' => isset($header['description']) ? (string) $header['description'] : null,
            'className' => $className,
            'enabled' => !array_key_exists('enabled', $header) || (bool) $header['enabled'],
        ]);
    }

    private function parseColumns(array $rawColumns, SmartExportEngineImportPreview $preview): void
    {
        $columns = [];
        foreach ($rawColumns as $index => $rawColumn) {
            if (!is_array($rawColumn)) {
                $preview->addError(sprintf('seb.import.error.invalid_column|%d', $index + 1));
                continue;
            }

            $classProperty = trim((string) ($rawColumn['classProperty'] ?? ''));
            if ('' === $classProperty) {
                $preview->addError(sprintf('seb.import.error.column_missing_class_property|%d', $index + 1));
                continue;
            }

            $cellGroupIndex = isset($rawColumn['cellGroupIndex']) ? trim((string) $rawColumn['cellGroupIndex']) : '';
            $filterDefaultValue = isset($rawColumn['filterDefaultValue']) ? (string) $rawColumn['filterDefaultValue'] : '';

            $columns[] = [
                'choicePosition' => isset($rawColumn['choicePosition']) ? (int) $rawColumn['choicePosition'] : null,
                'classProperty' => $classProperty,
                'label' => isset($rawColumn['label']) ? (string) $rawColumn['label'] : null,
                'cellGroupIndex' => '' !== $cellGroupIndex ? $cellGroupIndex : null,
                'interpreter' => isset($rawColumn['interpreter']) ? (string) $rawColumn['interpreter'] : null,
                'enabled' => !array_key_exists('enabled', $rawColumn) || (bool) $rawColumn['enabled'],
                'columnDisplay' => !array_key_exists('columnDisplay', $rawColumn) || (bool) $rawColumn['columnDisplay'],
                'selectedByDefault' => (bool) ($rawColumn['selectedByDefault'] ?? false),
                'filterable' => (bool) ($rawColumn['filterable'] ?? false),
                'filterDisplay' => !array_key_exists('filterDisplay', $rawColumn) || (bool) $rawColumn['filterDisplay'],
                'filterDefaultValue' => '' !== $filterDefaultValue ? $filterDefaultValue : null,
                'filterWidget' => FilterWidget::tryFrom((string) ($rawColumn['filterWidget'] ?? '')) ?? FilterWidget::Auto,
            ];
        }

        $preview->setColumns($columns);
    }
}
