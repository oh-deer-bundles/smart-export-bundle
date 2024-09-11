<?php

namespace Odb\SmartExportBundle\Form\Admin;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;


class EditSmartExportEngineType extends SmartExportEngineType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('enabled', CheckboxType::class,[
                'label' => 'seb.enabled',
                'required' => false
            ])
            ->remove('className')
            ->add('className', TextType::class, [
                'label' => 'seb.class_name.label',
                'disabled' => true
            ])
            ->add('columns', CollectionType::class, [
                'entry_type' => SmartExportColumnType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype' => true,
                'required' => true,
                'label' => null,
                'entry_options' => ['label' => false],
            ])
        ;
    }


}