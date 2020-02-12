<?php

namespace App\Bundle\SymfonyTpl\Service;

use MelisPlatformFrameworkSymfony\MelisServiceManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;

class SymfonyTplService
{
    /**
     * @var $melisServiceManager
     */
    protected $melisServiceManager;
    /**
     * @var $translator
     */
    protected $translator;
    /**
     * @var string
     */
    protected $moduleName = 'SymfonyTpl';

    /**
     * SymfonyTplService constructor.
     * @param MelisServiceManager $melisServiceManager
     * @param TranslatorInterface $translator
     */
    public function __construct(MelisServiceManager $melisServiceManager, TranslatorInterface $translator)
    {
        $this->melisServiceManager = $melisServiceManager;
        $this->translator = $translator;
    }

    /**
     * Get Melis Cms langauges
     * @return array
     */
    public function getCmsLanguages()
    {
        try {
            $cmsLangTable = $this->melisServiceManager->getService('MelisEngineTableCmsLang');
            $result = $cmsLangTable->fetchAll()->toArray();
            return $result;
        }catch (\Exception $ex){
            return [];
        }
    }

    /**
     * Update table display depending on
     * column display type in the config
     *
     * @param $data
     * @param $config
     * @return mixed
     */
    public function updateTableDisplay($data, $config)
    {
        foreach($data as $key => $val){
            foreach($val as $column => $info){
                if(array_key_exists($column, $config)){
                    /**
                     * Display a dot color instead of it's original value
                     */
                    if($config[$column] == 'dot_color'){
                        if(!is_array($info)) {
                            $data[$key][$column] = '<span class="text-'.($info ? 'success' : 'danger').'"><i class="fa fa-fw fa-circle"></i></span>';
                        }
                    }
                    /**
                     * Display the data with limit
                     */
                    elseif($config[$column] == 'char_length_limit'){
                        if(!is_array($info)) {
                            if (strlen($info) > 50)
                                $data[$key][$column] = substr($info, 0, 50) . '...';
                        }
                    }
                    /**
                     * Display the user name instead of it's id
                     */
                    elseif($config[$column] == 'admin_name'){
                        if(is_array($info)) {
                            if (!empty($info['usrName'])) {
                                $data[$key][$column] = $info['usrName'];
                            }
                        }
                    }
                    /**
                     * Display the template name instead of it's id
                     */
                    elseif($config[$column] == 'tpl_name'){
                        if(is_array($info)) {
                            if (!empty($info['tplName'])) {
                                $data[$key][$column] = $info['tplName'];
                            }
                        }
                    }
                    /**
                     * Display the language name instead of it's id
                     */
                    elseif($config[$column] == 'lang_name'){
                        if(is_array($info)) {
                            if (!empty($info['langCmsName'])) {
                                $data[$key][$column] = $info['langCmsName'];
                            }
                        }
                    }
                    /**
                     * Display the site name instead of it's id
                     */
                    elseif($config[$column] == 'site_name'){
                        if(is_array($info)) {
                            if (!empty($info['siteName'])) {
                                $data[$key][$column] = $info['siteName'];
                            }
                        }
                    }
                    /**
                     * Display its original data
                     */
                    elseif($config[$column] == 'raw_view'){
                        if(is_array($info)) {
                            if (!empty($info['rawData'])) {
                                $data[$key][$column] = $info['rawData'];
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Get form errors
     * @param FormInterface $form
     * @return array
     */
    public function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errMessage = $childErrors[0] ?? null;
                    $fieldLabel = $childForm->getConfig()->getOption('label');
                    $fieldLabel = $this->translator->trans($fieldLabel);
                    $errors[$childForm->getName()] = ['error_message' => $errMessage, 'label' => $fieldLabel];
                }
            }
        }

        return $errors;
    }

    /**
     * Add logs to notification
     *
     * @param $title
     * @param $message
     * @param string $icon
     * @throws \Exception
     */
    public function addToFlashMessenger($title, $message, $icon = 'fa fa-info-circle')
    {
        $flashMessenger = $this->melisServiceManager->getService('MelisCoreFlashMessenger');
        $flashMessenger->addToFlashMessenger($title, $message, $icon);
    }

    /**
     * Save logs
     *
     * @param $title
     * @param $message
     * @param $success
     * @param $typeCode
     * @param $itemId
     * @throws \Exception
     */
    public function saveLogs($title, $message, $success, $typeCode, $itemId)
    {
        $logs = $this->melisServiceManager->getService('MelisCoreLogService');
        $logs->saveLog($title, $message, $success, $typeCode, $itemId);
    }

    /**
     * Translate some text in the config
     * @param $config
     * @return mixed
     */
    public function translateConfig($config)
    {
        foreach($config as $key => $value){
            if(is_array($value)){
                $config[$key] = $this->translateConfig($value);
            }else{
                $config[$key] = $this->translator->trans($value);
            }
        }
        return $config;
    }

    /**
     * Upload file
     * @param UploadedFile $file
     * @param $targetDirectory
     * @return string
     */
    public function upload(UploadedFile $file, $targetDirectory = null)
    {
        //set target directory
        if(empty($targetDirectory))
            $targetDirectory = $this->getDirectoryPath().'/'.$this->moduleName.'/';

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $safeFilename .= '_'.uniqid();
        $fileName = '/'.$this->moduleName.'/'.$safeFilename.'.'.$file->guessExtension();

        try {
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $fileName;
    }

    /**
     * @return string
     */
    public function getDirectoryPath()
    {
        return $_SERVER['DOCUMENT_ROOT'].'/media';
    }

    /**
     * @param $data
     * @param null $format
     * @return array|bool|float|int|mixed|string
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function serialize($data, $format = null)
    {
        /**
         * Prepare the serializer to convert
         * Entity object to array
         */
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [$encoder]);

        return $serializer->normalize($data, $format);
    }

    /**
     * Get Entity Information
     *
     * @param $doctrine
     * @param $entityName
     * @param $fn
     * @param $param
     * @return mixed
     */
    public function getEntity($doctrine, $entityName, $fn, $param)
    {
        if (!empty($param)) {
            $entity = $doctrine
                ->getRepository($entityName)
                ->$fn($param);
            //if result is empty, we create a blank entity(except for findBy function)
            if(empty($entity) && $fn != 'findBy'){
                $entity = new $entityName();
            }
        }else{
            $entity = new $entityName();
        }

        return $entity;
    }

    /**
     * @param $entityManager
     * @param $entity
     * @return int
     */
    public function getEntityPrimaryIdValue($entityManager, $entity)
    {
        $id = 0;
        $meta = $entityManager->getClassMetadata(get_class($entity));
        $identifiers = $meta->getIdentifierFieldNames();
        if (!empty($identifiers)) {
            //create a function to get the id ex: getPrimaryId()
            $funcName = $this->generateFunctionName($identifiers[0]);
            $funcName = 'get' . $funcName;
            $id = $entity->$funcName();
        }
        return $id;
    }

    /**
     * @param $name
     * @return mixed|string
     */
    public function generateFunctionName($name)
    {
        $fName = ucwords(str_replace(array('-','_'), ' ', $name));
        $fName = str_replace(' ', '', $fName);

        return $fName;
    }

    /**
     * This will whether all of the data
     * in array is empty
     * @param $array
     * @return bool
     */
    public function isArrayEmpty($array) {
        foreach($array as $key => $val) {
            if (!empty($val))
                return false;
        }
        return true;
    }
}