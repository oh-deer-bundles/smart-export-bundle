<?php


namespace Odb\SmartExportBundle\Services;

use Odb\SmartExportBundle\Model\ExportSettings;

interface SmartExportQueryInterface
{
    public function getDataFromExportSettings(ExportSettings $exportSettings): array;
    public function getAdminSelectClasses() :array;
    public function getAdminSelectPropertiesAndAssociations(string $entity_class = null) :array;
}