<?php

/**
 * This file is part of the KnpDoctrineBehaviorsBundle package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TE\DoctrineBehaviorsBundle\Listener;

use Doctrine\Common\Annotations\Reader,
    Doctrine\Common\EventSubscriber,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * BlameableListener handle Blameable entites
 * Adds class metadata depending of user type (entity or string)
 * Listens to prePersist and PreUpdate lifecycle events
 */
class BlameableListener implements EventSubscriber
{
    const ANNOTATION_CLASS = 'TE\\DoctrineBehaviorsBundle\\Annotation\\Blameable';

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var callable
     */
    private $userCallable;

    /**
     * @var mixed
     */
    private $user;

    /**
     * userEntity name
     */
    private $userEntity;

    /**
     * @constructor
     *
     * @param Reader $reader
     * @param callable
     * @param string $userEntity
     */
    public function __construct(Reader $reader, callable $userCallable = null, $userEntity = null)
    {
        $this->reader       = $reader;
        $this->userCallable = $userCallable;
        $this->userEntity   = $userEntity;
    }

    /**
     * Adds metadata about how to store user, either a string or an ManyToOne association on user entity
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
        $hasCreatedBy = $hasUpdatedBy = true;

        if ($annotation = $this->reader->getClassAnnotation($classMetadata->getReflectionClass(), self::ANNOTATION_CLASS)) {

            $hasCreatedBy = $annotation->getHasCreatedby();
            $hasUpdatedBy = $annotation->getHasUpdatedby();
        }

        if ($this->userEntity) {
            $this->mapManyToOneUser($classMetadata, $hasCreatedBy, $hasUpdatedBy);
        } else {
            $this->mapStringUser($classMetadata, $hasCreatedBy, $hasUpdatedBy);
        }
    }

    private function mapStringUser(ClassMetadata $classMetadata, $hasCreatedBy, $hasUpdatedBy)
    {
        if (!$classMetadata->hasField('createdBy') && $hasCreatedBy ) {
            $classMetadata->mapField([
                'fieldName'  => 'createdBy',
                'columnName' => 'created_by',
                'type'       => 'string',
                'nullable'   => true,
            ]);
        }

        if (!$classMetadata->hasField('updatedBy') && $hasUpdatedBy ) {
            $classMetadata->mapField([
                'fieldName'  => 'updatedBy',
                'columnName' => 'updated_by',
                'type'       => 'string',
                'nullable'   => true,
            ]);
        }
    }

    private function mapManyToOneUser(classMetadata $classMetadata, $hasCreatedBy, $hasUpdatedBy)
    {
        if (!$classMetadata->hasAssociation('createdBy') && $hasCreatedBy ) {
            $classMetadata->mapManyToOne([
                'fieldName'    => 'createdBy',
                'targetEntity' => $this->userEntity,
                'joinColumns'  => array(array(
                    'name'                 => 'created_by_id',
                    'referencedColumnName' => 'id'
                ))
            ]);
        }
        if (!$classMetadata->hasAssociation('updatedBy') && $hasUpdatedBy ) {
            $classMetadata->mapManyToOne([
                'fieldName'    => 'updatedBy',
                'targetEntity' => $this->userEntity,
                'joinColumns'  => array(array(
                    'name'                 => 'updated_by_id',
                    'referencedColumnName' => 'id'
                ))
            ]);
        }
    }

    /**
     * Stores the current user into createdBy and updatedBy properties
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

            $user = $this->getUser();

            // no valid user
            if ( !$this->isValidUser($user) ) return;

            // Add createdBy
            if ($classMetadata->hasAssociation('createdBy') && !$entity->getCreatedBy() ) {

                $entity->setCreatedBy($user);
                $uow->propertyChanged($entity, 'createdBy', null, $user);
            }

            // Add updatedBy
            if ($classMetadata->hasAssociation('updatedBy') && !$entity->getUpdatedBy() ) {

                $entity->setUpdatedBy($user);
                $uow->propertyChanged($entity, 'updatedBy', null, $user);
            }
        }
    }

    /**
     * Stores the current user into updatedby property
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

            $user = $this->getUser();

            // no valid user
            if ( !$this->isValidUser($user) ) return;

            // Update updatedBy
            if ($classMetadata->hasAssociation('updatedBy') ) {

                $oldValue = $entity->getUpdatedBy();

                $entity->setUpdatedBy($user);
                $uow->propertyChanged($entity, 'updatedBy', $oldValue, $user);
            }
        }
    }

    /**
     * Check if the user is valid
     *
     * @param   user     $user
     * @return  boolean
     */
    private function isValidUser($user)
    {
        if ($this->userEntity) {
            return $user instanceof $this->userEntity;
        }

        if (is_object($user)) {
            return method_exists($user, '__toString');
        }

        return is_string($user);
    }

    /**
     * Set a custome representation of current user
     *
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get current user
     *
     * @return mixed The user reprensentation
     */
    public function getUser()
    {
        if (null !== $this->user) {
            return $this->user;
        }
        if (null === $this->userCallable) {
            return;
        }

        $callable = $this->userCallable;

        return $callable();
    }

    /**
     * Checks if entity supports Blameable
     *
     * @param ClassMetadata $classMetadata
     * @param bool          $isRecursive   true to check for parent classes until trait is found
     *
     * @return boolean
     */
    private function isEntitySupported(\ReflectionClass $reflClass, $isRecursive = false)
    {
        $isSupported = in_array('TE\DoctrineBehaviorsBundle\Model\Blameable',
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

    public function setUserCallable(callable $callable)
    {
        $this->userCallable = $callable;
    }
}
