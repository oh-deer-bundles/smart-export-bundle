<?php

namespace Odb\SmartExportBundle\Services;


use Laminas\Code\Exception\InvalidArgumentException;
use RuntimeException;
use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Odb\SmartExportBundle\Model\ExportSettings;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistanceClassMetadata;
use Exception;

class SmartExportQuery implements SmartExportQueryInterface
{

    private array $metas = [];


    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Parse settings objects engine and columns (columns must be ordered as expected)
     * build ORM QueryBuilder and executes it,
     * parse the result to return an array of data formatted like expected for file
     * @param ExportSettings $exportSettings
     * @return array
     * @throws Exception
     */
    public function getDataFromExportSettings(ExportSettings $exportSettings): array
    {
        if($exportSettings->getEngine() instanceof SmartExportEngine) {
            $primaryClassName = $exportSettings->getEngine()->getClassName();
            $meta = $this->getMetaFromEntityClass($primaryClassName);
            if(!$meta) {
                throw new InvalidArgumentException('can\t retrieve Doctrine ClassMetadata from className : '.$primaryClassName);
            }
            $primaryEntity = $meta->getName();
            $primaryProperties = $this->getNameProperties($primaryClassName);
            $primaryAssociations = $this->getNameAssociations($primaryClassName);
            $primaryAlias = 'a_'.strtolower($primaryClassName);
            $queryParameters = [];
            $loop = 0;
            foreach ($exportSettings->getColumns() as $key => $columns) {
                foreach ($columns as $column) {
                    if ($column instanceof SmartExportColumn) {
                        $propertyHierarchy = explode('.', $column->getClassProperty());
                        if(1 === count($propertyHierarchy) && in_array($propertyHierarchy[0], $primaryProperties, true )){
                            $rules = [];
                            if($column->getInterpreter() === 'string' && $column->getCellGroupIndex()){
                                $rules['append'] = true;
                                $rules['separator'] = ' ';
                            }

                            $queryParameters[$loop] = [
                                'exportKey' => $key,
                                'exportLabel' => $column->getHeaderLabel(),
                                'interpreter' => $column->getInterpreter(),
                                'rules' => $rules,
                                'entity' =>  $primaryAlias,
                                'property' => $propertyHierarchy[0],
                                'select' => $primaryAlias.'.'.$propertyHierarchy[0].' as '.strtolower($primaryAlias.'_'.$propertyHierarchy[0]),
                                'aliasEntity' => $primaryAlias,
                                'aliasField' => strtolower($primaryAlias.'_'.$propertyHierarchy[0])
                            ];
                            ++$loop;
                        } elseif (1 < count($propertyHierarchy) && in_array($propertyHierarchy[0], $primaryAssociations, true )) {
                            $queryParameter =  $this->getQueryParametersFromAssociation(
                                $primaryClassName,
                                $primaryAlias,
                                $propertyHierarchy[0],
                                0,
                                $propertyHierarchy[1],
                                $key,
                                $column
                            );

                            if(is_array($queryParameter)) {
                                $queryParameters[$loop] = $queryParameter;
                                $loop++;
                            }
                        }
                    }
                }
            }

            if (0 < count($queryParameters)) {
                $queryResult = $this->executeQuery($queryParameters, $primaryEntity, $primaryAlias);
                return $this->dataBuilder($queryParameters, $queryResult?:[], $exportSettings->getFileFormat());
            }
            throw new RuntimeException('Can\t retrieve query parameters from settings');
        }

        throw new RuntimeException('Settings doesn\'t contain a valid SmartExportEngine class');
    }

    public function getAdminSelectClasses() :array
    {
        $response = [];
        foreach ($this->getMetas() as $meta) {
            $class_name = substr($meta->getName(), strrpos($meta->getName(), '\\') + 1);
            $response[$meta->getName()] = $class_name;
        }
        ksort($response);
        return $response;
    }

    public function getAdminSelectPropertiesAndAssociations(string $entity_class = null) :array
    {
        $response = [];
        if($entity_class){
            $properties = $this->getNameProperties($entity_class);
            foreach ($properties as $property){
                $response['Fields'][$property] = $property;
            }
            $associations = $this->getNameAssociations($entity_class);
            foreach ($associations as $association){
                $response['Associations'][$association] = $association;
            }
        }

        ksort($response['Fields']);
        ksort($response['Associations']);
        return $response;
    }

    /**
     * This recursive function use for each relation field
     * @param string $parentEntity
     * @param string $parentAlias
     * @param string $parentProperty
     * @param int $parentDeeper
     * @param string $childProperty
     * @param string $key
     * @param SmartExportColumn $column
     * @param array|null $joins
     * @return array|null
     */
    private function getQueryParametersFromAssociation(
        string $parentEntity,
        string $parentAlias,
        string $parentProperty,
        int $parentDeeper,
        string $childProperty,
        string $key,
        SmartExportColumn $column,
        array $joins = null
    ): ?array
    {
        $entity = $this->getEntityFromAssociation($parentEntity, $parentProperty);

        $rules = [];
        if($column->getInterpreter() === 'string' && $column->getCellGroupIndex()){
            $rules['append'] = true;
            $rules['separator'] = ' ';
        }

        $entityAlias = 'a_'.strtolower($parentProperty);
        $joins = $joins ?
            array_merge($joins, [['join' =>$parentAlias.'.'.$parentProperty, 'alias' => $entityAlias]] )
            : [['join' =>$parentAlias.'.'.$parentProperty, 'alias' => $entityAlias]];


        if (in_array($childProperty,$this->getNameProperties($entity), true )) {
            return [
                'exportKey' => $key,
                'exportLabel' => $column->getHeaderLabel(),
                'interpreter' => $column->getInterpreter(),
                'rules' => $rules,
                'entity' =>  $entity,
                'property' => $childProperty,
                'joins' => $joins,
                'select' => $entityAlias.'.'.$childProperty.' as '.strtolower($entityAlias.'_'.$childProperty),
                'aliasEntity' => $entityAlias,
                'aliasField' => strtolower($entityAlias.'_'.$childProperty)
            ];
        }

        if(in_array($childProperty,$this->getNameAssociations($entity), true )) {
            $propertyHierarchy = explode('.', $column->getClassProperty());
            if($parentDeeper + 2 < count($propertyHierarchy)) {
                return $this->getQueryParametersFromAssociation(
                    $entity,
                    $entityAlias,
                    $propertyHierarchy[$parentDeeper+1],
                    $parentDeeper+1,
                    $propertyHierarchy[$parentDeeper+2],
                    $key,
                    $column,
                    $joins
                );
            }
        }

        return null;
    }

    /**
     * Create an ORM QueryBuilder and return the execution
     */
    private function executeQuery( array $queryParameters, string $primaryClassName, string $primaryAlias): int|array|string
    {
        $qb = new QueryBuilder($this->entityManager);
        $qb->from($primaryClassName, $primaryAlias);
        $joins = [];
        foreach ($queryParameters as $parameter){
            $qb->addSelect($parameter['select']);
            if (array_key_exists('joins', $parameter) && is_array($parameter['joins'])) {
                foreach ($parameter['joins'] as $joinParameter){
                    if(array_key_exists('alias', $joinParameter) && !in_array($joinParameter['join'], $joins, true)){
                        $qb->leftJoin($joinParameter['join'], $joinParameter['alias']);
                        $joins[] = $joinParameter['join'];
                    }
                }
            }
        }
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Create an array with query result, and different settings like cellGroup, columnGroup and format the values
     * @param array $queryParameters
     * @param array $queryResult
     * @param string $file_format
     * @return array
     */
    private function dataBuilder(array $queryParameters, array $queryResult, string $file_format) : array
    {
        $data = [];
        foreach ($queryParameters as $fieldSettings){
            $data[0][$fieldSettings['exportKey']] = $fieldSettings['exportLabel'];
            $data_loop = 1;
            foreach ($queryResult as $row){
                $initialValue = $data[$data_loop][$fieldSettings['exportKey']] ?? null;
                $data[$data_loop][$fieldSettings['exportKey']] = SmartExportInterpreter::translate($fieldSettings['interpreter'], $fieldSettings['rules'], $row[$fieldSettings['aliasField']], $initialValue, $file_format);
                ++$data_loop;
            }
        }

        return $data;
    }

    /**
     * @param string $entity_class
     * @return array
     */
    private function getNameProperties(string $entity_class):array
    {
        $response = [];
        $meta = $this->getMetaFromEntityClass($entity_class);
        //dd($entity_class);
        if($meta){
            foreach ($meta->getFieldNames() as $property){
                $response[] = $property;
            }
        }
        return $response;
    }

    /**
     * @param string $entity_class
     * @return array
     */
    private function getNameAssociations(string $entity_class):array
    {
        $response = [];
        $meta = $this->getMetaFromEntityClass($entity_class);
        if($meta){
            foreach ($meta->getAssociationNames() as $association){
                $response[] = $association;
            }
        }
        return $response;
    }

    /**
     * @param string $entity_class
     * @param string $association_name
     * @return string|null
     */
    private function getEntityFromAssociation(string $entity_class, string $association_name):?string
    {
        $meta = $this->getMetaFromEntityClass($entity_class);
        if($meta){
            $target_class = $meta->getAssociationTargetClass($association_name);
            return substr($target_class, strrpos($target_class, '\\') + 1);
        }
        return null;
    }

    /**
     * @param string $entity_class
     * @return ClassMetadata|null
     */
    private function getMetaFromEntityClass(string $entity_class) :?ClassMetadata
    {
        foreach ($this->getMetas() as $meta) {
            $class_name = substr($meta->getName(), strrpos($meta->getName(), '\\') + 1);
            if($class_name === $entity_class){
                return $meta;
            }
        }
        return null;
    }

    /**
     * @return array|PersistanceClassMetadata[]
     */
    private function getMetas(): array
    {
        if(!$this->metas){
            $this->metas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        }
        return $this->metas;
    }
}
