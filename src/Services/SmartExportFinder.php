<?php

namespace Tbl\SmartExportBundle\Services;

use Tbl\SmartExportBundle\Entity\SmartExportEngine;

trait SmartExportFinder
{
    /**
     * @return SmartExportEngine[]
     */
    public function findAll(): array
    {
        return $this->smartExportEngineRepository->findAll();
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return SmartExportEngine[]
     */
    public function findBy(array $criteria, ?array $orderBy = []): array
    {
        return $this->smartExportEngineRepository->findBy($criteria, $orderBy);
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return SmartExportEngine|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = []): ?SmartExportEngine
    {
        return $this->smartExportEngineRepository->findOneBy($criteria, $orderBy);
    }

    /**
     * @param string $code
     * @return SmartExportEngine
     */
    public function findByCode(string $code):SmartExportEngine
    {
        $engine = $this->smartExportEngineRepository->findOneBy(['code'=> $code]);
        if(!$engine instanceof SmartExportEngine) {
            throw new InvalidArgumentException('No SmartExportEngine found with this code : '.$code);
        }
        return $engine;
    }
}