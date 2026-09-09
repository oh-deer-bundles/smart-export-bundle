<?php
namespace Odb\SmartExportBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Enum\FilterWidget;
use Odb\SmartExportBundle\Services\SmartExportQueryInterface;

class SmartExportColumnType extends AbstractType
{
    protected array $entityChoices = [];


    public function __construct(SmartExportQueryInterface $queryService)
    {
        $this->entityChoices = $queryService->getAdminSelectClasses();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class,[
                'label' => 'seb.label.label',
                'help' => 'seb.label.helper'
            ])
            ->add('choicePosition', HiddenType::class,[
                'attr' => ['class' => 'input_position'],
                'required' => false
            ])

            ->add('cellGroupIndex', TextType::class,[
                'label' => 'seb.cell_group_index.label',
                'help' => 'seb.cell_group_index.helper',
                 'required' => false
            ])

            ->add('interpreter', ChoiceType::class,[
                'label' => 'seb.interpreter.label',
                'help' => 'seb.interpreter.helper',
                'choices' => [
                    'string' => 'string',
                    'integer' => 'integer',
                    'float' => 'float',
                    'boolean' => 'boolean',
                    'date' => 'date',
                    'html' => 'html',
                    'euro' => 'euro'
                ]
            ])

            ->add('classProperty', TextType::class, [
                'label' => 'seb.class_property.label',
                'help' => 'seb.class_property.helper'
            ])

            ->add('columnDisplay', CheckboxType::class, [
                'label' => 'seb.column_display.label',
                'help' => 'seb.column_display.helper',
                'required' => false,
            ])

            ->add('selectedByDefault', CheckboxType::class, [
                'label' => 'seb.selected_by_default.label',
                'help' => 'seb.selected_by_default.helper',
                'required' => false,
            ])

            ->add('filterable', CheckboxType::class, [
                'label' => 'seb.filterable.label',
                'help' => 'seb.filterable.helper',
                'required' => false,
            ])

            ->add('filterDisplay', CheckboxType::class, [
                'label' => 'seb.filter_display.label',
                'help' => 'seb.filter_display.helper',
                'required' => false,
            ])

            ->add('filterDefaultValue', TextType::class, [
                'label' => 'seb.filter_default_value.label',
                'help' => 'seb.filter_default_value.helper',
                'required' => false,
            ])

            ->add('filterWidget', EnumType::class, [
                'label' => 'seb.filter_widget.label',
                'help' => 'seb.filter_widget.helper',
                'class' => FilterWidget::class,
                'choice_label' => fn (FilterWidget $widget) => 'seb.filter_widget.'.$widget->value,
                'required' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SmartExportColumn::class,
            'translation_domain' => 'smart_export_bundle_forms',
        ]);
    }
}
