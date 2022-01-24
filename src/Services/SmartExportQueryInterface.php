<?php


namespace Tbl\SmartExportBundle\Services;

use Tbl\SmartExportBundle\Model\ExportSettings;

interface SmartExportQueryInterface
{
    public function getDataFromExportSettings(ExportSettings $exportSettings): array;
    public function getAdminSelectClasses() :array;
    public function getAdminSelectPropertiesAndAssociations(string $entity_class = null) :array;
}