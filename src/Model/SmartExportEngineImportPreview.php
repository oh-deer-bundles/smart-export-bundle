<?php

namespace Odb\SmartExportBundle\Model;

use Odb\SmartExportBundle\Entity\SmartExportEngine;

/**
 * Result of SmartExportEngineTransfer::parseImport(): pure parsing/validation,
 * no database writes (the "create if missing, matched only if the export's
 * uuid is found" lookup aside). Rendered as a confirmation screen — the user
 * must explicitly confirm before SmartExportEngineTransfer::commitImport()
 * actually persists anything.
 */
class SmartExportEngineImportPreview
{
    /** @var string[] */
    private array $errors = [];

    /** @var string[] */
    private array $warnings = [];

    private array $header = [];

    /** @var array<int, array<string, mixed>> */
    private array $columns = [];

    private ?SmartExportEngine $existingEngine = null;

    public function __construct(
        private readonly string $rawJson,
    ) {
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    /** @return string[] */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function setHeader(array $header): self
    {
        $this->header = $header;

        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @param array<int, array<string, mixed>> $columns */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function getExistingEngine(): ?SmartExportEngine
    {
        return $this->existingEngine;
    }

    public function setExistingEngine(?SmartExportEngine $existingEngine): self
    {
        $this->existingEngine = $existingEngine;

        return $this;
    }

    public function isUpdate(): bool
    {
        return null !== $this->existingEngine;
    }

    public function getRawJson(): string
    {
        return $this->rawJson;
    }
}
