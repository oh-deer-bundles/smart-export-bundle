<?php

namespace Odb\SmartExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Value widget for a FilterWidget::Auto column whose interpreter isn't numeric or
 * date (integer/float/euro use NumberType, date uses DateType directly — see
 * SmartExportFilterType::valueType() — since neither needs bespoke markup beyond
 * what's already styled generically via form_widget_simple): a plain text field.
 * "No filter" is simply an empty value here, not a choice of its own — leaving the
 * field blank already reads as "not filtering on this". Its block name —
 * filter_option_default_widget, auto-derived from this class name — is what a
 * host application overrides to change how it renders; by default it inherits
 * form_widget_simple (already styled in tailwind_layout.html.twig).
 *
 * The caller only needs to pass 'default_value' — the column's raw
 * admin-configured default, or null.
 */
class FilterOptionDefaultType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'label' => false,
            'default_value' => null,
            'data' => static fn (Options $options) => $options['default_value'],
        ]);
        $resolver->setAllowedTypes('default_value', ['null', 'string']);
    }
}
