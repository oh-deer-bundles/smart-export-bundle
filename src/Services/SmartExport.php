<?php


namespace Tbl\SmartExportBundle\Services;

use PhpOffice\PhpSpreadsheet\Exception as SpreadSheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as SpreadSheetWriterException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tbl\SmartExportBundle\Form\SmartExportType;
use Tbl\SmartExportBundle\Model\ExcelStyle;
use Tbl\SmartExportBundle\Model\ExportSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tbl\SmartExportBundle\Repository\SmartExportEngineRepository;

class SmartExport implements SmartExportInterface
{
    use SmartExportFinder;

    public const FORMAT_CSV = 'csv';
    public const FORMAT_TXT = 'txt';
    public const FORMAT_EXCEL_XLSX = 'xlsx';
    public const FORMAT_EXCEL_XLS = 'xls';

    public const SEPARATOR_SEMICOLON = 'semicolon';
    public const SEPARATOR_COMMA = 'comma';
    public const SEPARATOR_TABULATION = 'tabulation';
    public const SEPARATOR_PIPE = 'pipe';

    public const CHARSET_UTF8 = 'UTF-8';
    public const CHARSET_CP1252 = 'CP1252';
    public const CHARSET_MACINTOSH = 'MACINTOSH';
    
    public const CHARSET_INTERNAL = self::CHARSET_UTF8;
    
    

    private $request;
    private $locale;
    private $formFactory;
    private $smartExportChoice;
    private $smartExportQuery;
    private $form;
    private $rawData;
    private $code;
    private $slugger;
    private $exportSettings;
    private $smartExportEngineRepository;

    /**
     * @param SmartExportChoiceInterface $smartExportChoice
     * @param SmartExportQueryInterface $smartExportQuery
     * @param RequestStack $requestStack
     * @param FormFactoryInterface $formFactory
     * @param SluggerInterface $slugger
     */
    public function __construct(
        SmartExportChoiceInterface $smartExportChoice,
        SmartExportQueryInterface $smartExportQuery,
        SmartExportEngineRepository $smartExportEngineRepository,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        SluggerInterface $slugger
    ){
        $this->request = $requestStack->getCurrentRequest();
        $this->locale = $requestStack->getCurrentRequest() ? $requestStack->getCurrentRequest()->getLocale() : 'en';
        $this->formFactory = $formFactory;
        $this->smartExportChoice = $smartExportChoice;
        $this->smartExportQuery = $smartExportQuery;
        $this->slugger = $slugger;
        $this->smartExportEngineRepository = $smartExportEngineRepository;
    }

    public function add(string $code, ?string $filename = null) {
        $this->createForm($code);
        if($filename) {
            $this->exportSettings->setFilename($filename);
        }
    }

    /**
     * @param string $code
     * @param array $formOptions
     * @return FormInterface
     */
    public function createForm(string $code, array $formOptions = []): FormInterface
    {
        if(!$this->form) {
            $this->exportSettings = new ExportSettings();
            $this->exportSettings->setCode($code);
            $this->exportSettings->setFormattedCode(strtolower($this->slugger->slug($code)));
            $this->exportSettings->setLocale($this->locale);
            
            $this->code = $code;
            $formName = 'smart_export_'.$this->exportSettings->getFormattedCode();
            $formOptions['code_export'] = $this->exportSettings->getCode();
            $this->form = $this->formFactory->createNamed($formName, SmartExportType::class, [], $formOptions);
        }
        return $this->form;
    }

    public function handleFrom(): bool
    {
        $this->form->handleRequest($this->request);
        if($this->form->isSubmitted() && $this->form->isValid()) {
            $this->setDefinitions()->setRawData();
            
        }
        return $this->exportSettings->getIsValid();
    }


    /**
     * @param string|null $filename
     * @return BinaryFileResponse|StreamedResponse|null
     * @throws SpreadSheetException
     * @throws SpreadSheetWriterException
     */
    public function getResponse(?string $filename = null)
    {
        if($filename) {
            $this->exportSettings->setFilename($filename);
        }

        return $this->exportSettings->getIsValid()
            ? SmartExportResponseBuilder::getResponse($this->rawData, $this->exportSettings)
            : null;
    }

    private function setDefinitions(): SmartExport
    {
        $fielsData = $this->form->get('fields')->getData();
        $definitions = $this->smartExportChoice->parseChoices($this->code, $fielsData);
        
        $this->exportSettings->setEngine($definitions['engine']);
        $this->exportSettings->setColumns($definitions['columns']);
        $this->exportSettings->setFileFormat($this->form->get('file_format')->getData());
        $this->exportSettings->setCharset($this->form->get('charset')->getData());
        $this->exportSettings->setSeparator($this->form->get('separator')->getData());
        
        if($this->exportSettings->getFileFormat() === self::FORMAT_EXCEL_XLSX
            || $this->exportSettings->getFileFormat() === self::FORMAT_EXCEL_XLS) {
            $this->exportSettings->setExcelStyle(new ExcelStyle());
        }
        
        return $this;
    }

    private function setRawData(): void
    {
        $this->rawData = $this->smartExportQuery->getDataFromExportSettings($this->exportSettings);
        $this->exportSettings->setIsValid(is_array($this->rawData));
    }

    /**
     * @return null|FormInterface
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * @return null|array
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * @return null|ExportSettings
     */
    public function getSettings() :? ExportSettings
    {
        return $this->exportSettings;
    }

    /**
     * @return null|ExcelStyle
     */
    public function getExcelStyle() :? ExcelStyle
    {
        return $this->exportSettings->getExcelStyle();
    }

    /**
     * @param ExcelStyle $excelStyle
     * @return SmartExport
     */
    public function setExcelStyle(ExcelStyle $excelStyle) :self
    {
        $this->exportSettings->setExcelStyle($excelStyle);
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->getRawData();
    }

}
