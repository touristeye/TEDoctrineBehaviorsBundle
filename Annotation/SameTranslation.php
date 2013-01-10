<?php

namespace TE\DoctrineBehaviorsBundle\Annotation;

/**
 * @Annotation
 */
class SameTranslation
{
    /* If we want the same value of this field on the other translations */
    private $sameTranslation;

    public function __construct($data)
    {
        $this->sameTranslation = isset($data['value']) ?: true;
    }

    public function getSameTranslation()
    {
        return $this->sameTranslation;
    }
}