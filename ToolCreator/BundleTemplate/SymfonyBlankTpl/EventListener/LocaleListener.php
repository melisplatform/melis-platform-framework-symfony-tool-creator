<?php

namespace App\Bundle\SymfonyTpl\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleListener
{
    private $container;

    /**
     * LocaleListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        /**
         * Get the locale used of melis platform
         * to override the default locale of symfony
         */
        $melisService = $this->container->get('melis_platform.service_manager');
        if(!empty($melisService->getMelisLangLocale())){
            $request->setLocale($melisService->getMelisLangLocale());
        }
    }
}