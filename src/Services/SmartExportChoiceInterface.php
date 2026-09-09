<?php


namespace Odb\SmartExportBundle\Services;


interface SmartExportChoiceInterface
{
    public function getChoices(string $engineUuid) :array;
    public function parseChoices(string $engineUuid, string $export_fields_value):array;

    /**
     * Ids of this engine's columns marked selectedByDefault, in the same
     * one-id-per-pickable-choice shape as getChoices() (a cellGroup counts
     * once, represented by its first column) — used to seed the `fields`
     * hidden field's server-side default (SmartExportType), so a submission
     * stays valid even when the Colonnes panel isn't rendered at all
     * (smart_export_popup()'s `detailed: false` option).
     * @return array<int, int>
     */
    public function getDefaultSelectedFieldIds(string $engineUuid): array;

    /**
     * Every exportable (columnDisplay=1) column for this engine, indexed by id —
     * used by the popup template to read per-choice metadata (classProperty,
     * selectedByDefault) that getChoices()'s flat label=>id shape can't carry,
     * since that shape feeds a ChoiceType's 'choices' option directly.
     * @return array<int, \Odb\SmartExportBundle\Entity\SmartExportColumn>
     */
    public function getColumnsIndexedById(string $engineUuid): array;

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