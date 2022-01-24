<?php


namespace Odb\SmartExportBundle\Services;


use PhpOffice\PhpSpreadsheet\Exception as SpreadSheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Exception as SpreadSheetWriterException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Odb\SmartExportBundle\Model\ExportSettings;
use Odb\SmartExportBundle\Model\ExcelStyle;

class SmartExportGeneratorExcel
{
    /**
     * @param array $data
     * @param ExportSettings $exportSettings
     * @return StreamedResponse
     * @throws SpreadSheetException
     * @throws SpreadSheetWriterException
     */
    public static function getExcelStreamResponse( array $data, ExportSettings $exportSettings): StreamedResponse
    {

        $excelStyle = $exportSettings->getExcelStyle() ?? new ExcelStyle();
        $excelObject = new Spreadsheet();
        $excelObject->getDefaultStyle()->getFont()->setName($excelStyle->getFontFamily())->setSize($excelStyle->getFontSizeMedium());

        /** Create Active sheet (tab) */
        $excelObject->removeSheetByIndex(0);
        $excelSheet = $excelObject->createSheet(0);

        $firstRow = array_shift($data);
        $maxColLetter = ExcelStyle::getColLetter(count($firstRow));
        $rowNumber = 1;
        $columnNumber = 1;
        $columnLetters = [];
        /** first row (header) */
        foreach ($firstRow as $columnKey => $columnTitle) {
            $colLetter = ExcelStyle::getColLetter($columnNumber);
            /** for next loop */
            $columnLetters[$columnKey] = $colLetter;
            $excelSheet->getColumnDimension($colLetter)->setAutoSize(true);
            $excelSheet->setCellValue($colLetter.$rowNumber, $columnTitle);
            ++$columnNumber;
        }

        foreach ($data as $row) {
            ++$rowNumber;
            foreach ($row as $columnKey => $columnValue) {
                $colLetter = $columnLetters[$columnKey];
                $excelSheet->setCellValue($colLetter.$rowNumber, $columnValue);
            }
        }

        /** Apply some style and format */
        foreach ($exportSettings->getColumns() as $columnKey => $columnDefinition) {
            if( count($columnDefinition) > 0) {
                $colLetter = $columnLetters[$columnKey];
                $interpreter = $columnDefinition[0]->getInterpreter() ?? 'string';
                switch ($interpreter) {
                    case 'date' :
                        $excelSheet->getStyle($colLetter.'2:'.$colLetter.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                        break;
                    case 'money' :
                        // TODO manage currency
                        $excelSheet->getStyle($colLetter.'2:'.$colLetter.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
                        break;
                }
            }
        }

        $excelSheet->getStyle('A1:'.$maxColLetter.'1')->applyFromArray($excelStyle->getHeadStyle());
        $excelSheet->getStyle('A2:'.$maxColLetter.$rowNumber)->applyFromArray($excelStyle->getMainStyle());
        $excelSheet->setAutoFilter('A1:'.$maxColLetter.$rowNumber);

        if($exportSettings->getFileFormat() === SmartExport::FORMAT_EXCEL_XLS) {
            $writer = IOFactory::createWriter($excelObject, 'Xls');
            $mime = 'application/vnd.ms-excel';
        } else {
            $writer = IOFactory::createWriter($excelObject, 'Xlsx');
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }


        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            "200",
            [
                'Content-Type' => $mime.'; charset=utf-8',
                'Content-Disposition' => 'attachment;filename=' . $exportSettings->getFilename(),
                'Pragma' => 'public',
                'Cache-Control' => 'maxage=1'
            ]
        );
    }
}