<?php

namespace Odb\SmartExportBundle\Repository;

use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SmartExportEngine|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmartExportEngine|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmartExportEngine[]    findAll()
 * @method SmartExportEngine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmartExportEngineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmartExportEngine::class);
    }

    public function save(SmartExportEngine $engine): void
    {
        if(!$engine->getId()) {
            $checkCodeEngine = $this->findOneBy(['code' => $engine->getCode()]);
            if($checkCodeEngine instanceof SmartExportEngine){
                throw new \InvalidArgumentException('A SmartExportEngine already exists with this code '.$engine->getCode());
            }
        }
        $this->_em->persist($engine);
        $this->_em->flush();
    }

    public function remove(SmartExportEngine $engine): void
    {
        $this->_em->remove($engine);
        $this->_em->flush();
    }

}
