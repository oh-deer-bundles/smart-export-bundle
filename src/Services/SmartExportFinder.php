<?php

namespace Odb\SmartExportBundle\Services;

use InvalidArgumentException;
use Odb\SmartExportBundle\Entity\SmartExportEngine;

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
     * @param string $uuid
     * @return SmartExportEngine
     */
    public function findByUuid(string $uuid):SmartExportEngine
    {
        $engine = $this->smartExportEngineRepository->findOneBy(['uuid'=> $uuid]);
        if(!$engine instanceof SmartExportEngine) {
            throw new InvalidArgumentException('No SmartExportEngine found with this uuid : '.$uuid);
        }
        return $engine;
    }
}