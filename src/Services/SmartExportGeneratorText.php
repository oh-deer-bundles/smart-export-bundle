<?php
namespace Odb\SmartExportBundle\Services;


use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\String\Exception\RuntimeException;
use Odb\SmartExportBundle\Model\ExportSettings;

class SmartExportGeneratorText
{
    /**
     * Accept :
     *      SmartExport::SEPARATOR_SEMICOLON
     *      SmartExport::SEPARATOR_COMMA
     *      SmartExport::SEPARATOR_TABULATION
     *      SmartExport::SEPARATOR_PIPE
     * @param string $code
     * @return string could be character 35 (#)
     */
    public static function getSeparator(string $code = SmartExport::SEPARATOR_SEMICOLON) :string
    {
        switch ($code){
            case SmartExport::SEPARATOR_SEMICOLON:
                return chr(59);
            case SmartExport::SEPARATOR_COMMA:
                return chr(44);
            case SmartExport::SEPARATOR_TABULATION:
                return chr(9);
            case SmartExport::SEPARATOR_PIPE:
                return chr(179);
        }

        return chr(35);
    }

    /**
     * @param int|float|string $value
     * @param string $charset default is SmartExport::CHARSET_UTF8 accept all from
     * @param string|null $locale
     * @return false|int|float|string
     */
    public static function convert($value, string $charset = SmartExport::CHARSET_UTF8, string $locale = null)
    {
        if (is_string($value)) {
            if ($locale) {
                setlocale(LC_CTYPE, $locale);
            }
            return iconv(SmartExport::CHARSET_INTERNAL, $charset, $value);
        }

        return $value;
    }


    /**
     * @param array $data
     * @param ExportSettings $exportSettings
     * @return BinaryFileResponse
     */
    public static function getTextBinaryResponse(
        array $data,
        ExportSettings $exportSettings
    ): BinaryFileResponse
    {
        try{
            $charSeparator = self::getSeparator($exportSettings->getSeparator());
            $temp_file = tempnam(sys_get_temp_dir(), 'SmartExport_');
            $fp = fopen($temp_file, 'wb');
//            $fp = fopen('php://output', 'w');
            foreach ($data as $row){
                $max = count($row);
                $loopIndex = 0;
                foreach ($row as $value){
                    ++$loopIndex;

                    if( $exportSettings->getCharset() !== SmartExport::CHARSET_INTERNAL) {
                        $value = self::convert($value, $exportSettings->getCharset(), $exportSettings->getLocale());
                    }

                    $value .= ($loopIndex === $max)
                        ? chr(13).chr(10)
                        : $charSeparator;

                    fwrite($fp,$value);
                }
            }
            fclose($fp);
            if($exportSettings->getFileFormat() === SmartExport::FORMAT_TXT) {
                $mime = 'text/plain';
            } elseif ($exportSettings->getFileFormat() === SmartExport::FORMAT_CSV) {
                $mime = 'text/csv';
            } else {
                $mime = mime_content_type($temp_file);
            }
            $filesize = filesize($temp_file);

            $response = new BinaryFileResponse($temp_file);
            $response->headers->set('Content-Type', $mime.'; charset=utf-8');
            $response->headers->set('Content-Length', $filesize);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $exportSettings->getFilename()
            );
            $response->deleteFileAfterSend(true);
            return $response;

        } catch (Exception $e){
            throw new RuntimeException($e->getMessage());
        }
    }
}