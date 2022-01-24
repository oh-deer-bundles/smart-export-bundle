<?php

namespace Tbl\SmartExportBundle\Repository;

use Tbl\SmartExportBundle\Entity\SmartExportColumn;
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

    public function getChoicesByEngineCode(string $engineCode) :array
    {
        $qb = $this->createQueryBuilder('sec')
            ->leftJoin('sec.engine', 'see')
            ->select(
                'sec.id as id',
                'sec.choiceLabel as label',
                'sec.columnGroupIndex as columnGroup',
                'sec.cellGroupIndex as cellGroup'
            )
            ->where('see.code = :engineCode')
            ->andWhere('sec.isActive = 1')
            ->setParameter('engineCode', $engineCode)
            ->orderBy('sec.choicePosition', 'ASC')
            ->addOrderBy('sec.choiceLabel','ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function getColumnsByEngineCode(string $engineCode)
    {
        $qb = $this->createQueryBuilder('sec')
            ->leftJoin('sec.engine', 'see')
            ->where('see.code = :engineCode')
            ->andWhere('sec.isActive = 1')
            ->setParameter('engineCode', $engineCode)
            ->orderBy('sec.choicePosition', 'ASC')
            ->addOrderBy('sec.choiceLabel','ASC');

        return $qb->getQuery()->getResult();
    }

    public function remove(SmartExportColumn $column, ?bool $withFlush = true): void
    {
        $this->_em->remove($column);
        if($withFlush) {
            $this->_em->flush();
        }
    }
}
