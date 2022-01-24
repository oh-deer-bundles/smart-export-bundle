<?php

namespace Tbl\SmartExportBundle\Model;

use Tbl\SmartExportBundle\Entity\SmartExportEngine;

class ExportSettings
{
    
    /**
     * @var SmartExportEngine
     */
    private $engine;
    
    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var string
     */
    private $code;

    /**
     * @var string|null
     */
    private $formattedCode;

    /**
     * @var string
     */
    private $fileFormat;

    /**
     * @var string|null
     */
    private $fileExtension;

    /**
     * @var string|null
     */
    private $fileMime;

    /**
     * @var string
     */
    private $charset;

    /**
     * @var string
     */
    private $separator;
    
    /**
     * @var string|null
     */
    private $locale;

    /**
     * @var string|null
     */
    private $filename;

    
    /**
     * @var bool
     */
    private $isValid = false;

    /**
     * @var \Tbl\SmartExportBundle\Model\ExcelStyle|null
     */
    private $excelStyle;
    
    /**
     * @return SmartExportEngine
     */
    public function getEngine(): SmartExportEngine
    {
        return $this->engine;
    }

    /**
     * @param SmartExportEngine $engine
     */
    public function setEngine(SmartExportEngine $engine): void
    {
        $this->engine = $engine;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ExportSettings
     */
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFormattedCode(): ?string
    {
        return $this->formattedCode;
    }

    /**
     * @param string|null $formattedCode
     * @return ExportSettings
     */
    public function setFormattedCode(?string $formattedCode): self
    {
        $this->formattedCode = $formattedCode;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    /**
     * @param string $fileFormat
     * @return ExportSettings
     */
    public function setFileFormat(string $fileFormat): self
    {
        $this->fileFormat = $fileFormat;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    /**
     * @param string|null $fileExtension
     * @return ExportSettings
     */
    public function setFileExtension(?string $fileExtension): self
    {
        $this->fileExtension = $fileExtension;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileMime(): ?string
    {
        return $this->fileMime;
    }

    /**
     * @param string|null $fileMime
     * @return ExportSettings
     */
    public function setFileMime(?string $fileMime): self
    {
        $this->fileMime = $fileMime;
        return $this;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @param string $charset
     * @return ExportSettings
     */
    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @return string
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * @param string $separator
     * @return ExportSettings
     */
    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     * @return ExportSettings
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string|null $filename
     * @return ExportSettings
     */
    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @param bool $isValid
     * @return ExportSettings
     */
    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;
        return $this;
    }

    /**
     * @return ExcelStyle|null
     */
    public function getExcelStyle(): ?ExcelStyle
    {
        return $this->excelStyle;
    }

    /**
     * @param ExcelStyle $excelStyle
     */
    public function setExcelStyle(ExcelStyle $excelStyle): void
    {
        $this->excelStyle = $excelStyle;
    }
}