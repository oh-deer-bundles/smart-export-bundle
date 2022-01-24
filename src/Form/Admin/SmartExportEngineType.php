<?php
namespace Tbl\SmartExportBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tbl\SmartExportBundle\Entity\SmartExportEngine;
use Tbl\SmartExportBundle\Services\SmartExportQueryInterface;

class SmartExportEngineType extends AbstractType
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
            ->add('code', TextType::class,[
                    'label' => 'seb.code.label',
                    'help' => 'seb.code.helper'
            ])

            ->add('description', TextType::class,[
                    'label' => 'seb.description.label',
                    'help' => 'seb.description.helper',
                    'required' => false
            ])

            ->add('className', ChoiceType::class, [
                'label' => 'seb.class_name.label',
                'choices'  => $this->entity_choices,
                'required' => true,
                'placeholder' => 'seb.class_name.placeholder'
            ])
        ; 
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SmartExportEngine::class,
            'translation_domain' => 'smart_export_bundle_forms',
        ]);
    }
}