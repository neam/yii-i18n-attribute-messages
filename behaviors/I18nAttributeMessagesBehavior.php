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
     * Make translated attributes readable, with and without suffix
     */
    public function __get($name)
    {

        if (!$this->handlesProperty($name)) {
            return parent::__get($name);
        }

        if (in_array($name, $this->translationAttributes)) {
            $sourceMessageAttribute = "_" . $name;
            $sourceMessageContent = $this->owner->$sourceMessageAttribute;
            return Yii::t('attributes', $sourceMessageContent);
        }

        $langsuffix = $this->getLanguageSuffix($name);
        $originalAttribute = $this->getOriginalAttribute($name);
        if (in_array($originalAttribute, $this->translationAttributes)) {
            return Yii::t('attributes', $this->owner->attributes["_" . $originalAttribute], array(), null, $langsuffix);
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

        $translatedAttribute = $name . '_' . Yii::app()->language;
        if (array_key_exists($translatedAttribute, $this->owner->attributes)) {
            $this->owner->$translatedAttribute = $value;
            return;
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
        return false;
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
            return true;
        }

        // ... as well as language-code suffixed translationAttributes (title_en)
        $originalAttribute = $this->getOriginalAttribute($name);
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
