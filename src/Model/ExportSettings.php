<?php

namespace Odb\SmartExportBundle\Model;

use Odb\SmartExportBundle\Entity\SmartExportEngine;

class ExportSettings
{

    private SmartExportEngine $engine;

    private array $columns = [];

    private string $code;

    private ?string $formattedCode = null;

    private string $fileFormat;

    private ?string $fileExtension = null;

    private ?string $fileMime = null;

    private string $charset;

    private string $separator;

    private ?string $locale = null;

    private ?string $filename = null;

    private bool $isValid = false;

    private ?ExcelStyle $excelStyle = null;

    public function getEngine(): SmartExportEngine
    {
        return $this->engine;
    }

    public function setEngine(SmartExportEngine $engine): void
    {
        $this->engine = $engine;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getFormattedCode(): ?string
    {
        return $this->formattedCode;
    }

    public function setFormattedCode(?string $formattedCode): static
    {
        $this->formattedCode = $formattedCode;
        return $this;
    }

    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    public function setFileFormat(string $fileFormat): static
    {
        $this->fileFormat = $fileFormat;
        return $this;
    }

    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(?string $fileExtension): static
    {
        $this->fileExtension = $fileExtension;
        return $this;
    }

    public function getFileMime(): ?string
    {
        return $this->fileMime;
    }

    public function setFileMime(?string $fileMime): static
    {
        $this->fileMime = $fileMime;
        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }


    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }


    public function getIsValid(): bool
    {
        return $this->isValid;
    }


    public function setIsValid(bool $isValid): static
    {
        $this->isValid = $isValid;
        return $this;
    }


    public function getExcelStyle(): ?ExcelStyle
    {
        return $this->excelStyle;
    }

    public function setExcelStyle(ExcelStyle $excelStyle): static
    {
        $this->excelStyle = $excelStyle;
        return $this;
    }
}