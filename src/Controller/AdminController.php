<?php

namespace Odb\SmartExportBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Odb\SmartExportBundle\Model\ExcelStyle;
use Odb\SmartExportBundle\Services\SmartExportAdminInterface;
use Odb\SmartExportBundle\Services\SmartExportInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class AdminController extends AbstractController
{
    public function __construct(
        private readonly SmartExportAdminInterface $smartExportAdmin,
        private readonly SmartExportInterface $smartExport,
        private readonly int $maxRows,
    ){
    }


    public function index(Request $request): Response
    {
        $redirectUrl = $this->generateUrl('odb_smart_export_admin_edit', ['uuid' => 'uuid']);

        $renderParameter = [];
        $handlerResponse = $this->smartExportAdmin->handleFormNewEngine($redirectUrl);
        if ($handlerResponse instanceof RedirectResponse) {
            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            $handlerResponse->prepare($request);
            return $handlerResponse->send();
        }

        if ($handlerResponse instanceof FormInterface) {
            $renderParameter['formAddEngine'] = $handlerResponse;
        }

        $renderParameter['engines'] = $this->smartExportAdmin->findAll();
        return $this->render('@OdbSmartExport/admin/index.html.twig', $renderParameter);
//        return $this->render('@OdbSmartExportBundle/admin/index.html.twig', $renderParameter);
    }


    public function edit(string $uuid, Request $request): Response
    {
        $redirectUrl = $this->generateUrl('odb_smart_export_admin_index');
        $handlerResponse = $this->smartExportAdmin->handleFormEditEngine($uuid, $redirectUrl);

        if ($handlerResponse instanceof RedirectResponse) {
            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            $handlerResponse->prepare($request);
            return $handlerResponse->send();
        }

        if ($handlerResponse instanceof StreamedResponse) {
            $handlerResponse->prepare($request);
            return $handlerResponse->send();
        }

        if(!$handlerResponse instanceof FormInterface){
            throw new NotFoundHttpException();
        }


        return $this->render('@OdbSmartExport/admin/edit.html.twig', ['formEditEngine' => $handlerResponse, 'uuid' => $uuid]);
    }

    public function toggle(string $uuid): RedirectResponse
    {
        $this->smartExportAdmin->toggleEngine($uuid);
        return $this->redirectToRoute('odb_smart_export_admin_index');
    }

    public function remove(string $uuid): RedirectResponse
    {
        $this->smartExportAdmin->removeEngine($uuid);
        return $this->redirectToRoute('odb_smart_export_admin_index');
    }

    public function getDataStructure(string $token, EntityManagerInterface $entityManager): StreamedResponse
    {
        if($this->isCsrfTokenValid('odb_smart_export_admin', $token)) {
            throw new AccessDeniedHttpException();
        }
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();
        $data[0] = [
            'entity' => 'Entity',
            'field' => 'Property',
            'type' => 'Type',
            'target' => 'Target',
            'relation_type' => 'RelationType',
            'reversed_by' => 'ReversedProperty'
        ];

        $loop = 1;
        foreach ($metas as $meta) {

            $fieldMappings = $entityManager->getClassMetadata($meta->getName())->fieldMappings;
            $entityName = substr($meta->getName(), strrpos($meta->getName(), '\\') + 1);

            foreach ($fieldMappings as $mapping) {
                $data[$loop] = [
                    'entity' => $entityName,
                    'field' => $mapping['fieldName'],
                    'type' => $mapping['type'],
                    'target' => null,
                    'relation_type' => null,
                    'reversed_by' => null
                ];
                $loop++;
            }

            $associationMappings = $entityManager->getClassMetadata($meta->getName())->associationMappings;
            $associationType = [
                ClassMetadata::ONE_TO_ONE => 'One to One',
                ClassMetadata::MANY_TO_ONE => 'Many to One',
                ClassMetadata::TO_ONE => 'to One',
                ClassMetadata::ONE_TO_MANY => 'One to Many',
                ClassMetadata::MANY_TO_MANY => 'Many to Many',
                ClassMetadata::TO_MANY => 'to Many',
            ];
            foreach ($associationMappings as $mapping) {
                $data[$loop] = [
                    'entity' => $entityName,
                    'field' => $mapping['fieldName'],
                    'type' => 'relation',
                    'target' => substr($mapping['targetEntity'], strrpos($mapping['targetEntity'], '\\') + 1),
                    'relation_type' => $associationType[$mapping['type']] ?? null,
                    'reversed_by' => $mapping['inversedBy'] ?? $mapping['mappedBy'] ??null
                ];
                $loop++;
            }
        }


        $description = 'Smart Export Bundle : Data Structure ';
        $export = 'Generated at ' . date('d/m/Y H:i');



        $excelObject = new Spreadsheet();
        $excelStyle = new ExcelStyle();
        $excelObject->getProperties()
            ->setCreator('Oh Deer Bundle')
            ->setTitle($description)
            ->setSubject($description)
            ->setDescription($description)
            ->setKeywords($description)
            ->setCategory("Admin document");
        $excelObject->getDefaultStyle()->getFont()->setName($excelStyle->getFontFamily())->setSize($excelStyle->getFontSizeMedium());
        $excelObject->removeSheetByIndex(0);


        $maxColLetter = $excelStyle::getColLetter(count($data[0]));
        $excel_sheet = $excelObject->createSheet(1);
        $excel_sheet->setTitle('Data structure');

        $excel_sheet->mergeCells('A1:' . $maxColLetter . '1');
        $excel_sheet->mergeCells('A2:' . $maxColLetter . '2');
        $excel_sheet->mergeCells('A3:' . $maxColLetter . '3');

        $excel_sheet->setCellValue('A1', $description);
        $excel_sheet->setCellValue('A2', $export);

        $excel_sheet->getStyle('A1')->applyFromArray($excelStyle->getTitle1());
        $excel_sheet->getStyle('A2')->applyFromArray($excelStyle->getTitle2());

        $rowMin = 4;

        foreach ($data as $k=>$row) {
            $rowNumber = $k + $rowMin;
            $columnNumber = 1;
            foreach ($row as $value) {
                $colLetter = $excelStyle::getColLetter($columnNumber);
                if($k===0) {
                    $excel_sheet->getColumnDimension($colLetter)->setAutoSize(true);
                    $excel_sheet->getStyle($colLetter.$rowNumber)->applyFromArray($excelStyle->getHeadStyle());
                } else {
                    $excel_sheet->getStyle($colLetter.$rowNumber)->applyFromArray($excelStyle->getMainStyle());
                }
                $excel_sheet->setCellValue($colLetter.$rowNumber,$value);
                $columnNumber ++;
            }
        }
        $excel_sheet->setAutoFilter('A'.$rowMin.':' . $maxColLetter . $rowNumber);

        $filename = (date('dmY_His')) . '_Data_structure.xlsx';
        $writer = IOFactory::createWriter($excelObject, 'Xlsx');

        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            "200",
            [
                'Content-Type' => 'text/vnd.ms-excel; charset=utf-8',
                'Content-Disposition' => 'attachment;filename=' . $filename,
                'Pragma' => 'public',
                'Cache-Control' => 'maxage=1'
            ]
        );
    }

    public function demoExport(string $uuid)
    {
        $formExport = $this->smartExport->createForm($uuid);
        $isValid = $this->smartExport->handleFrom();
        if ($isValid) {
            return $this->smartExport->getResponse();
        }

        return $this->render('@OdbSmartExport/admin/demo.html.twig', [
            'formExport' => $formExport,
            'uuid' => $uuid
        ]);
    }

    public function count(string $uuid): JsonResponse
    {
        $this->smartExport->createForm($uuid);
        $count = $this->smartExport->count();

        return new JsonResponse([
            'count' => $count,
            'maxRows' => $this->maxRows,
            'allowed' => $count <= $this->maxRows,
        ]);
    }
}