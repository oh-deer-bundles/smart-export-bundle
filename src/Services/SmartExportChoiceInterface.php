<?php


namespace Odb\SmartExportBundle\Services;


interface SmartExportChoiceInterface
{
    public function getChoices(string $engineUuid) :array;
    public function parseChoices(string $engineUuid, string $export_fields_value):array;

    /**
     * @return \Odb\SmartExportBundle\Entity\SmartExportColumn[]
     */
    public function getFilterableColumns(string $engineUuid): array;

    /**
     * @param array<int, array{operator: string, value: mixed, value2: mixed}> $rawFilters keyed by column id
     * @return array<int, array{column: \Odb\SmartExportBundle\Entity\SmartExportColumn, operator: string, value: mixed, value2: mixed}>
     */
    public function resolveFilters(string $engineUuid, array $rawFilters): array;

    /**
     * @return array<string> Distinct, non-null real values currently in the database for
     *                        this column's path (e.g. all existing category names) — used
     *                        to populate a SmartExportColumn::FILTER_WIDGET_SELECT filter.
     */
    public function getDistinctValuesForColumn(\Odb\SmartExportBundle\Entity\SmartExportColumn $column): array;
}