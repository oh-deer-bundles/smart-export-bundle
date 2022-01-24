<?php

namespace Tbl\SmartExportBundle\Services;

use PhpOffice\PhpSpreadsheet\Exception as SpreadSheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as SpreadSheetWriterException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tbl\SmartExportBundle\Entity\SmartExportEngine;
use Tbl\SmartExportBundle\Model\ExportSettings;

interface SmartExportInterface
{
    /**
     * @param string $code
     * @param string|null $filename if $filename is null the default will be YYYY-MM-DD-HH-II_{$code}.extension
     * @return mixed
     */
    public function add(string $code, ?string $filename = null);

    /**
     * @param string $code
     * @param array $formOptions optional you can overwrite the form options except code_export
     * @return FormInterface
     */
    public function createForm(string $code, array $formOptions = []): FormInterface;

    /**
     * true if form is valid
     * @return bool
     */
    public function handleFrom(): bool;

    /**
     * @param null|string $filename
     * @return BinaryFileResponse|StreamedResponse|null
     * @throws SpreadSheetException
     * @throws SpreadSheetWriterException
     */
    public function getResponse(?string $filename = null);
    
    /**
     * @return null|FormInterface
     */
    public function getForm(): ?FormInterface;

    /**
     * @return null|array
     */
    public function getRawData(): ?array;

    /**
     * Alias of getRawData()
     * @return null|array
     */
    public function getData(): ?array;

    /**
     * @return null|ExportSettings
     */
    public function getSettings() :? ExportSettings;

    /**
     * @return null|string
     */
    public function getCode(): ?string;

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