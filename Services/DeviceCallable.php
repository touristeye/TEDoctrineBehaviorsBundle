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
        $device = $this->container->get('session')->get('_api_client');

        return $device ?: 0;
    }
}
