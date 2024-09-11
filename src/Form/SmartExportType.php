<?php

namespace Odb\SmartExportBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Odb\SmartExportBundle\Services\SmartExport;
use Odb\SmartExportBundle\Services\SmartExportChoice;
use Odb\SmartExportBundle\Services\SmartExportChoiceInterface;

class SmartExportType extends AbstractType
{
    public function __construct(
        private readonly SmartExportChoiceInterface $exportChoice)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$options['columns'] = $this->getFieldChoices($options['code_export']);
        $builder
            ->add('file_format', ChoiceType::class, array(
                'required'  => true,
                'label'     => 'seb.file_format.label',
//                'mapped'    => false,
                'choices'   => array(
                    'seb.file_format.excel' => SmartExport::FORMAT_EXCEL_XLSX,
                    'seb.file_format.csv' => SmartExport::FORMAT_CSV,
                    'seb.file_format.txt' => SmartExport::FORMAT_TXT,
                )
            ))
            ->add('separator', ChoiceType::class, array(
                'required'  => true,
                'label'     => 'seb.separator.label',
//                'mapped'    => false,
                'choices'   => array(
                    'seb.separator.semicolon' => SmartExport::SEPARATOR_SEMICOLON,
                    'seb.separator.comma' => SmartExport::SEPARATOR_COMMA,
                    'seb.separator.tabulation' => SmartExport::SEPARATOR_TABULATION,
                    'seb.separator.pipe' => SmartExport::SEPARATOR_PIPE
                )
            ))
            ->add('charset', ChoiceType::class, array(
                'required'  => true,
                'label'     => 'seb.charset.label',
//                'mapped'    => false,
                'choices'   => array(
                    'seb.charset.windows' => SmartExport::CHARSET_CP1252,
                    'seb.charset.mac' => SmartExport::CHARSET_MACINTOSH,
                    'seb.charset.utf8' => SmartExport::CHARSET_UTF8
                )
            ))
            ->add('fields', HiddenType::class, array(
                'required'  => true,
                'attr' => ['class' => 'smart_export_fields']
            ))
            ->addEventListener(FormEvents::POST_SET_DATA, [$this,'onPostSetData'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'code_export' => null,
            'translation_domain' => 'smart_export_bundle_forms',
        ]);
    }

    public function onPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $code_export = $event->getForm()->getConfig()->getOption('code_export');
        if($code_export) {
            $choices =
            $form->add('choices', ChoiceType::class, [
                'choices' =>  $this->exportChoice->getChoices($code_export),
                'label'     => null,
                'required'    => false,
                'mapped'    => false,
            ]);
        }
    }
}
