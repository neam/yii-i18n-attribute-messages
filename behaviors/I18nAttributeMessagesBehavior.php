<?php

/**
 * I18nAttributeMessagesBehavior
 *
 * @uses CActiveRecordBehavior
 * @license MIT
 * @author See https://github.com/neam/yii-i18n-attribute-messages/graphs/contributors
 */
class I18nAttributeMessagesBehavior extends CActiveRecordBehavior
{

    /**
     * @var array list of attributes to translate
     */
    public $translationAttributes = array();

    /**
     * @var array list of valid language suffixes - keep empty to disable this feature
     */
    public $languageSuffixes = array();

    /**
     * @var string the message source component to be used with this behavior instance
     */
    public $messageSourceComponent = "messages";

    /**
     * @var array list of attributes that are set, but yet to be saved
     */
    private $dirtyAttributes = array();

    /**
     * Make translated attributes readable, with and without suffix
     */
    public function __get($name)
    {

        if (!$this->handlesProperty($name)) {
            return parent::__get($name);
        }

        if (in_array($name, $this->translationAttributes)) {
            // Without suffix
            $lang = Yii::app()->language;
            $withoutSuffix = $name;
        } else {
            // With suffix
            $lang = $this->getLanguageSuffix($name);
            $withoutSuffix = $this->getOriginalAttribute($name);
        }
        $withSuffix = $withoutSuffix . '_' . $lang;

        if (in_array($withoutSuffix, $this->translationAttributes)) {

            if (isset($this->dirtyAttributes[$withSuffix])) {
                return $this->dirtyAttributes[$withSuffix];
            }

            if ($lang == Yii::app()->sourceLanguage) {
                $sourceMessageAttribute = "_" . $withoutSuffix;
                $sourceMessageContent = $this->owner->attributes[$sourceMessageAttribute];
                return $sourceMessageContent;
            }

            if (is_null($this->getSourceMessage($withoutSuffix))) {
                return null;
            }

            return Yii::t($this->getCategory($withoutSuffix), $this->getSourceMessage($withoutSuffix), array(), $this->messageSourceComponent, $lang);
        }

    }

    /**
     * Make translated attributes writeable, with and without suffix
     */
    public function __set($name, $value)
    {

        if (!$this->handlesProperty($name)) {
            return parent::__set($name, $value);
        }

        // Without suffix
        if (in_array($name, $this->translationAttributes)) {
            $lang = Yii::app()->language;
            $this->dirtyAttributes[$name . '_' . $lang] = $value; // Always store with suffix since we are interested in the language while setting the attribute, not while saving
        }

        // With suffix
        $originalAttribute = $this->getOriginalAttribute($name);
        if (in_array($originalAttribute, $this->translationAttributes)) {
            $this->dirtyAttributes[$name] = $value;
        }

    }

    /**
     * Expose translatable attributes as readable
     */
    public function canGetProperty($name)
    {
        return $this->handlesProperty($name);
    }

    /**
     * Expose translatable attributes as writeable
     */
    public function canSetProperty($name)
    {
        return $this->handlesProperty($name);
    }

    /**
     *
     * @param string $name
     * @return bool
     */
    protected function handlesProperty($name)
    {
        // This behavior handles translationAttributes as specified in the configuration...
        if (in_array($name, $this->translationAttributes)) {
            $lang = Yii::app()->language;
            if (isset($this->dirtyAttributes[$name . '_' . $lang])) {
                return $this->dirtyAttributes[$name . '_' . $lang];
            }
            return true;
        }

        // ... as well as language-code suffixed translationAttributes (title_en)
        $originalAttribute = $this->getOriginalAttribute($name);
        $lang = Yii::app()->language;
        if (isset($this->dirtyAttributes[$originalAttribute . '_' . $lang])) {
            return $this->dirtyAttributes[$originalAttribute . '_' . $lang];
        }
        return in_array($originalAttribute, $this->translationAttributes);

    }

    public function getLanguageSuffix($name)
    {
        $lastTwo = substr($name, 2, -2);
        if (in_array($lastTwo, $this->languageSuffixes)) {
            return $lastTwo;
        }

        $lastFive = substr($name, -5, 5);
        if (in_array($lastFive, $this->languageSuffixes)) {
            return $lastFive;
        }

        $_ = explode("_", $name);
        $langsuffix = array_pop($_);
        return $langsuffix;
    }

    private function getOriginalAttribute($name)
    {
        $langsuffix = $this->getLanguageSuffix($name);
        return substr($name, 0, -strlen($langsuffix) - 1);
    }

    public function getCategory($name)
    {
        return 'attributes.' . get_class($this->owner) . '.' . $name;
    }

    public function getSourceMessage($name)
    {
        return $this->owner->primaryKey;
    }

    /**
     * Mark the multilingual attributes as safe, so that forms that rely
     * on setting attributes from post values works without modification.
     *
     * @param CActiveRecord $owner
     * @throws Exception
     */
    public function attach($owner)
    {
        parent::attach($owner);
        if (!($owner instanceof CActiveRecord)) {
            throw new Exception('Owner must be a CActiveRecord class');
        }

        $validators = $owner->getValidatorList();

        foreach ($this->translationAttributes as $name) {
            $validators->add(CValidator::createValidator('safe', $owner, $name, array()));
        }
    }

    public function afterSave()
    {

        // do nothing if we have nothing to save
        if (empty($this->dirtyAttributes)) {
            return true;
        }

        // format into a structured array of translations to save
        $sourceMessages = array();
        foreach ($this->dirtyAttributes as $keyWithSuffix => $value) {

            $originalAttribute = $this->getOriginalAttribute($keyWithSuffix);
            $language = $this->getLanguageSuffix($keyWithSuffix);

            // Do not save translations in sourceLanguage
            if ($language != Yii::app()->sourceLanguage) {
                continue;
            }

            $sourceMessage = $this->getSourceMessage($originalAttribute);
            if (!isset($sourceMessages[$sourceMessage])) {
                $sourceMessages[$sourceMessage] = array('category' => $this->getCategory($originalAttribute), 'translations' => array());
            }

            $sourceMessages[$sourceMessage]['translations'][] = array('language' => $language, 'message' => $value);
        }

        // do nothing if we have nothing to save
        if (empty($sourceMessages)) {
            return true;
        }

        // find a suitable method of saving
        $component = Yii::app()->{$this->messageSourceComponent};
        if (method_exists($component, 'saveTranslations')) {

            $component->saveTranslations($sourceMessages);

            // clear the dirty attributes that have now been saved
            $this->dirtyAttributes = array();

            return true;
        }
        if ($component instanceof CPhpMessageSource) {
            throw new CException("Cannot save translations with CPhpMessageSource");
        }
        throw new CException("Cannot save translations with " . get_class(Yii::app()->messages));

    }

    /**
     * Expose the behavior
     */
    public function getI18nAttributeMessagesBehavior()
    {
        return $this;
    }

}
