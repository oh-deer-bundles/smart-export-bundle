<?php

namespace Odb\SmartExportBundle\Form;

use Odb\SmartExportBundle\Enum\FilterWidget;
use Odb\SmartExportBundle\Form\Type\FilterOptionBooleanType;
use Odb\SmartExportBundle\Form\Type\FilterOptionDefaultType;
use Odb\SmartExportBundle\Form\Type\FilterOptionMultiSelectType;
use Odb\SmartExportBundle\Form\Type\FilterOptionSingleSelectType;
use Odb\SmartExportBundle\Services\SmartExportFilterOperators;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One filter row for one filterable SmartExportColumn: an operator (its choices
 * depend on the column's interpreter, or are fixed to "in"/"not_in" for a
 * select-widget column — see SmartExportFilterOperators) plus one or two value
 * fields.
 *
 * "No filter" always ends up meaning an empty value at the query layer (see
 * SmartExportQuery::applyFilters(), which skips a filter whose value is
 * empty/null regardless of operator), but how that's exposed in the UI differs:
 * - Auto (text/number/date): the operator selector never offers a placeholder —
 *   it always defaults to a real operator (the first of the interpreter's
 *   list) — because leaving the value field itself blank already reads as
 *   "not filtering on this". The value field itself is NumberType/DateType for
 *   numeric/date interpreters, or FilterOptionDefaultType (a plain text field)
 *   otherwise.
 * - Boolean/boolean_translated: no operator selector at all (operator is fixed
 *   to "equal"); "value" is FilterOptionBooleanType, a three-way Oui/Non/Non
 *   filtré radio choice, so "not filtering" is its own explicit, visible option
 *   rather than an absence of selection.
 * - Select widget: "value" is FilterOptionMultiSelectType, a multi-select of the
 *   column's actual distinct database values (passed in via the "choices" option
 *   — this type has no DB access of its own), filtered with "in"/"not_in". The
 *   operator selector KEEPS its placeholder, defaulting to it, because clearing
 *   every checked value by hand is more friction than picking "no filter" from
 *   the dropdown.
 * - SingleSelect widget: "value" is FilterOptionSingleSelectType, a plain
 *   dropdown over the same distinct-values source as Select, but picking at
 *   most one. No operator selector either — the dropdown's own placeholder
 *   choice IS "no filter", any real choice means "equal".
 *
 * Each of the FilterOption*Type value widgets has its own block name (auto-derived
 * by Symfony from its class name, e.g. FilterOptionBooleanType ->
 * filter_option_boolean_widget) that a host application can override in its own
 * form theme to change how it renders, without needing to know anything about this
 * class's internals.
 *
 * "value2" is only meaningful for range operators like "between" — the demo
 * popup's filter_controller.js shows/hides it client-side.
 */
class SmartExportFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $interpreter = $options['interpreter'];
        $isBoolean = in_array($interpreter, ['boolean', 'boolean_translated'], true);
        $isMultiSelect = FilterWidget::Select === $options['filter_widget'];
        $isSingleSelect = FilterWidget::SingleSelect === $options['filter_widget'];

        if ($isBoolean || $isSingleSelect) {
            $builder->add('operator', HiddenType::class, ['data' => 'equal']);
        } else {
            $operators = SmartExportFilterOperators::forColumn($interpreter, $options['filter_widget']);
            $operatorOptions = [
                'choices' => array_combine(
                    array_map(fn (string $operator) => 'seb.filter.operator.'.$operator, $operators),
                    $operators
                ),
                'required' => false,
                'label' => false,
            ];

            if ($isMultiSelect) {
                // Kept as an explicit, always-available "reset" choice — see class docblock.
                $operatorOptions['placeholder'] = 'seb.filter.operator.none';
                if (null !== $options['default_value'] && [] !== $operators) {
                    $operatorOptions['data'] = $operators[0];
                }
            } else {
                // A non-required ChoiceType auto-adds a blank-labeled placeholder option
                // unless explicitly disabled — exactly the empty entry this widget must not
                // have (see class docblock: "no filter" is an empty value here, not a choice).
                $operatorOptions['placeholder'] = false;
                $operatorOptions['data'] = $operators[0] ?? null;
            }

            $builder->add('operator', ChoiceType::class, $operatorOptions);
        }

        [$valueType, $valueOptions] = match (true) {
            $isBoolean => [FilterOptionBooleanType::class, ['default_value' => $options['default_value']]],
            $isMultiSelect => [FilterOptionMultiSelectType::class, ['choices' => $options['choices'], 'default_value' => $options['default_value']]],
            $isSingleSelect => [FilterOptionSingleSelectType::class, ['choices' => $options['choices'], 'default_value' => $options['default_value']]],
            default => [$this->valueType($interpreter), $this->valueOptions($interpreter, $options['default_value'])],
        };

        $builder
            ->add('value', $valueType, $valueOptions)
            ->add('value2', $this->valueType($interpreter), $this->valueOptions($interpreter, null))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'interpreter' => null,
            'default_value' => null,
            'filter_widget' => FilterWidget::Auto,
            'choices' => [],
            'translation_domain' => 'smart_export_bundle_forms',
        ]);
    }

    private function valueType(?string $interpreter): string
    {
        return match ($interpreter) {
            'integer', 'int', 'float', 'euro' => NumberType::class,
            'date' => DateType::class,
            default => FilterOptionDefaultType::class,
        };
    }

    private function valueOptions(?string $interpreter, ?string $defaultValue): array
    {
        // NumberType/DateType are used directly (no FilterOption*Type wrapper, since neither
        // needs bespoke markup — see class docblock), so 'required'/'label' are set here
        // rather than via a configureOptions() default the way the other widgets get them.
        $options = [
            'required' => false,
            'label' => false,
        ];

        if (null !== $defaultValue) {
            $options['data'] = $defaultValue;
        }

        return match ($interpreter) {
            'float', 'euro' => $options + ['scale' => 2, 'html5' => true],
            'integer', 'int' => $options + ['html5' => true],
            'date' => $options + ['widget' => 'single_text', 'input' => 'string', 'format' => 'yyyy-MM-dd', 'html5' => true],
            default => $options,
        };
    }
}
