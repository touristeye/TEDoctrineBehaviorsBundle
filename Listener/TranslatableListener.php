<?php

namespace TE\DoctrineBehaviorsBundle\Listener;

use Doctrine\Common\Annotations\Reader,
    Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Translatable Doctrine2 listener.
 *
 * Provides mapping for translatable entities and their translations.
 */
class TranslatableListener implements EventSubscriber
{
    const ANNOTATION_CLASS = 'TE\\DoctrineBehaviorsBundle\\Annotation\\SameTranslation';

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var callable
     */
    private $currentLocaleCallable;

    /**
     * @var array
     */
    private $acceptedLocales;

    /**
     * @constructor
     *
     * @param Reader $reader
     * @param callable
     * @param array $acceptedLocales
     */
    public function __construct(Reader $reader, callable $currentLocaleCallable = null, array $acceptedLocales = array())
    {
        $this->reader                = $reader;
        $this->currentLocaleCallable = $currentLocaleCallable;
        $this->acceptedLocales       = $acceptedLocales;
    }

    /**
     * Adds mapping to the translatable and translations.
     *
     * @param LoadClassMetadataEventArgs $eventArgs The event arguments
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isTranslatable($classMetadata->reflClass)) {
            $this->mapTranslatable($classMetadata);
        }

        if ($this->isTranslation($classMetadata)) {
            $this->mapTranslation($classMetadata);
        }
    }

    private function mapTranslatable(ClassMetadata $classMetadata)
    {
        if (!$classMetadata->hasAssociation('translations')) {
            $classMetadata->mapOneToMany([
                'fieldName'    => 'translations',
                'mappedBy'     => 'translatable',
                'indexBy'      => 'locale',
                'cascade'      => ['persist', 'merge', 'remove'],
                'targetEntity' => $classMetadata->name.'Translation'
            ]);
        }
    }

    private function mapTranslation(ClassMetadata $classMetadata)
    {
        if (!$classMetadata->hasAssociation('translatable')) {
            $classMetadata->mapManyToOne([
                'fieldName'    => 'translatable',
                'inversedBy'   => 'translations',
                'joinColumns'  => [[
                    'name'                 => 'translatable_id',
                    'referencedColumnName' => 'id',
                    'onDelete'             => 'CASCADE'
                ]],
                'targetEntity' => substr($classMetadata->name, 0, -11)
            ]);
        }

        $name = $classMetadata->getTableName().'_unique_translation';
        if (!$this->hasUniqueTranslationConstraint($classMetadata, $name)) {
            $classMetadata->setPrimaryTable([
                'uniqueConstraints' => [[
                    'name'    => $name,
                    'columns' => ['translatable_id', 'locale' ]
                ]],
            ]);
        }
    }

    private function hasUniqueTranslationConstraint(ClassMetadata $classMetadata, $name)
    {
        if (!isset($classMetadata->table['uniqueConstraints'])) {
            return;
        }

        $constraints = array_filter($classMetadata->table['uniqueConstraints'], function($constraint) use ($name) {
            return $name === $constraint['name'];
        });

        return 0 !== count($constraints);
    }

    /**
     * Checks if entity is translatable
     *
     * @param ClassMetadata $classMetadata
     * @param bool          $isRecursive   true to check for parent classes until found
     *
     * @return boolean
     */
    private function isTranslatable(\ReflectionClass $reflClass, $isRecursive = false)
    {
        $isSupported = $reflClass->hasProperty('translations');

        while ($isRecursive and !$isSupported and $reflClass->getParentClass()) {
            $reflClass   = $reflClass->getParentClass();
            $isSupported = $this->isTranslatable($reflClass, true);
        }

        return $isSupported;
    }

    private function isTranslation(ClassMetadata $classMetadata)
    {
        return $classMetadata->reflClass->hasProperty('translatable');
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $em            = $eventArgs->getEntityManager();
        $entity        = $eventArgs->getEntity();
        $classMetadata = $em->getClassMetadata(get_class($entity));

        if (!$classMetadata->reflClass->hasMethod('setCurrentLocale')) {
            return;
        }

        if ($locale = $this->getCurrentLocale()) {
            $entity->setCurrentLocale($locale);
        }
    }

    /**
     * Create the translations on the other accepted locales
     * Persist the translations
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $em     = $eventArgs->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $entity = $eventArgs->getEntity();

        $classMetadata = $em->getClassMetadata(get_class($entity));

        if ( $this->isTranslatable($classMetadata->reflClass, true) ) {

            // get metadata from the translation
            $translationEntity        = $entity->translate();
            $translationClassMetadata = $em->getClassMetadata(get_class($translationEntity));

            // get fields to copy to the other translations
            $fieldsToCopy = [];
            foreach ( $translationClassMetadata->getReflectionProperties() as $refClass ) {

                if ( $this->reader->getPropertyAnnotation($refClass, self::ANNOTATION_CLASS)) {
                    $method = 'get'.ucfirst($refClass->name);
                    $fieldsToCopy[ $refClass->name ] = $translationEntity->$method();
                }
            }

            // create the translation in the other locales
            foreach ( $this->acceptedLocales as $acceptedLocale ) {

                if ( $acceptedLocale != $entity->getCurrentLocale() ) {

                    // create translation
                    $newTranslation = $entity->translate($acceptedLocale);

                    // copy the values of the fields
                    foreach ($fieldsToCopy as $field => $value) {
                        $getMethod = 'get'.ucfirst($field);

                        // copy if it has no value
                        if ( !$newTranslation->$getMethod() ) {
                            $setMethod = 'set'.ucfirst($field);
                            $newTranslation->$setMethod( $value );
                        }
                    }
                }
            }

            // persist all translations
            $entity->mergeNewTranslations();
        }
    }

    private function getCurrentLocale()
    {
        if ($currentLocaleCallable = $this->currentLocaleCallable) {
            return $currentLocaleCallable();
        }
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::loadClassMetadata,
            Events::postLoad,
        ];
    }
}
