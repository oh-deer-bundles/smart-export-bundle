<?php


namespace Tbl\SmartExportBundle\Services;


interface SmartExportChoiceInterface
{
    public function getChoices(string $engineCode) :array;
    public function parseChoices(string $engineCode, string $export_fields_value):array;
}