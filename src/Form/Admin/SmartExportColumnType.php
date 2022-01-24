<?php
namespace Odb\SmartExportBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Services\SmartExportQueryInterface;

class SmartExportColumnType extends AbstractType
{
    protected $entity_choices = [];

    /**
     * SmartExportEngineType constructor.
     * @param SmartExportQueryInterface $queryService
     */
    public function __construct(SmartExportQueryInterface $queryService)
    {
        $this->entity_choices = $queryService->getAdminSelectClasses();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('choiceLabel', TextType::class,[
                'label' => 'seb.choice_label.label',
                'help' => 'seb.choice_label.helper'
            ])
            ->add('choicePosition', HiddenType::class,[
                'attr' => ['class' => 'input_position'],
                'required' => false
            ])
            ->add('headerLabel', TextType::class,[
                    'label' => 'seb.header_label.label',
                    'help' => 'seb.header_label.helper',
            ])
            ->add('columnGroupIndex', TextType::class,[
                'label' => 'seb.column_group_index.label',
                'help' => 'seb.column_group_index.helper',
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
        
        ; 
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SmartExportColumn::class,
            'translation_domain' => 'smart_export_bundle_forms',
        ]);
    }
}