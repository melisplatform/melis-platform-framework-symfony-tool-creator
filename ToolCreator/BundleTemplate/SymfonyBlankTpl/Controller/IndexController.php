<?php

namespace App\Bundle\SymfonyTpl\Controller;

use App\Bundle\SymfonyTpl\Service\SymfonyTplService;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MelisPlatformFrameworkSymfony\MelisServiceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class IndexController extends AbstractController
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
     * @var
     */
    protected $doctrine;

    /**
     * IndexController constructor.
     * @param ParameterBagInterface $parameterBag
     * @param ManagerRegistry $doctrine
     * @param SymfonyTplService $toolService
     * @param Connection $connection
     */
    public function __construct(ParameterBagInterface $parameterBag, ManagerRegistry $doctrine, SymfonyTplService $toolService, Connection $connection)
    {
        $this->parameters = $parameterBag;
        $this->toolService = $toolService;
        $this->connection = $connection;
        $this->doctrine = $doctrine;
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
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(),
        [
            'melis_platform.service_manager' => MelisServiceManager::class,
            'translator' => TranslatorInterface::class,
        ]);
    }

    /**
     * Function to get the tool
     */
    public function getSymfonyTplTool()
    {
        return $this->render("@SymfonyTpl/index.html.twig");
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