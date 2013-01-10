<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TE\DoctrineBehaviorsBundle\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Events,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\Common\EventSubscriber,
    Doctrine\Common\Annotations\Reader;

/**
 * Timestampable listener.
 *
 * Adds mapping to the timestampable entites.
 */
class TimestampableListener implements EventSubscriber
{
    const ANNOTATION_CLASS = 'TE\\DoctrineBehaviorsBundle\\Annotation\\Timestampable';

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @constructor
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader       = $reader;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isEntitySupported($classMetadata)) {

            $this->mapEntity($classMetadata);

            if ($classMetadata->reflClass->hasMethod('updateTimestamps')) {
                $classMetadata->addLifecycleCallback('updateTimestamps', Events::prePersist);
                $classMetadata->addLifecycleCallback('updateTimestamps', Events::preUpdate);
            }
        }
    }

    private function mapEntity(ClassMetadata $classMetadata)
    {
        $hasCreatedAt = $hasUpdatedAt = true;

        if ($annotation = $this->reader->getClassAnnotation($classMetadata->getReflectionClass(), self::ANNOTATION_CLASS)) {

            $hasCreatedAt = $annotation->getHasCreatedAt();
            $hasUpdatedAt = $annotation->getHasUpdatedAt();
        }

        if (!$classMetadata->hasField('createdAt') && $hasCreatedAt ) {
            $classMetadata->mapField([
                'fieldName'  => 'createdAt',
                'columnName' => 'created_at',
                'type'       => 'datetime',
                'nullable'   => true,
            ]);
        }

        if (!$classMetadata->hasField('updatedAt') && $hasUpdatedAt ) {
            $classMetadata->mapField([
                'fieldName'  => 'updatedAt',
                'columnName' => 'updated_at',
                'type'       => 'datetime',
                'nullable'   => true,
            ]);
        }
    }

    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }

    /**
     * Checks whether provided entity is supported.
     *
     * @param ClassMetadata $classMetadata The metadata
     *
     * @return Boolean
     */
    private function isEntitySupported(ClassMetadata $classMetadata)
    {
        $traitNames = $classMetadata->reflClass->getTraitNames();

        return in_array('TE\DoctrineBehaviorsBundle\Model\Timestampable', $traitNames);
    }
}
