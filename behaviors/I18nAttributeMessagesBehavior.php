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

        // Without suffix
        if (in_array($name, $this->translationAttributes)) {

            $lang = Yii::app()->language;
            if (isset($this->dirtyAttributes[$name . '_' . $lang])) {
                return $this->dirtyAttributes[$name . '_' . $lang];
            }

            $sourceMessageAttribute = "_" . $name;
            $sourceMessageContent = $this->owner->attributes[$sourceMessageAttribute];
            return Yii::t('attributes', $sourceMessageContent);
        }

        // With suffix
        $originalAttribute = $this->getOriginalAttribute($name);
        if (in_array($originalAttribute, $this->translationAttributes)) {

            if (isset($this->dirtyAttributes[$name])) {
                return $this->dirtyAttributes[$name];
            }

            $sourceMessageAttribute = "_" . $originalAttribute;
            $sourceMessageContent = $this->owner->$sourceMessageAttribute;
            $lang = $this->getLanguageSuffix($name);
            return Yii::t('attributes', $sourceMessageContent, array(), null, $lang);
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

    private function getLanguageSuffix($name)
    {
        $_ = explode("_", $name);
        $langsuffix = array_pop($_);
        return $langsuffix;
    }

    private function getOriginalAttribute($name)
    {
        $langsuffix = $this->getLanguageSuffix($name);
        return substr($name, 0, -strlen($langsuffix) - 1);
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

}
