<?php

namespace Odb\SmartExportBundle\Repository;

use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SmartExportColumn|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmartExportColumn|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmartExportColumn[]    findAll()
 * @method SmartExportColumn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmartExportColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmartExportColumn::class);
    }

    public function getChoicesByEngineUuid(string $engineUuid) :array
    {
        $qb = $this->createQueryBuilder('sec')
            ->leftJoin('sec.engine', 'see')
            ->select(
                'sec.id as id',
                'sec.choiceLabel as label',
                'sec.columnGroupIndex as columnGroup',
                'sec.cellGroupIndex as cellGroup'
            )
            ->where('see.uuid = :engineUuid')
            ->andWhere('sec.enabled = 1')
            ->setParameter('engineUuid', $engineUuid, 'uuid')
            ->orderBy('sec.choicePosition', 'ASC')
            ->addOrderBy('sec.choiceLabel','ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function getColumnsByEngineUuid(string $engineUuid)
    {
        $qb = $this->createQueryBuilder('sec')
            ->leftJoin('sec.engine', 'see')
            ->where('see.uuid = :engineUuid')
            ->andWhere('sec.enabled = 1')
            ->setParameter('engineUuid', $engineUuid, 'uuid')
            ->orderBy('sec.choicePosition', 'ASC')
            ->addOrderBy('sec.choiceLabel','ASC');

        return $qb->getQuery()->getResult();
    }

    public function remove(SmartExportColumn $column, ?bool $withFlush = true): void
    {
        $this->getEntityManager()->remove($column);
        if($withFlush) {
            $this->getEntityManager()->flush();
        }
    }
}
