<?php


namespace Odb\SmartExportBundle\Services;

use PhpOffice\PhpSpreadsheet\Exception as SpreadSheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as SpreadSheetWriterException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;
use Odb\SmartExportBundle\Form\SmartExportType;
use Odb\SmartExportBundle\Model\ExcelStyle;
use Odb\SmartExportBundle\Model\ExportSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;

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
    
    

    private ?Request $request;
    private string $locale;
    private ?FormInterface $form = null;
    private ?array $rawData;
    private ?string $code;

    private ExportSettings $exportSettings;

    public function __construct(
        private readonly SmartExportChoiceInterface  $smartExportChoice,
        private readonly SmartExportQueryInterface   $smartExportQuery,
        private readonly SmartExportEngineRepository $smartExportEngineRepository,
        private readonly RequestStack                $requestStack,
        private readonly FormFactoryInterface        $formFactory,
        private readonly SluggerInterface $slugger
    ){
        $this->request = $requestStack->getCurrentRequest();
        $this->locale = $requestStack->getCurrentRequest() ? $requestStack->getCurrentRequest()->getLocale() : 'en';
    }

    public function add(string $code, ?string $filename = null): void
    {
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
        $this->exportSettings->setIsValid((bool)$this->rawData);
    }


    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    public function getSettings() :? ExportSettings
    {
        return $this->exportSettings;
    }

    public function getExcelStyle() :? ExcelStyle
    {
        return $this->exportSettings->getExcelStyle();
    }


    public function setExcelStyle(ExcelStyle $excelStyle) :static
    {
        $this->exportSettings->setExcelStyle($excelStyle);
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }


    public function getData(): ?array
    {
        return $this->getRawData();
    }

}
