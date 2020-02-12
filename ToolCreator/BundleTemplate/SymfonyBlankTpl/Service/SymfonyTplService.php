<?php

namespace App\Bundle\SymfonyTpl\Service;

use MelisPlatformFrameworkSymfony\MelisServiceManager;
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
}