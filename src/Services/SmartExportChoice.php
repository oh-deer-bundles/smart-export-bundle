<?php


namespace Odb\SmartExportBundle\Services;


use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Repository\SmartExportColumnRepository;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;

class SmartExportChoice implements SmartExportChoiceInterface
{

    public function __construct(
        private readonly SmartExportEngineRepository $exportEngineRepository,
        private readonly SmartExportColumnRepository $exportColumnRepository
    ) {
    }

    /**
     * @param string $engineCode
     * @return array
     */
    public function getChoices(string $engineCode) :array
    {
        $choices_em = $this->exportColumnRepository->getChoicesByEngineCode($engineCode);
        $response = [];
        $columnGroups = [];
        $cellGroups = [];
        foreach ($choices_em as $row) {
            if(
                $row['columnGroup'] &&
                (
                    (
                        !$row['cellGroup']
                        || !in_array($row['columnGroup'].'_'.$row['cellGroup'], $cellGroups,true)
                    )
                    || !in_array($row['columnGroup'], $columnGroups,true)
                )
            ) {
                $response[$row['label']] = $row['id'];
                $columnGroups[] = $row['columnGroup'];
                if($row['cellGroup']) {
                    $cellGroups[] =  $row['columnGroup'].'_'.$row['cellGroup'];
                }
            } elseif ($row['cellGroup'] && !in_array($row['cellGroup'], $cellGroups,true)) {
                $response[$row['label']] = $row['id'];
                $cellGroups[] = $row['cellGroup'];
            } elseif (!$row['columnGroup'] && !$row['cellGroup']) {
                $response[$row['label']] = $row['id'];
            }
        }

        return $response;
    }

    /**
     * @param string $engineCode
     * @param string $export_fields_value
     * @return array
     */
    public function parseChoices(string $engineCode, string $export_fields_value):array
    {

        $response = [
            'columns' => [],
            'engine' => $this->exportEngineRepository->findOneBy(['code'=> $engineCode])
        ];
        $columns_em = $this->exportColumnRepository->getColumnsByEngineCode($engineCode);
        $selectedKeys = [];
        $selectedColumns = json_decode($export_fields_value, true);
        $columns = [];
        foreach ($columns_em as $column){
            if($column instanceof SmartExportColumn) {
                $key = $column->getClassProperty();


                if($column->getCellGroupIndex()) {
                    $key = '#'.$column->getCellGroupIndex();
                }

                // todo not working
                if ($column->getColumnGroupIndex()) {
                    $key = '#'.$column->getColumnGroupIndex();
                    if(!$column->getCellGroupIndex()) {
                        $key .= '_'.$column->getClassProperty();
                    } else {
                        $key .= '_'.$column->getCellGroupIndex();
                    }
                }
                $columns[$key][] = $column;

                if (in_array($column->getId(), $selectedColumns,true)){
                    $index = array_search($column->getId(), $selectedColumns, true);
                    $selectedKeys[$index] = $key;
                }
            }
        }
        
        ksort($selectedKeys);

        foreach ($selectedKeys as $key){
            if(array_key_exists($key, $columns)) {
               $response['columns'][$key] = $columns[$key];
            }
        }

        return $response;
    }
}
