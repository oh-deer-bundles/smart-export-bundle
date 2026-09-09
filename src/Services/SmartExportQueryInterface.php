<?php


namespace Odb\SmartExportBundle\Services;

use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Odb\SmartExportBundle\Model\ExportSettings;

interface SmartExportQueryInterface
{
    public function getDataFromExportSettings(ExportSettings $exportSettings): array;
    public function countDataFromExportSettings(ExportSettings $exportSettings): int;
    public function getAdminSelectClasses() :array;
    public function getAdminSelectPropertiesAndAssociations(string $entity_class = null) :array;

    /**
     * @return array<string> Distinct, non-null real values currently in the database for
     *                        $column's path off $engine's primary entity (same allowed-ids
     *                        security restriction as a real export applies to any restricted
     *                        entity along the way).
     */
    public function getDistinctValues(SmartExportEngine $engine, SmartExportColumn $column): array;
}