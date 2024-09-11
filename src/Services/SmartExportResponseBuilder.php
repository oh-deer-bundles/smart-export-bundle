<?php

namespace Odb\SmartExportBundle\Services;

use DateTime;
use PhpOffice\PhpSpreadsheet\Exception as SpreadSheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as SpreadSheetWriterException;
use Odb\SmartExportBundle\Model\ExportSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmartExportResponseBuilder
{

    public static function getResponse(array $rawData, ExportSettings $exportSettings): BinaryFileResponse|StreamedResponse
    {
        if (!$exportSettings->getFilename()) {
            $exportSettings->setFilename(self::generateDefaultFilename($exportSettings));
        }

        return $exportSettings->getFileFormat() === SmartExport::FORMAT_EXCEL_XLSX || $exportSettings->getFileFormat() === SmartExport::FORMAT_EXCEL_XLS
            ? SmartExportGeneratorExcel::getExcelStreamResponse($rawData, $exportSettings)
            : SmartExportGeneratorText::getTextBinaryResponse($rawData, $exportSettings);
    }

    /**
     * @param ExportSettings $exportSettings
     * @return string
     */
    private static function generateDefaultFilename(ExportSettings $exportSettings) :string
    {
        $extension = match ($exportSettings->getFileFormat()) {
            SmartExport::FORMAT_CSV => 'csv',
            SmartExport::FORMAT_EXCEL_XLSX => 'xlsx',
            SmartExport::FORMAT_EXCEL_XLS => 'xls',
            default => 'txt',
        };
        $now = new DateTime('now');
        $filename = $now->format('y-m-d-H-i').'_'.$exportSettings->getFormattedCode().'.';
        $filename .= $extension;
        return $filename;
    }

}
