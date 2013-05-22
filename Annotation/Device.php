<?php

namespace TE\DoctrineBehaviorsBundle\Annotation;

/**
 * @Annotation
 */
class Device
{
    /* If it has the createOn field */
    private $hasCreatedOn = true;

    /* If it has the createdOn field */
    private $hasUpdatedOn = true;

    public function __construct($data)
    {
        if ( isset($data['create']) ) {
            $this->hasCreatedOn = $data['create'];
        }

        if ( isset($data['update']) ) {
            $this->hasUpdatedOn = $data['update'];
        }
    }

    public function getHasCreatedOn()
    {
        return $this->hasCreatedOn;
    }

    public function getHasUpdatedOn()
    {
        return $this->hasUpdatedOn;
    }
}