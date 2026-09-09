<?php

namespace Odb\SmartExportBundle\Services;

use Odb\SmartExportBundle\Enum\FilterWidget;

/**
 * Single source of truth for which filter operators are available for a given
 * column (its interpreter, or its filter widget when it's a multi-select), and
 * which of them need a second value (range operators) or multiple values
 * (select operators). Used both by SmartExportFilterType (to build the
 * operator choice list) and SmartExportQuery (to translate a submitted
 * operator into a DQL clause) so the two can never drift apart.
 */
class SmartExportFilterOperators
{
    private const STRING_OPERATORS = ['contains', 'not_contains', 'starts_with', 'ends_with', 'equal'];
    private const NUMERIC_OPERATORS = ['equal', 'gt', 'gte', 'lt', 'lte', 'between'];
    private const DATE_OPERATORS = ['equal', 'before', 'after', 'between'];
    private const BOOLEAN_OPERATORS = ['equal'];
    private const SELECT_OPERATORS = ['in', 'not_in'];

    private const OPERATORS_BY_INTERPRETER = [
        'string' => self::STRING_OPERATORS,
        'string_translated' => self::STRING_OPERATORS,
        'html' => self::STRING_OPERATORS,
        'integer' => self::NUMERIC_OPERATORS,
        'int' => self::NUMERIC_OPERATORS,
        'float' => self::NUMERIC_OPERATORS,
        'euro' => self::NUMERIC_OPERATORS,
        'date' => self::DATE_OPERATORS,
        'boolean' => self::BOOLEAN_OPERATORS,
        'boolean_translated' => self::BOOLEAN_OPERATORS,
    ];

    private const RANGE_OPERATORS = ['between'];

    /**
     * @return array<string> Operator keys available for this interpreter, empty if the
     *                        interpreter isn't filterable at all.
     */
    public static function forInterpreter(?string $interpreter): array
    {
        return self::OPERATORS_BY_INTERPRETER[$interpreter] ?? [];
    }

    /**
     * @return array<string> Operator keys available for this column: the fixed
     *                        "in"/"not_in" pair for a select-widget column (values come
     *                        from a multi-select, not the interpreter), otherwise
     *                        forInterpreter().
     */
    public static function forColumn(?string $interpreter, FilterWidget $filterWidget): array
    {
        if (FilterWidget::Select === $filterWidget) {
            return self::SELECT_OPERATORS;
        }

        return self::forInterpreter($interpreter);
    }

    public static function needsSecondValue(string $operator): bool
    {
        return in_array($operator, self::RANGE_OPERATORS, true);
    }

    public static function needsMultipleValues(string $operator): bool
    {
        return in_array($operator, self::SELECT_OPERATORS, true);
    }
}
