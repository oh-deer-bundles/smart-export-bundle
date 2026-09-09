<?php

namespace Odb\SmartExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Value widget for a FilterWidget::SingleSelect column: a plain dropdown populated
 * from the column's actual distinct database values (the "choices" option — the
 * caller, SmartExportFilterType, is the one with DB access to resolve them). No
 * operator selector: the dropdown's own placeholder choice IS "no filter", any
 * other choice means "equal". Its block name —
 * filter_option_single_select_widget, auto-derived from this class name — is what
 * a host application overrides to change how it renders; by default it inherits
 * choice_widget_collapsed (already styled in tailwind_layout.html.twig).
 *
 * The caller only needs to pass 'choices' (a flat array of distinct values — this
 * type is the one that turns it into a real label => value choice list) and
 * 'default_value' (the column's raw admin-configured default, or null).
 */
class FilterOptionSingleSelectType extends AbstractType
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
            'placeholder' => 'seb.filter.operator.none',
            'choices' => [],
            'default_value' => null,
            'data' => static function (Options $options) {
                return in_array($options['default_value'], $options['choices'], true) ? $options['default_value'] : null;
            },
        ]);
        $resolver->setAllowedTypes('choices', 'array');
        $resolver->setAllowedTypes('default_value', ['null', 'string']);
        $resolver->setNormalizer('choices', static fn (Options $options, array $choices) => array_combine($choices, $choices));
    }
}
