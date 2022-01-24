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
    /**
     * @param array $rawData
     * @param ExportSettings $exportSettings
     * @return BinaryFileResponse|StreamedResponse
     * @throws SpreadSheetException
     * @throws SpreadSheetWriterException
     */
    public static function getResponse(array $rawData, ExportSettings $exportSettings)
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
        switch($exportSettings->getFileFormat()) {
            case SmartExport::FORMAT_CSV:
                $extension = 'csv';
                break;
            case SmartExport::FORMAT_EXCEL_XLSX:
                $extension = 'xlsx';
                break;
            case SmartExport::FORMAT_EXCEL_XLS:
                $extension = 'xls';
                break;
            default:
                $extension = 'txt';
                break;
        }
        $now = new DateTime('now');
        $filename = $now->format('y-m-d-H-i').'_'.$exportSettings->getFormattedCode().'.';
        $filename .= $extension;
        return $filename;
    }

}
