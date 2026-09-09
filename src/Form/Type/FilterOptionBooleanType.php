<?php

namespace Odb\SmartExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Value widget for a boolean-interpreter filter: three radio inputs (Oui / Non /
 * Non filtré) — "no filter" is its own explicit, always-visible choice here rather
 * than an empty field, unlike every other filter widget (see SmartExportFilterType).
 *
 * Its own block name — filter_option_boolean_widget, auto-derived by Symfony from
 * this class name — is what a host application overrides to change how it renders,
 * without needing to know anything about SmartExportFilterType's internals. See
 * that block in tailwind_layout.html.twig: the default choice_widget_expanded this
 * would otherwise inherit never renders each radio's own label in this app's form
 * theming stack (a long-standing, unexplained quirk), so it's overridden here.
 *
 * The caller (SmartExportFilterType) only ever needs to pass 'default_value' — the
 * column's raw admin-configured default, or null — this type is the one that knows
 * how to turn that into a real "1"/"0" selection (falling back to "" / Non filtré
 * for anything else, e.g. a default that was never set or predates this feature).
 */
class FilterOptionBooleanType extends AbstractType
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
            'expanded' => true,
            'placeholder' => false,
            'choices' => [
                'seb.filter.boolean.yes' => '1',
                'seb.filter.boolean.no' => '0',
                'seb.filter.boolean.unfiltered' => '',
            ],
            'default_value' => null,
            'data' => static function (Options $options) {
                return in_array($options['default_value'], ['1', '0'], true) ? $options['default_value'] : '';
            },
        ]);
        $resolver->setAllowedTypes('default_value', ['null', 'string']);
    }
}
