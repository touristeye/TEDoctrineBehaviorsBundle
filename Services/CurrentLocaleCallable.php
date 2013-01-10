<?php

namespace TE\DoctrineBehaviorsBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author     Florian Klein <florian.klein@free.fr>
 */
class CurrentLocaleCallable
{
    /* Container */
    protected $container;

    /**
     * Construct
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke()
    {
        if (!$this->container->isScopeActive('request')) {
            return;
        }

        $request = $this->container->get('request');

        return $request->getLocale();
    }
}

