<?php

namespace TE\DoctrineBehaviorsBundle\Annotation;

/**
 * @Annotation
 */
class Blameable
{
    /* If it has the createdby field */
    private $hasCreatedby = true;

    /* If it has the createdby field */
    private $hasUpdatedby = true;

    public function __construct($data)
    {
        if ( isset($data['create']) ) {
            $this->hasCreatedby = $data['create'];
        }

        if ( isset($data['update']) ) {
            $this->hasUpdatedby = $data['update'];
        }
    }

    public function getHasCreatedby()
    {
        return $this->hasCreatedby;
    }

    public function getHasUpdatedby()
    {
        return $this->hasUpdatedby;
    }
}