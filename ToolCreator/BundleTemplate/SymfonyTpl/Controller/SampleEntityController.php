<?php

namespace App\Bundle\SymfonyTpl\Controller;

//ADDITIONAL_USE
use App\Bundle\SymfonyTpl\Service\SymfonyTplService;
use App\Bundle\SymfonyTpl\Entity\SampleEntity;
use App\Bundle\SymfonyTpl\Form\Type\SampleEntityFormType;
use Doctrine\DBAL\Connection;
use MelisPlatformFrameworkSymfony\MelisServiceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;

class SampleEntityController extends AbstractController
{
    /**
     * Store all the parameters
     * @var ParameterBagInterface
     */
    protected $parameters;
    /**
     * @var $toolService
     */
    protected $toolService;
    /**
     * @var $connection
     */
    protected $connection;

    /**
     * SampleEntityController constructor.
     * @param ParameterBagInterface $parameterBag
     * @param SymfonyTplService $toolService
     * @param Connection $connection
     */
    public function __construct(ParameterBagInterface $parameterBag, SymfonyTplService $toolService, Connection $connection)
    {
        $this->parameters = $parameterBag;
        $this->toolService = $toolService;
        $this->connection = $connection;
    }

    /**
     * Override getSubscribedServices function inside AbstractController
     * to add the MelisServiceManager and translator
     * since AbstractController only uses a limited container
     * that only contains some services.
     * Or you can use Dependency Injection.
     *
     * @return array
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(),
        [
            'melis_platform.service_manager' => MelisServiceManager::class,
            'translator' => TranslatorInterface::class,
        ]);
    }

    /**
     * Function to get the tool
     *
     * @return Response
     */
    public function getSymfonyTplTool(): Response
    {
        try {
            $view = $this->render('@SymfonyTpl/lists.html.twig',
                [
                    "tableConfig" => $this->getTableConfig(),
                    //SAVING_TYPE
                ])->getContent();

            return new Response($view);
        }catch (\Exception $ex){
            exit($ex->getMessage());
        }
    }

    /**
     * Get data
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function getSampleEntityData(Request $request)
    {
        /**
         * Prepare the serializer to convert
         * Entity object to array
         */
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {},
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [$encoder]);

        /**
         * Prepare all the parameters needed
         * for data table
         */
        //get sort order
        $sortOrder = $request->get('order', 'ASC');
        $sortOrder = $sortOrder[0]['dir'];
        //get column name to sort
        $colId = array_keys($this->getTableConfigColumns());
        $selCol = $request->get('order', 'sample_primary_id');
        $selCol = $colId[$selCol[0]['column']];
        //convert column name(ex. albName) to exact field name in the table(ex. alb_name)
        $selCol = ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $selCol)), '_');
        //get draw
        $draw = $request->get('draw', 1);
        //get offset
        $start = (int)$request->get('start', 1);
        //get limit
        $length = (int)$request->get('length', 5);
        //get search value
        $search = $request->get('search', null);
        $search = $search['value'];

        //get repository
        $repository = $this->getDoctrine()->getRepository(SampleEntity::class);
        //get total records
        $total = $repository->getTotalRecords();
        //get data
        $tableData = $repository->getSampleEntityData($search, $this->getSearchableColumns(), $selCol, $sortOrder, $length, $start);
        //convert entity object to array
        $tableData = $serializer->normalize($tableData, null);

        for ($ctr = 0; $ctr < count($tableData); $ctr++) {
            $samplePrimaryId = $tableData[$ctr]['samplePrimaryId'];
            // add DataTable RowID, this will be added in the <tr> tags in each rows
            //insert id to every row
            $tableData[$ctr]['DT_RowId'] = $samplePrimaryId;

            //SECOND_TABLE_DATA
        }

        /**
         * Update column display
         */
        $tableData = $this->toolService->updateTableDisplay($tableData, $this->getTableConfigColumnsDisplay());

        //get total filtered record
        $totalFilteredRecord = $serializer->normalize($repository->getTotalFilteredRecord());

        return new JsonResponse(array(
            'draw' => (int) $draw,
            'recordsTotal' => (int) $total,
            'recordsFiltered' => (int) count($totalFilteredRecord),
            'data' => $tableData,
        ));
    }

    /**
     * Get SymfonyTpl Modal Content
     * @param $id
     * @return Response
     */
    public function getSymfonyTplSavingTypeForm($id)
    {
        $data = [];
        foreach($this->getSavingTypeConfig()['tabs'] as $tabName => $tab) {
            /**
             * Check if we use form as our content
             */
            if(!empty($tab['form'])) {
                $entityName = $tab['form']['entity_class_name'];
                $formTypeName = $tab['form']['form_type_class_name'];
                $formView = $tab['form']['form_view_file'];
                $formId = $tab['form']['form_id'];

                /**
                 * get languages if we have a language tab
                 */
                if($tabName == 'tab_language'){
                    //process language form here
                    //LANGUAGE_FORM_BUILDER
                }else {
                    $entity = $this->toolService->getEntity($this->getDoctrine(), $entityName, "find", $id);
                    /**
                     * Create form
                     */
                    $param['form'] = $this->createForm($formTypeName, $entity, [
                        'attr' => [
                            'id' => $formId
                        ]
                    ])->createView();
                }
                $data[$tabName] = $this->renderView($formView, $param);
            }else {
                $data[$tabName] = $tab['content'];
            }
        }
        //FORM_RETURN_DATA
    }

    //SAVE_FUNCTIONS

    /**
     * @param $id
     * @param Request $request
     * @return array
     */
    private function saveSampleEntity($id, Request $request)
    {
        /**
         * Prepare the results
         */
        $result = [
            'errors' => [],
            'success' => false,
            'id' => 0
        ];
        $entity = $this->toolService->getEntity($this->getDoctrine(), 'App\Bundle\SymfonyTpl\Entity\SampleEntity', "find", $id);
        $form = $this->createForm(SampleEntityFormType::class, $entity);
        $this->validatedAndSave($result, $form, $request, $entity);
        return $result;
    }

    /**
     * Validate the form and
     * try to save the data
     * @param $result
     * @param $form
     * @param $request
     * @param $entity
     */
    private function validatedAndSave(&$result, $form, $request, $entity)
    {
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $form->handleRequest($request);
            //validate form
            if ($form->isSubmitted() && $form->isValid()) {
                /**
                 * Check if there are some files needed to upload
                 */
                foreach ($request->files->all() as $fieldName => $file) {
                    //create setter function name to set the file
                    $fName = $this->toolService->generateFunctionName($fieldName);
                    $fName = 'set' . $fName;
                    $methods = get_class_methods(get_class($entity));
                    if (in_array($fName, $methods)) {
                        if (!empty($file))
                            $fileValue = $this->toolService->upload($file);
                        else
                            $fileValue = $request->request->get($fieldName . '_value');

                        $entity->$fName($fileValue);
                    }
                }

                $entity = $form->getData();
                // tell Doctrine you want to (eventually) save the data (no queries yet)
                $entityManager->persist($entity);
                // executes the queries
                $entityManager->flush();

                /**
                 * get the primary key identifier of the entity
                 * so that we can return it's value
                 */
                $result['id'] = $this->toolService->getEntityPrimaryIdValue($entityManager, $entity);
                $result['success'] = true;
            } else {
                $result['errors'] = array_merge($result['errors'], $this->toolService->getErrorsFromForm($form));
                $result['success'] = false;
            }
        }catch (\Exception $ex){
            $result['success'] = false;
        }
    }

    /**
     * Delete SampleEntity
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteSampleEntity(Request $request): JsonResponse
    {
        $icon = 'fa fa-exclamation-triangle';
        $typeCode = 'SYMFONYTPL_TOOL_DELETE';
        $id = $request->get('id', null);

        $translator = $this->get('translator');

        $result = [
            'title' => 'SymfonyTpl',
            'success' => false,
            'message' => $translator->trans('tool_symfony_tpl_cannot_delete'),
        ];
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entity = $entityManager->getRepository(SampleEntity::class)->find($id);
            $entityManager->remove($entity);
            $entityManager->flush();
            $result['message'] = $translator->trans('tool_symfony_tpl_successfully_deleted');
            $result['success'] = true;
            $icon = 'fa fa-info-circle';
        }catch (\Exception $ex){
            //cannot delete item
        }

        //add message notification
        $this->toolService->addToFlashMessenger($result['title'], $result['message'], $icon);
        //save logs
        $this->toolService->saveLogs($result['title'], $result['message'], $result['success'], $typeCode, $id);

        return new JsonResponse($result);
    }

    /**
     * Get searchable columns
     * @return array|mixed
     */
    private function getSearchableColumns()
    {
        if(!empty($this->getTableConfig()['searchables'])){
            return $this->getTableConfig()['searchables'];
        }
        return [];
    }

    /**
     * Get table columns
     * @return array
     */
    private function getTableConfigColumnsDisplay()
    {
        if(!empty($this->getTableConfig()['columnDisplay'])){
            return $this->getTableConfig()['columnDisplay'];
        }
        return [];
    }

    /**
     * Get table columns
     * @return array
     */
    private function getTableConfigColumns()
    {
        if(!empty($this->getTableConfig()['columns'])){
            return $this->getTableConfig()['columns'];
        }
        return [];
    }

    /**
     * Get table config
     * @return mixed|string
     */
    private function getTableConfig()
    {
        $tableConfig = [];
        if(!empty($this->parameters->get('symfony_tpl_table'))){
            $tableConfig = $this->parameters->get('symfony_tpl_table');
            $tableConfig = $this->toolService->translateConfig($tableConfig);
        }
        return $tableConfig;
    }

    /**
     * Get modal config
     * @return array|mixed
     */
    private function getSavingTypeConfig()
    {
        $modalConfig = [];
        if(!empty($this->parameters->get('symfony_tpl_savingType'))){
            $modalConfig = $this->parameters->get('symfony_tpl_savingType');
            $modalConfig = $this->toolService->translateConfig($modalConfig);
        }
        return $modalConfig;
    }

    /**
     * Get Melis Service Manager
     * @return object
     */
    private function melisServiceManager()
    {
        return $this->get('melis_platform.service_manager');
    }
}