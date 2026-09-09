<?php

namespace Odb\SmartExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Value widget for a FilterWidget::Select column: a multi-select populated from the
 * column's actual distinct database values (the "choices" option — the caller,
 * SmartExportFilterType, is the one with DB access to resolve them), filtered with
 * "in"/"not_in". Its block name — filter_option_multi_select_widget, auto-derived
 * from this class name — is what a host application overrides to change how it
 * renders; by default it inherits choice_widget_collapsed (already styled in
 * tailwind_layout.html.twig), since a native <select multiple> needs no bespoke
 * markup the way FilterOptionBooleanType's radios do.
 *
 * The caller only needs to pass 'choices' (a flat array of distinct values — this
 * type is the one that turns it into a real label => value choice list) and
 * 'default_value' (the column's raw admin-configured default, or null — a
 * comma-separated list of values, since filterDefaultValue is a single string
 * column shared with every other widget kind).
 */
class FilterOptionMultiSelectType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'label' => false,
            'multiple' => true,
            'choices' => [],
            'default_value' => null,
            'data' => static function (Options $options) {
                if (null === $options['default_value'] || '' === $options['default_value']) {
                    return [];
                }

                $defaults = array_intersect(
                    array_map('trim', explode(',', $options['default_value'])),
                    $options['choices']
                );

                return array_values($defaults);
            },
        ]);
        $resolver->setAllowedTypes('choices', 'array');
        $resolver->setAllowedTypes('default_value', ['null', 'string']);
        $resolver->setNormalizer('choices', static fn (Options $options, array $choices) => array_combine($choices, $choices));
    }
}
