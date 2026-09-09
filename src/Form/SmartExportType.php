<?php

namespace Odb\SmartExportBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Odb\SmartExportBundle\Enum\FilterWidget;
use Odb\SmartExportBundle\Services\SmartExport;
use Odb\SmartExportBundle\Services\SmartExportChoiceInterface;

class SmartExportType extends AbstractType
{
    public function __construct(
        private readonly SmartExportChoiceInterface $exportChoice)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file_format', ChoiceType::class, array(
                'required'  => true,
                'label'     => 'seb.file_format.label',
                // Real radios (not a <select>) so the popup can style each choice as its
                // own branded button (Excel/CSV/Texte) — see templates/popup/export_popup.html.twig.
                // Needs an explicit default: unlike a <select>, an expanded ChoiceType
                // doesn't auto-check any radio, so file_format would submit null otherwise.
                'expanded'  => true,
                'data'      => SmartExport::FORMAT_EXCEL_XLSX,
                'choices'   => array(
                    'seb.file_format.excel' => SmartExport::FORMAT_EXCEL_XLSX,
                    'seb.file_format.csv' => SmartExport::FORMAT_CSV,
                    'seb.file_format.txt' => SmartExport::FORMAT_TXT,
                )
            ))
            ->add('separator', ChoiceType::class, array(
                'required'  => true,
                'label'     => 'seb.separator.label',
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
                'choices'   => array(
                    'seb.charset.windows' => SmartExport::CHARSET_CP1252,
                    'seb.charset.mac' => SmartExport::CHARSET_MACINTOSH,
                    'seb.charset.utf8' => SmartExport::CHARSET_UTF8
                )
            ))
            ->add('fields', HiddenType::class, array(
                'required'  => true,
                // Seeded server-side from the columns marked selectedByDefault (see
                // AdminController::demoExport()) so a submission is valid even when the
                // Colonnes panel itself isn't rendered (detailed=false — col_chips_controller.js,
                // which normally recomputes this on connect(), never runs then). When the
                // panel IS rendered this default is immediately overwritten by that same JS
                // from the chips' own aria-pressed state, so it changes nothing there.
                'data'      => $options['default_fields'] ?? null,
                'attr' => ['class' => 'smart_export_fields']
            ))
            ->add('filters', FormType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
            ])
            // The two options below are the smart_export_popup() Twig function's `id` and
            // `detailed` options, threaded through as hidden fields so they survive the
            // count()/generate POST round trips exactly like `fields`/`file_format` do —
            // see AdminController::demoExport() for where their initial value comes from
            // (the trigger's query string, on the very first GET only).
            ->add('id_filter', HiddenType::class, [
                'required' => false,
                'data' => $options['id_filter'] ?? null,
            ])
            ->add('detailed', HiddenType::class, [
                'required' => false,
                'data' => $options['detailed'] ?? '1',
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this,'onPostSetData'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'uuid_export' => null,
            'default_fields' => null,
            'id_filter' => null,
            'detailed' => null,
            'translation_domain' => 'smart_export_bundle_forms',
        ]);
    }

    public function onPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $uuid_export = $event->getForm()->getConfig()->getOption('uuid_export');
        if($uuid_export) {
            $form->add('choices', ChoiceType::class, [
                'choices' =>  $this->exportChoice->getChoices($uuid_export),
                'label'     => null,
                'required'    => false,
                'mapped'    => false,
            ]);

            $filtersForm = $form->get('filters');
            foreach ($this->exportChoice->getFilterableColumns($uuid_export) as $column) {
                $filterOptions = [
                    'interpreter' => $column->getInterpreter(),
                    'default_value' => $column->getFilterDefaultValue(),
                    'label' => $column->getLabel(),
                    'filter_widget' => $column->getFilterWidget(),
                    'filter_display' => $column->isFilterDisplay(),
                ];
                if (in_array($column->getFilterWidget(), [FilterWidget::Select, FilterWidget::SingleSelect], true)) {
                    $filterOptions['choices'] = $this->exportChoice->getDistinctValuesForColumn($column);
                }
                $filtersForm->add('col_'.$column->getId(), SmartExportFilterType::class, $filterOptions);
            }
        }
    }
}
