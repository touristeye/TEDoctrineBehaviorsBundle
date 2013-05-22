<?php

namespace TE\DoctrineBehaviorsBundle\Listener;

use Doctrine\Common\Annotations\Reader,
    Doctrine\Common\EventSubscriber,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * DeviceListener handle Device entites
 * Adds the device on which the object was created
 * Listens to prePersist and PreUpdate lifecycle events
 */
class DeviceListener implements EventSubscriber
{
    const ANNOTATION_CLASS = 'TE\\DoctrineBehaviorsBundle\\Annotation\\Device';

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var callable
     */
    private $deviceCallable;

    private $device = null;

    /**
     * @constructor
     *
     * @param Reader $reader
     * @param callable
     */
    public function __construct(Reader $reader, callable $deviceCallable = null)
    {
        $this->reader         = $reader;
        $this->deviceCallable = $deviceCallable;
    }

    /**
     * Adds metadata
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isEntitySupported($classMetadata->reflClass)) {
            $this->mapEntity($classMetadata);
        }
    }

    private function mapEntity(ClassMetadata $classMetadata)
    {
        $hasCreatedOn = $hasUpdatedOn = true;

        if ($annotation = $this->reader->getClassAnnotation($classMetadata->getReflectionClass(), self::ANNOTATION_CLASS)) {

            $hasCreatedOn = $annotation->getHasCreatedOn();
            $hasUpdatedOn = $annotation->getHasUpdatedOn();
        }

        if (!$classMetadata->hasField('createdOn') && $hasCreatedOn ) {
            $classMetadata->mapField([
                'fieldName'  => 'createdOn',
                'columnName' => 'created_on',
                'type'       => 'smallint',
                'nullable'   => true
            ]);
        }

        if (!$classMetadata->hasField('updatedOn') && $hasUpdatedOn ) {
            $classMetadata->mapField([
                'fieldName'  => 'updatedOn',
                'columnName' => 'updated_on',
                'type'       => 'smallint',
                'nullable'   => true
            ]);
        }
    }

    /**
     * Stores the current device into createdOn and updatedOn properties
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $em     = $eventArgs->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $entity = $eventArgs->getEntity();

        $classMetadata = $em->getClassMetadata(get_class($entity));

        if ($this->isEntitySupported($classMetadata->reflClass, true)) {

            $device = $this->getDevice();

            // Add createdOn
            if ($classMetadata->hasField('createdOn') && !$entity->getCreatedOn() ) {

                $entity->setCreatedOn($device);
                $uow->propertyChanged($entity, 'createdOn', null, $device);
            }

            // Add updatedOn
            if ($classMetadata->hasField('updatedOn') && !$entity->getUpdatedOn() ) {

                $entity->setUpdatedOn($device);
                $uow->propertyChanged($entity, 'updatedOn', null, $device);
            }
        }
    }

    /**
     * Stores the current device into updatedOn property
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $em     = $eventArgs->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $entity = $eventArgs->getEntity();

        $classMetadata = $em->getClassMetadata(get_class($entity));

        if ($this->isEntitySupported($classMetadata->reflClass, true)) {

            $device = $this->getDevice();

            // Update updatedOn
            if ($classMetadata->hasField('updatedOn') ) {

                $oldValue = $entity->getUpdatedOn();

                $entity->setUpdatedOn($device);
                $uow->propertyChanged($entity, 'updatedOn', $oldValue, $device);
            }
        }
    }

    /**
     * Get device
     *
     * @return integer
     */
    public function getDevice()
    {
        if (null !== $this->device) {
            return $this->device;
        }
        if (null === $this->deviceCallable) {
            return;
        }

        $callable = $this->deviceCallable;

        return $callable();
    }

    /**
     * Checks if entity supports Device
     *
     * @param ClassMetadata $classMetadata
     * @param bool          $isRecursive   true to check for parent classes until trait is found
     *
     * @return boolean
     */
    private function isEntitySupported(\ReflectionClass $reflClass, $isRecursive = false)
    {
        $isSupported = in_array('TE\DoctrineBehaviorsBundle\Model\Device',
            $reflClass->getTraitNames());

        while ($isRecursive and !$isSupported and $reflClass->getParentClass()) {
            $reflClass = $reflClass->getParentClass();
            $isSupported = $this->isEntitySupported($reflClass, true);
        }

        return $isSupported;
    }

    public function getSubscribedEvents()
    {
        $events = [
            Events::prePersist,
            Events::preUpdate,
            Events::loadClassMetadata,
        ];

        return $events;
    }

    public function setDeviceCallable(callable $callable)
    {
        $this->deviceCallable = $callable;
    }
}
