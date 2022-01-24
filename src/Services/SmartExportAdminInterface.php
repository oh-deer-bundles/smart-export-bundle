<?php

namespace Tbl\SmartExportBundle\Services;

use Tbl\SmartExportBundle\Entity\SmartExportEngine;

interface SmartExportAdminInterface
{

    public function removeEngine(string $code):void;
    public function handleFormNewEngine(string $redirectUrl);
    public function handleFormEditEngine(string $code, string $redirectUrl);

    /**
     * @inheritdoc
     */
    public function findAll(): array;

    /**
     * @inheritdoc
     */
    public function findBy(array $criteria, ?array $orderBy = []): array;

    /**
     * @inheritdoc
     */
    public function findOneBy(array $criteria, ?array $orderBy = []): ?SmartExportEngine;

    /**
     * @inheritdoc
     */
    public function findByCode(string $code):SmartExportEngine;
}