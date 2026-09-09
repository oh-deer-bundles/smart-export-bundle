<?php

namespace Odb\SmartExportBundle\Enum;

/**
 * Which form widget the demo popup uses for a filterable SmartExportColumn.
 */
enum FilterWidget: string
{
    /**
     * Derived from the column's interpreter: text/number/date field with the usual
     * contains/equal/between-style operators (see SmartExportFilterOperators).
     */
    case Auto = 'auto';

    /**
     * Multi-select populated from the distinct real values of this column currently in
     * the database (e.g. all existing category names) instead of free text, filtered
     * with "in"/"not_in".
     */
    case Select = 'select';

    /**
     * Single-value dropdown populated from the distinct real values of this column
     * (same source as Select, but pick exactly one). No operator selector: the
     * dropdown's own placeholder choice IS "no filter", any other choice means "equal".
     */
    case SingleSelect = 'single_select';
}
