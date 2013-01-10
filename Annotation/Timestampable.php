<?php

namespace TE\DoctrineBehaviorsBundle\Annotation;

/**
 * @Annotation
 */
class Timestampable
{
    /* If it has the createdAt field */
    private $hasCreatedAt = true;

    /* If it has the updatedAt field */
    private $hasUpdatedAt = true;

    public function __construct($data)
    {
        if ( isset($data['create']) ) {
            $this->hasCreatedAt = $data['create'];
        }

        if ( isset($data['update']) ) {
            $this->hasUpdatedAt = $data['update'];
        }
    }

    public function getHasCreatedAt()
    {
        return $this->hasCreatedAt;
    }

    public function getHasUpdatedAt()
    {
        return $this->hasUpdatedAt;
    }
}