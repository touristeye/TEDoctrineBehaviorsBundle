<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TE\DoctrineBehaviorsBundle\Model\Translatable;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Translatable trait.
 *
 * Should be used inside entity, that needs to be translated.
 */
trait Translatable
{
    /**
     * Will be mapped to translatable entity
     * by TranslatableListener
     */
    private $translations;

    /**
     * Will be merged with persisted translations on mergeNewTranslations call
     *
     * @see mergeNewTranslations
     */
    private $newTranslations;

    /**
     * currentLocale is a non persisted field configured during postLoad event
     */
    private $currentLocale;

    /**
     * acceptedLocales is a non persisted field configured during postLoad event
     */
    private $acceptedLocales;

    /**
     * Returns collection of translations.
     *
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations = $this->translations ?: new ArrayCollection();
    }

    /**
     * Returns collection of new translations.
     *
     * @return ArrayCollection
     */
    public function getNewTranslations()
    {
        return $this->newTranslations = $this->newTranslations ?: new ArrayCollection();
    }

    /**
     * Adds new translation.
     *
     * @param Translation $translation The translation
     */
    public function addTranslation($translation)
    {
        $this->getTranslations()->set($translation->getLocale(), $translation);
        $translation->setTranslatable($this);
    }

    /**
     * Removes specific translation.
     *
     * @param Translation $translation The translation
     */
    public function removeTranslation($translation)
    {
        $this->getTranslations()->removeElement($translation);
    }

    /**
     * Returns translation for specific locale (creates new one if doesn't exists).
     * If requested translation doesn't exist, it will first try to fallback default locale
     * If any translation doesn't exist, it will be added to newTranslations collection.
     * In order to persist new translations, call mergeNewTranslations method, before flush
     *
     * @param string $locale The locale (en, ru, fr) | null If null, will try with current locale
     *
     * @return Translation
     */
    public function translate($locale = null)
    {
        return $this->doTranslate($locale);
    }

    protected function doTranslate($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getCurrentLocale();
        }

        $translation = $this->findTranslationByLocale($locale);
        if ($translation and !$translation->isEmpty()) {
            return $translation;
        }

        if ($defaultTranslation = $this->findTranslationByLocale($this->getDefaultLocale(), false)) {
            return $defaultTranslation;
        }

        $class       = self::getTranslationEntityClass();
        $translation = new $class();
        $translation->setLocale($locale);

        $this->getNewTranslations()->set($translation->getLocale(), $translation);
        $translation->setTranslatable($this);

        return $translation;
    }

    /**
     * Merges newly created translations into persisted translations.
     */
    public function mergeNewTranslations()
    {
        foreach ($this->getNewTranslations() as $newTranslation) {
            if (!$this->getTranslations()->contains($newTranslation)) {
                $this->addTranslation($newTranslation);
                $this->getNewTranslations()->removeElement($newTranslation);
            }
        }
    }

    /**
     * @param mixed $locale the current locale
     */
    public function setCurrentLocale($locale)
    {
        $this->currentLocale = $locale;
    }

    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->getDefaultLocale();
    }

    public function getDefaultLocale()
    {
        return 'en';
    }

    /**
     * @param array $acceptedLocales the accepted locales
     */
    public function setAcceptedLocales($acceptedLocales)
    {
        $this->acceptedLocales = $acceptedLocales;
    }

    public function getAcceptedLocales()
    {
        return $this->acceptedLocales ?: array($this->getDefaultLocale());
    }

    protected function proxyCurrentLocaleTranslation($method, array $arguments = [])
    {
        return call_user_func_array(
            [$this->translate($this->getCurrentLocale()), $method],
            $arguments
        );
    }

    /**
     * Returns translation entity class name.
     *
     * @return string
     */
    public static function getTranslationEntityClass()
    {
        return __CLASS__.'Translation';
    }

    /**
     * Finds specific translation in collection by its locale.
     *
     * @param string $locale              The locale (en, ru, fr)
     * @param bool   $withNewTranslations searched in new translations too
     *
     * @return Translation|null
     */
    protected function findTranslationByLocale($locale, $withNewTranslations = true)
    {
        $translation = $this->getTranslations()->get($locale);

        if ($translation) {
            return $translation;
        }

        if ($withNewTranslations) {
            return $this->getNewTranslations()->get($locale);
        }
    }

    /**
     * Execute the method in the translation object
     *
     * @param string $method
     * @param array $arguments
     */
    public function __call($method, $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }
}
