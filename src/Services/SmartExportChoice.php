<?php


namespace Odb\SmartExportBundle\Services;


use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Repository\SmartExportColumnRepository;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;

class SmartExportChoice implements SmartExportChoiceInterface
{

    public function __construct(
        private readonly SmartExportEngineRepository $exportEngineRepository,
        private readonly SmartExportColumnRepository $exportColumnRepository,
        private readonly SmartExportQueryInterface $exportQuery
    ) {
    }

    /**
     * @param string $engineUuid
     * @return array
     */
    public function getChoices(string $engineUuid) :array
    {
        $choices_em = $this->exportColumnRepository->getChoicesByEngineUuid($engineUuid);
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
     * @param string $engineUuid
     * @param string $export_fields_value
     * @return array
     */
    public function parseChoices(string $engineUuid, string $export_fields_value):array
    {

        $response = [
            'columns' => [],
            'engine' => $this->exportEngineRepository->findOneBy(['uuid'=> $engineUuid])
        ];
        $columns_em = $this->exportColumnRepository->getColumnsByEngineUuid($engineUuid);
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

    public function getFilterableColumns(string $engineUuid): array
    {
        return $this->exportColumnRepository->getFilterableColumnsByEngineUuid($engineUuid);
    }

    public function getDistinctValuesForColumn(SmartExportColumn $column): array
    {
        if (!$column->getEngine()) {
            return [];
        }

        return $this->exportQuery->getDistinctValues($column->getEngine(), $column);
    }

    public function resolveFilters(string $engineUuid, array $rawFilters): array
    {
        $response = [];
        foreach ($rawFilters as $columnId => $rawFilter) {
            $column = $this->exportColumnRepository->find($columnId);
            if (
                !$column instanceof SmartExportColumn
                || !$column->isFilterable()
                || !$column->getEngine()
                || $column->getEngine()->getUuid()?->toRfc4122() !== $engineUuid
            ) {
                continue;
            }

            $response[] = [
                'column' => $column,
                'operator' => $rawFilter['operator'],
                'value' => $rawFilter['value'] ?? null,
                'value2' => $rawFilter['value2'] ?? null,
            ];
        }

        return $response;
    }
}
