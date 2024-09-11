<?php

namespace Odb\SmartExportBundle\Services;

use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface SmartExportAdminInterface
{

    public function removeEngine(string $code):void;
    public function toggleEngine(string $code):void;
    public function handleFormNewEngine(string $redirectUrl): RedirectResponse|FormInterface;
    public function handleFormEditEngine(string $code, string $redirectUrl): RedirectResponse|FormInterface;

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