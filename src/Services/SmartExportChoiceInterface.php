<?php


namespace Odb\SmartExportBundle\Services;


interface SmartExportChoiceInterface
{
    public function getChoices(string $engineUuid) :array;
    public function parseChoices(string $engineUuid, string $export_fields_value):array;
}