<?php

namespace Odb\SmartExportBundle\Services;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Odb\SmartExportBundle\Entity\SmartExportColumn;
use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Odb\SmartExportBundle\Form\Admin\SmartExportEngineType;
use Odb\SmartExportBundle\Form\Admin\EditSmartExportEngineType;
use Odb\SmartExportBundle\Repository\SmartExportColumnRepository;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;

class SmartExportAdmin implements SmartExportAdminInterface
{
    use SmartExportFinder;
    
    private ?Request $request;




    public function __construct(
        private readonly RequestStack                $requestStack,
        private readonly FormFactoryInterface        $formFactory,
        private readonly SmartExportEngineRepository $smartExportEngineRepository,
        private readonly SmartExportColumnRepository $smartExportColumnRepository
    ){
        $this->request = $requestStack->getCurrentRequest();
    }


    public function removeEngine(string $code):void
    {
        $engine = $this->findByCode($code);
        $this->smartExportEngineRepository->remove($engine);
    }

    public function toggleEngine(string $code):void
    {
        $engine = $this->findByCode($code);
        $engine->setIsActive(!$engine->getIsActive());
        $this->smartExportEngineRepository->save($engine);
    }

    /**
     * @param string $redirectUrl
     * @return FormInterface|RedirectResponse
     */
    public function handleFormNewEngine(string $redirectUrl): RedirectResponse|FormInterface
    {
        $newEngine = new SmartExportEngine();
        $formEngine = $this->formFactory->createNamed('form_smart_export_engine_add', SmartExportEngineType::class, $newEngine);
        $formEngine->handleRequest($this->request);
        if($formEngine->isSubmitted() && $formEngine->isValid()) {
            $newEngine->setIsActive(true);
            $this->smartExportEngineRepository->save($newEngine);
            $url = str_replace('code', $newEngine->getCode(), $redirectUrl);
            return new RedirectResponse($url);
        }
     
        return $formEngine;
    }

    /**
     * @param string $code
     * @param string $redirectUrl
     * @return FormInterface|RedirectResponse
     */
    public function handleFormEditEngine(string $code, string $redirectUrl): RedirectResponse|FormInterface
    {
        $engine = $this->findByCode($code);

        /** array of columns will be used when the form is submitted to check if we need to remove some columns */
        $initialsColumns = [];
        foreach ($engine->getColumns() as $initialColumn) {
            $initialsColumns[$initialColumn->getId()] = $initialColumn;
        }

        $formEdit = $this->formFactory->createNamed('form_smart_export_engine_edit', EditSmartExportEngineType::class, $engine);
        $formEdit->handleRequest($this->request);
        if($formEdit->isSubmitted() && $formEdit->isValid()) {
            $postedColumns = $formEdit->get('columns')->getData();
            /**
             * @note Manage columns update, Steps :
             * 1 check if there is existing column and clean initial columns array
             * 2 remove column if it already exits and not in posted data
             */
            foreach($postedColumns as $postedColumn)
            {
                if($postedColumn->getId()){
                    if (array_key_exists($postedColumn->getId(), $initialsColumns)) {
                        unset($initialsColumns[$postedColumn->getId()]);
                    }
                } else {
                    $postedColumn->setEngine($engine);
                }
            }

            foreach ($initialsColumns as $initialColumn) {
                if($initialColumn instanceof SmartExportColumn) {
                    $engine->removeColumn($initialColumn);
                    $this->smartExportColumnRepository->remove($initialColumn, false);
                }
            }

            $this->smartExportEngineRepository->save($engine);
            $url = str_replace('code', $engine->getCode(), $redirectUrl);
            return new RedirectResponse($url);
        }

        return $formEdit;
    }

}