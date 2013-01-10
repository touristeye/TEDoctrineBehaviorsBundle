<?php

namespace TE\DoctrineBehaviorsBundle\Model;

trait JSONBindable {

    /**
     * Bind params into the entity
     *
     * @param array $params
     */
    public function bind($params) {

        // get the allowed params
        $cleanedParams = array_intersect_key($params,
            array_flip(self::$allowedParams));

        // bind data to entity
        foreach ( $cleanedParams as $field => $value ){
            $method = 'set'.\Doctrine\Common\Util\Inflector::classify($field);
            $this->$method($value);
        }
    }
}