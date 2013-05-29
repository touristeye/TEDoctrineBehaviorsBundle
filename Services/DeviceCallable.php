<?php

namespace TE\DoctrineBehaviorsBundle\Services;

use Symfony\Component\DependencyInjection\Container;

/**
 * DeviceCallable can be invoked to return a device ID
 */
class DeviceCallable
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @constructor
     *
     * @param callable
     * @param string $userEntity
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke()
    {
        // if we don't have the session object or the session has not been started, return 0
        if ( !$this->container->get('session') || !$this->container->get('session')->isStarted() ) {
            return 0;
        }

        $device = $this->container->get('session')->get('_api_client');

        return $device ?: 0;
    }
}
