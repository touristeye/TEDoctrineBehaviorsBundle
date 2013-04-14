<?php

namespace TE\DoctrineBehaviorsBundle\Model;

trait JSONBindable {

    /**
     * Bind params into the entity
     *
     * @param  array    $params
     * @param  boolean  $canChangePrivateFields
     */
    public function bind($params, $canChangePrivateFields=false) {

        // get the fields that we can change
        $bindableParams = $canChangePrivateFields && isset(self::$privateParams)
            ? array_merge(self::$allowedParams, self::$privateParams)
            : self::$allowedParams;

        // get the allowed params
        $cleanedParams = array_intersect_key($params, array_flip($bindableParams));

        // bind data to entity
        foreach ( $cleanedParams as $field => $value ){
            $method = 'set'.\Doctrine\Common\Util\Inflector::classify($field);
            $this->$method($value);
        }
    }
}