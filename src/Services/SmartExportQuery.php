<?php

namespace Odb\SmartExportBundle\Services;


use DateTime;
use InvalidArgumentException;
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


    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AllowedIdsResolver $allowedIdsResolver,
    )
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
        ['primaryEntity' => $primaryEntity, 'primaryAlias' => $primaryAlias, 'queryParameters' => $queryParameters, 'filters' => $filters]
            = $this->resolveQueryParameters($exportSettings);

        $queryResult = $this->executeQuery($queryParameters, $filters, $primaryEntity, $primaryAlias, $exportSettings->getIdFilter());
        return $this->dataBuilder($queryParameters, $queryResult?:[], $exportSettings->getFileFormat());
    }

    /**
     * Same column/join resolution as getDataFromExportSettings(), but returns
     * the number of matching rows instead of fetching them, so the admin can
     * check the result size before generating a potentially huge export.
     * @param ExportSettings $exportSettings
     * @return int
     * @throws Exception
     */
    public function countDataFromExportSettings(ExportSettings $exportSettings): int
    {
        ['primaryEntity' => $primaryEntity, 'primaryAlias' => $primaryAlias, 'queryParameters' => $queryParameters, 'filters' => $filters]
            = $this->resolveQueryParameters($exportSettings);

        return $this->executeCountQuery($queryParameters, $filters, $primaryEntity, $primaryAlias, $exportSettings->getIdFilter());
    }

    /**
     * Parse settings objects engine and columns (columns must be ordered as expected)
     * and resolve, for each column, the join(s) and select expression needed to fetch it.
     * @param ExportSettings $exportSettings
     * @return array{primaryEntity: string, primaryAlias: string, queryParameters: array, filters: array}
     * @throws Exception
     */
    private function resolveQueryParameters(ExportSettings $exportSettings): array
    {
        if (!$exportSettings->getEngine() instanceof SmartExportEngine) {
            throw new RuntimeException('Settings doesn\'t contain a valid SmartExportEngine class');
        }

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
                            'exportLabel' => $column->getLabel(),
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

        if (0 === count($queryParameters)) {
            throw new RuntimeException('Can\t retrieve query parameters from settings');
        }

        $filters = [];
        foreach ($exportSettings->getFilters() as $resolvedFilter) {
            $location = $this->resolveFilterLocation($primaryClassName, $primaryAlias, $primaryProperties, $primaryAssociations, $resolvedFilter['column']);
            if (null === $location) {
                continue;
            }

            $filters[] = $location + [
                'interpreter' => $resolvedFilter['column']->getInterpreter(),
                'operator' => $resolvedFilter['operator'],
                'value' => $resolvedFilter['value'],
                'value2' => $resolvedFilter['value2'],
            ];
        }

        return [
            'primaryEntity' => $primaryEntity,
            'primaryAlias' => $primaryAlias,
            'queryParameters' => $queryParameters,
            'filters' => $filters,
        ];
    }

    /**
     * Resolves a filter's column path (same dotted "a.b.c" syntax as export columns) to the
     * DQL alias/property/joins needed to filter on it. Reuses getQueryParametersFromAssociation()
     * unchanged for the relation case, so a filter on a joined/nested relation walks the join
     * graph exactly like a SELECTed column on the same path would.
     * @return array{alias: string, property: string, joins: array}|null
     */
    private function resolveFilterLocation(
        string $primaryClassName,
        string $primaryAlias,
        array $primaryProperties,
        array $primaryAssociations,
        SmartExportColumn $column
    ): ?array
    {
        $propertyHierarchy = explode('.', $column->getClassProperty());

        if (1 === count($propertyHierarchy) && in_array($propertyHierarchy[0], $primaryProperties, true)) {
            return ['alias' => $primaryAlias, 'property' => $propertyHierarchy[0], 'joins' => []];
        }

        if (1 < count($propertyHierarchy) && in_array($propertyHierarchy[0], $primaryAssociations, true)) {
            $resolved = $this->getQueryParametersFromAssociation(
                $primaryClassName,
                $primaryAlias,
                $propertyHierarchy[0],
                0,
                $propertyHierarchy[1],
                '__filter__',
                $column
            );

            if (is_array($resolved)) {
                return ['alias' => $resolved['aliasEntity'], 'property' => $resolved['property'], 'joins' => $resolved['joins']];
            }
        }

        return null;
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
        $entityFqcn = $this->getEntityFqcnFromAssociation($parentEntity, $parentProperty);

        $rules = [];
        if($column->getInterpreter() === 'string' && $column->getCellGroupIndex()){
            $rules['append'] = true;
            $rules['separator'] = ' ';
        }

        $entityAlias = 'a_'.strtolower($parentProperty);
        $joinEntry = ['join' => $parentAlias.'.'.$parentProperty, 'alias' => $entityAlias, 'entityClass' => $entityFqcn];
        $joins = $joins ? array_merge($joins, [$joinEntry]) : [$joinEntry];


        if (in_array($childProperty,$this->getNameProperties($entity), true )) {
            return [
                'exportKey' => $key,
                'exportLabel' => $column->getLabel(),
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
     * Builds the ORM QueryBuilder shared by executeQuery() and executeCountQuery():
     * the primary FROM plus every join required by the resolved columns, plus the
     * allowed-ids restriction on every alias (primary or joined) whose entity class
     * is configured as restricted, without a SELECT clause (each caller adds its own).
     *
     * Security is a property of the ENTITY (security.restricted_entities bundle
     * config), not of the export: a restricted entity must be filtered wherever it
     * appears in the join graph, whether it's the export's primary entity or reached
     * through a relation (e.g. an "Item -> Contract -> Customer" export must still
     * filter on Customer if Customer is restricted, exactly as a "Customer -> Contract
     * -> Item" export would).
     */
    private function buildQueryBuilder(array $queryParameters, array $filters, string $primaryClassName, string $primaryAlias, array $idFilter = []): QueryBuilder
    {
        $qb = new QueryBuilder($this->entityManager);
        $qb->from($primaryClassName, $primaryAlias);

        // Filters can join a relation that no SELECTed column uses (e.g. filtering on
        // contract.customer.name while only exporting item fields) — their joins must be
        // registered the same way as output columns' joins, both so the DQL path resolves
        // and so a restricted entity reached only through a filter is still caught below.
        // Only an ACTIVE filter (real value submitted) earns its join though: an untouched
        // filter row contributes no WHERE clause (see applyFilters()/isActiveFilter()), and
        // joining its relation anyway would fan the primary row out — one duplicate per
        // related row — for a to-many relation the export never asked for.
        $joinSources = $queryParameters;
        foreach ($filters as $filter) {
            if ($this->isActiveFilter($filter) && !empty($filter['joins'])) {
                $joinSources[] = ['joins' => $filter['joins']];
            }
        }

        $aliasToEntityClass = $this->registerJoins($qb, $joinSources, $primaryAlias, $primaryClassName);
        $this->applySecurityRestrictions($qb, $aliasToEntityClass);
        $this->applyIdFilter($qb, $primaryAlias, $idFilter);
        $this->applyFilters($qb, $filters);

        return $qb;
    }

    /**
     * Explicit id restriction requested by the caller (smart_export_popup()'s
     * `id` option: a single id or an array of ids of the export's PRIMARY
     * entity — e.g. scoping a "Télécharger" link on a customer row to that
     * one customer, or on a contract's items to that contract's item ids).
     * Applied as an additional AND'd WHERE clause: it only narrows the
     * export further, it never bypasses applySecurityRestrictions() above —
     * a restricted entity's allowed-ids check still applies on top, exactly
     * like any other filter, so a caller can never pass an id they don't
     * have access to and see it exported anyway.
     */
    private function applyIdFilter(QueryBuilder $qb, string $primaryAlias, array $idFilter): void
    {
        if (empty($idFilter)) {
            return;
        }

        $qb->andWhere($qb->expr()->in($primaryAlias.'.id', ':odbSmartExportIdFilter'))
            ->setParameter('odbSmartExportIdFilter', array_values($idFilter));
    }

    /**
     * Distinct, non-null real values currently in the database for $column's path off
     * $engine's primary entity — used to populate a FILTER_WIDGET_SELECT filter (e.g. all
     * existing category names) instead of asking the user to type free text. Same
     * allowed-ids security restriction as a real export applies to any restricted entity
     * reached along the way. Capped at 500 values as a safety net against a column
     * mistakenly marked as a select despite having very high cardinality.
     */
    public function getDistinctValues(SmartExportEngine $engine, SmartExportColumn $column): array
    {
        $primaryClassName = $engine->getClassName();
        $meta = $this->getMetaFromEntityClass($primaryClassName);
        if (!$meta) {
            return [];
        }

        $primaryEntity = $meta->getName();
        $primaryAlias = 'a_'.strtolower($primaryClassName);
        $primaryProperties = $this->getNameProperties($primaryClassName);
        $primaryAssociations = $this->getNameAssociations($primaryClassName);

        $location = $this->resolveFilterLocation($primaryClassName, $primaryAlias, $primaryProperties, $primaryAssociations, $column);
        if (null === $location) {
            return [];
        }

        $field = $location['alias'].'.'.$location['property'];

        $qb = new QueryBuilder($this->entityManager);
        $qb->select('DISTINCT '.$field.' AS value')->from($primaryEntity, $primaryAlias);

        $aliasToEntityClass = $this->registerJoins($qb, [['joins' => $location['joins']]], $primaryAlias, $primaryEntity);
        $this->applySecurityRestrictions($qb, $aliasToEntityClass);

        $qb->andWhere($qb->expr()->isNotNull($field))
            ->orderBy($field, 'ASC')
            ->setMaxResults(500);

        return array_map('strval', array_column($qb->getQuery()->getArrayResult(), 'value'));
    }

    /**
     * Registers every join in $joinSources (deduped by DQL join path) on $qb, seeded with
     * the primary FROM alias/class. Shared by buildQueryBuilder() and getDistinctValues()
     * so both apply the exact same join graph and, from its return value, the exact same
     * security restriction (see applySecurityRestrictions()).
     * @return array<string, string> alias => entity FQCN, for every registered alias.
     */
    private function registerJoins(QueryBuilder $qb, array $joinSources, string $primaryAlias, string $primaryClassName): array
    {
        $aliasToEntityClass = [$primaryAlias => $primaryClassName];
        $joinedAliases = [];
        foreach ($joinSources as $parameter){
            if (array_key_exists('joins', $parameter) && is_array($parameter['joins'])) {
                foreach ($parameter['joins'] as $joinParameter){
                    if(array_key_exists('alias', $joinParameter) && !in_array($joinParameter['join'], $joinedAliases, true)){
                        $qb->leftJoin($joinParameter['join'], $joinParameter['alias']);
                        $joinedAliases[] = $joinParameter['join'];
                        if (!empty($joinParameter['entityClass'])) {
                            $aliasToEntityClass[$joinParameter['alias']] = $joinParameter['entityClass'];
                        }
                    }
                }
            }
        }
        return $aliasToEntityClass;
    }

    /**
     * Applies the allowed-ids restriction to every alias (primary or joined) whose entity
     * class is configured as restricted (security.restricted_entities). Security is a
     * property of the ENTITY, not of the export: a restricted entity must be filtered
     * wherever it appears in the join graph, whether it's the export's primary entity or
     * reached through a relation (e.g. an "Item -> Contract -> Customer" export must still
     * filter on Customer if Customer is restricted, exactly as a "Customer -> Contract ->
     * Item" export would) — including a distinct-values lookup for a select filter.
     */
    private function applySecurityRestrictions(QueryBuilder $qb, array $aliasToEntityClass): void
    {
        $paramIndex = 0;
        foreach ($aliasToEntityClass as $alias => $entityClass) {
            $allowedIds = $this->allowedIdsResolver->getAllowedIdsIfRestricted($entityClass);
            if (null === $allowedIds) {
                continue;
            }

            if (empty($allowedIds)) {
                // Fail closed: restriction active but nothing cached for this user = no rows, never "unrestricted".
                $qb->andWhere('1 = 0');
                break;
            }

            $paramName = 'odbSmartExportAllowedIds'.$paramIndex++;
            $qb->andWhere($alias.'.id IN (:'.$paramName.')')
                ->setParameter($paramName, $allowedIds);
        }
    }

    /**
     * Create an ORM QueryBuilder and return the execution
     */
    private function executeQuery(array $queryParameters, array $filters, string $primaryClassName, string $primaryAlias, array $idFilter = []): int|array|string
    {
        $qb = $this->buildQueryBuilder($queryParameters, $filters, $primaryClassName, $primaryAlias, $idFilter);
        foreach ($queryParameters as $parameter){
            $qb->addSelect($parameter['select']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Same joins as executeQuery(), but counts matching rows instead of fetching them.
     * Deliberately NOT DISTINCT: executeQuery()/getArrayResult() does not deduplicate
     * either, so a column on a to-many relation (e.g. contracts.items.name) fans out
     * into one row per joined item. The count must match that real, unaggregated row
     * count — that fan-out is exactly the runaway-export scenario max_rows guards
     * against, so counting distinct primary ids would silently defeat the limit.
     */
    private function executeCountQuery(array $queryParameters, array $filters, string $primaryClassName, string $primaryAlias, array $idFilter = []): int
    {
        $qb = $this->buildQueryBuilder($queryParameters, $filters, $primaryClassName, $primaryAlias, $idFilter);
        $qb->select('COUNT('.$primaryAlias.'.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Whether a resolved filter actually has what it needs to apply a WHERE clause (and,
     * per buildQueryBuilder(), to earn its relation's join): a real, non-empty value, plus
     * a second value too for an operator like "between". An untouched filter row (default
     * operator, empty value) is not active — see SmartExportFilterType/getRawSubmittedFilters()
     * docblocks for why "no filter" is represented as an empty value rather than a missing row.
     */
    private function isActiveFilter(array $filter): bool
    {
        // A multi-select ("in"/"not_in") submits an array; every other widget submits a
        // scalar. Guard both shapes explicitly rather than casting to string, which would
        // turn an empty array into the non-empty string "Array" and let it through.
        $value = $filter['value'];
        $isEmptyValue = is_array($value) ? [] === $value : (null === $value || '' === (string) $value);
        if ($isEmptyValue) {
            return false;
        }

        return !SmartExportFilterOperators::needsSecondValue($filter['operator'])
            || ('' !== (string) $filter['value2'] && null !== $filter['value2']);
    }

    /**
     * Translates each resolved, active filter criterion into an andWhere clause. An
     * inactive filter (see isActiveFilter()) is skipped rather than applied with an
     * empty/null value (which for LIKE or BETWEEN would either match everything or throw).
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        foreach ($filters as $index => $filter) {
            if (!$this->isActiveFilter($filter)) {
                continue;
            }

            $field = $filter['alias'].'.'.$filter['property'];
            $operator = $filter['operator'];
            $paramName = 'odbSmartExportFilter'.$index;
            $value = $filter['value'];

            switch ($operator) {
                case 'contains':
                    $qb->andWhere($qb->expr()->like($field, ':'.$paramName))->setParameter($paramName, '%'.$filter['value'].'%');
                    break;
                case 'not_contains':
                    $qb->andWhere($qb->expr()->notLike($field, ':'.$paramName))->setParameter($paramName, '%'.$filter['value'].'%');
                    break;
                case 'starts_with':
                    $qb->andWhere($qb->expr()->like($field, ':'.$paramName))->setParameter($paramName, $filter['value'].'%');
                    break;
                case 'ends_with':
                    $qb->andWhere($qb->expr()->like($field, ':'.$paramName))->setParameter($paramName, '%'.$filter['value']);
                    break;
                case 'equal':
                    $qb->andWhere($qb->expr()->eq($field, ':'.$paramName))->setParameter($paramName, $this->castFilterValue($filter, 'value'));
                    break;
                case 'gt':
                    $qb->andWhere($qb->expr()->gt($field, ':'.$paramName))->setParameter($paramName, $this->castFilterValue($filter, 'value'));
                    break;
                case 'gte':
                    $qb->andWhere($qb->expr()->gte($field, ':'.$paramName))->setParameter($paramName, $this->castFilterValue($filter, 'value'));
                    break;
                case 'lt':
                case 'before':
                    $qb->andWhere($qb->expr()->lt($field, ':'.$paramName))->setParameter($paramName, $this->castFilterValue($filter, 'value'));
                    break;
                case 'lte':
                    $qb->andWhere($qb->expr()->lte($field, ':'.$paramName))->setParameter($paramName, $this->castFilterValue($filter, 'value'));
                    break;
                case 'after':
                    $qb->andWhere($qb->expr()->gt($field, ':'.$paramName))->setParameter($paramName, $this->castFilterValue($filter, 'value'));
                    break;
                case 'between':
                    $qb->andWhere($qb->expr()->between($field, ':'.$paramName.'_from', ':'.$paramName.'_to'))
                        ->setParameter($paramName.'_from', $this->castFilterValue($filter, 'value'))
                        ->setParameter($paramName.'_to', $this->castFilterValue($filter, 'value2'));
                    break;
                case 'in':
                    $qb->andWhere($qb->expr()->in($field, ':'.$paramName))
                        ->setParameter($paramName, is_array($value) ? array_values($value) : [$value]);
                    break;
                case 'not_in':
                    $qb->andWhere($qb->expr()->notIn($field, ':'.$paramName))
                        ->setParameter($paramName, is_array($value) ? array_values($value) : [$value]);
                    break;
            }
        }
    }

    /**
     * Casts a filter's raw submitted value according to the column's interpreter, so
     * e.g. a numeric comparison isn't done on strings and a date filter gets a real
     * DateTime bound parameter.
     */
    private function castFilterValue(array $filter, string $key): int|float|bool|DateTime|string
    {
        $raw = $filter[$key];
        return match (true) {
            in_array($filter['interpreter'], ['integer', 'int'], true) => (int) $raw,
            in_array($filter['interpreter'], ['float', 'euro'], true) => (float) str_replace(',', '.', (string) $raw),
            'date' === $filter['interpreter'] => new DateTime((string) $raw),
            in_array($filter['interpreter'], ['boolean', 'boolean_translated'], true) => (bool) $raw,
            default => (string) $raw,
        };
    }

    /**
     * Create an array with query result, and different settings like cellGroup and format the values
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
     * Same as getEntityFromAssociation() but returns the joined entity's fully
     * qualified class name (untruncated), needed to check it against the
     * security.restricted_entities bundle config regardless of where in the
     * join graph it appears.
     * @param string $entity_class
     * @param string $association_name
     * @return string|null
     */
    private function getEntityFqcnFromAssociation(string $entity_class, string $association_name):?string
    {
        $meta = $this->getMetaFromEntityClass($entity_class);
        if($meta){
            return $meta->getAssociationTargetClass($association_name);
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
