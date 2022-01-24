<?php


namespace Odb\SmartExportBundle\Services;

use DateTime;
use DatetimeInterface;

class SmartExportInterpreter
{
    /**
     * @param string $interpreter
     * can be 'integer', 'int', 'float', 'bool', 'boolean', 'string', 'date_'+format eg date_dmY, 'boolean_translated','money'
     * @param array $rules
     * allow_null (bool) if true the return can be null
     * In case $interpreter = boolean_translated :
     *      false_values (array) of values must be translated to false
     *      true_values (array) of values must be translated to true
     * In case of string :
     *      prefix (mixed) : eg. prefix = "ali " and $raw_value = "baba" => "ali baba"
     *      suffix (mixed) : eg. suffix = " 66" and $raw_value = "route" => "route 66"
     *      append (bool) if true initial_value will be preserved and raw_value will be insert at end
     *      prepend (bool) if true initial_value will be preserved and raw_value will be insert at begin
     *      separator (string) in case of prepend or append you can specify a separator like "<br>" or "\n"
     * In case of money
     *      currency (string) allowed value 'euro', 'dollard' (US) or symbol
     *      decimals (int) number of decimals default is 2
     *      thousand_separator (string) default is ' ' (white space)
     *      decimal_separator (string) default is ',' (comma)
     * @param null $raw_value
     * @param null $initial_value
     * @param null|string $file_format default will be SmartExport::FORMAT_TXT
     * @return float|int|string|null
     */
    public static function translate(string $interpreter, array $rules, $raw_value = null, $initial_value = null, string $file_format = null)
    {
        if(!$file_format) {
            $file_format = SmartExport::FORMAT_TXT;
        }

        if (!$raw_value
            && array_key_exists('allow_null', $rules)
            && $rules['allow_null']
            && !($rules['preserve_date'] || $rules['preserve_time'] || $rules['append'])
        ) {
            return null;
        }

        switch ($interpreter) {
            case 'integer':
            case 'int':
                $raw_value = trim($raw_value);
                $value = self::formatInteger($raw_value);
                break;
            case 'float':
                $raw_value = trim($raw_value);
                $value = self::formatFloat($raw_value);
                break;
            case 'boolean':
            case 'bool':
                $raw_value = trim($raw_value);
                $value = self::formatBoolean($raw_value);
                break;
            case 'string':
            case 'string_translated':
                $raw_value = trim($raw_value);
                $value = self::formatString($rules, $raw_value, $initial_value);
                break;
            case 'date':
                $value = self::formatDateTime($rules, $raw_value, $file_format);
                break;
            case 'html':
                $value = self::formatHtml($raw_value);
                break;
            case 'boolean_translated':
                $raw_value = trim($raw_value);
                $value = self::formatBooleanTranslated($rules, $raw_value);
                break;
            case 'euro':
                $raw_value = trim($raw_value);
                $value = self::formatMoney($rules, $raw_value);
                break;
            default:
                $value = $raw_value;
        }

        return $value;
    }

    /**
     * @param null $raw_value
     * @return int
     */
    public static function formatInteger($raw_value = null) :int
    {
        $raw_value = str_replace(',', '.', $raw_value);
        $raw_value = preg_replace("/[^\d.-]/", '', $raw_value);
        return (int)$raw_value;
    }

    /**
     * @param null $raw_value
     * @return float
     */
    public static function formatFloat($raw_value = null) :float
    {
        $raw_value = str_replace(',', '.', $raw_value);
        $raw_value = preg_replace("/[^\d.-]/", '', $raw_value);
        return (float)$raw_value;
    }

    /**
     * @param null $raw_value
     * @return int
     */
    public static function formatBoolean($raw_value = null) :int
    {
        return $raw_value ? 1 : 0;
    }

    /**
     * @param array $rules
     * prefix (mixed) : eg. prefix = "ali " and $raw_value = "baba" => "ali baba"
     * suffix (mixed) : eg. suffix = " 66" and $raw_value = "route" => "route 66"
     * append (bool) if true initial_value will be preserved and raw_value will be insert at end
     * prepend (bool) if true initial_value will be preserved and raw_value will be insert at begin
     * separator (string) in case of prepend or append you can specify a separator like "<br>" or "\n"
     * @param null $raw_value
     * @param null $initial_value
     * @return string
     */
    public static function formatString(array $rules, $raw_value = null, $initial_value= null) : string
    {
        if (array_key_exists('prefix', $rules)) {
            $raw_value = $rules['prefix'] . $raw_value;
        }

        if (array_key_exists('suffix', $rules)) {
            $raw_value .= $rules['suffix'];
        }

        if (
            (array_key_exists('append', $rules) && $rules['append'])
            || (array_key_exists('prepend', $rules) && $rules['prepend'])
        ) {

            $separator = (array_key_exists('separator', $rules) && $initial_value && $raw_value) ? $rules['separator'] : '';
            $initial_value = $initial_value ?: '';
            $value = array_key_exists('append', $rules) ?
                $initial_value . $separator . $raw_value :
                $raw_value . $separator . $initial_value;
        } else {
            $value = $raw_value.'';
        }

        return $value;
    }

    /**
     * @param array $rules
     * preserve_time (bool) if true the time will be the same as $initial_value only in case the format skip h H i s
     * preserve_date (bool) if true the date will be the same as $initial_value only in case the format skip d m y Y
     * date_format (string) allowed values : d m y Y h H i s see https://www.php.net/manual/en/datetime.format.php
     *                      eg. mdY or mdyHis in order you want
     * @param DatetimeInterface|null $raw_value
     * @param string|null $file_format
     * if null the return will be 1st january 1970
     * @return string|int|null
     */
    public static function formatDateTime(array $rules, DatetimeInterface  $raw_value = null, string $file_format = null)
    {
//        if(!array_key_exists('date_format', $rules)){
//            throw new \Exception('No date_format set');
//        }
        $value = '';
        if($raw_value instanceof DateTimeInterface){
            if($file_format === SmartExport::FORMAT_EXCEL_XLSX || $file_format === SmartExport::FORMAT_EXCEL_XLS) {
                $origine = new DateTime('1900-01-01 00:00:00');
                $value = (int)$origine->diff($raw_value)->format('%a')+2;
            } else  {
                $format = array_key_exists('date_format', $rules) ? $rules['date_format'] : 'd/m/Y';
                $value = $raw_value->format($format);
            }

        } elseif (array_key_exists('allow_null', $rules) && $rules['allow_null']){
            $value = null;
        }

        return $value;

    }

    /**
     * Is conditional bool eg. you need true when value is equal 'bar'
     * @param array $rules
     * allow_null (bool) if true the return can be null
     * false_values (array) of values must be translated to false
     * true_values (array) of values must be translated to true
     * @param null $raw_value
     * @return string|null
     */
    public static function formatBooleanTranslated(array $rules, $raw_value = null) : ?string
    {
        $value = (array_key_exists('allow_null', $rules) && $rules['allow_null']) ?
            null
            : self::formatBoolean($raw_value);

        if (0 === $value && array_key_exists('false_value', $rules)){
            $value = $rules['false_value'];
        }

        if (1 === $value && array_key_exists('true_value', $rules) ) {
            $value = $rules['true_value'];
        }

        return $value;
    }

    public static function formatHtml($raw_value = null): string
    {
       return  $raw_value ? trim(strip_tags($raw_value)) : '';
    }

    /**
     * @param array $rules
     *      currency (string) allowed value 'euro', 'dollard' (US) or symbol
     *      decimals (int) number of decimals default is 2
     *      thousand_separator (string) default is ' ' (white space)
     *      decimal_separator (string) default is ',' (comma)
     * @param null $raw_value
     * @return string|null
     */
    public static function formatMoney(array $rules, $raw_value = null) : ?string
    {
        $value = null;
        if($raw_value) {
            $symbol = $rules['currency'] ?? '';
            $way = 'append';
            $decimals = $rules['decimals'] ?? 2;
            $thousandsSeparator = $rules['thousand_separator'] ?? ' ';
            $decimalSeparator = $rules['decimal_separator'] ?? ',';
            if (array_key_exists('currency', $rules)) {
                switch ($rules['currency']) {
                    case 'euro':
                        $symbol = '€';
                        break;
                    case 'dollard':
                        $symbol = '$';
                        $way = 'prepend';
                        break;
                }
            }
            $value = number_format(self::formatFloat($raw_value), (int)$decimals, $thousandsSeparator, $decimalSeparator);
            $value = $way === 'append'
                ? $value.' '.$symbol
                : $symbol.' '.$value;
        }
       

        return $value;
    }

}