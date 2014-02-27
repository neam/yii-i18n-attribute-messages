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
     * @var string
     */
    public $name = 'i18n-attribute-messages';

    /**
     * @var array list of attributes to translate
     */
    public $translationAttributes = array();

    /**
     * @var array list of valid language suffixes - keep empty to disable this feature
     */
    public $languageSuffixes = array();

    /**
     * @var string the message source component to be used for displaying messages with this behavior instance
     */
    public $displayedMessageSourceComponent = "messages";

    /**
     * @var string the message source component to be used for editing messages with this behavior instance
     */
    public $editedMessageSourceComponent = "messages";

    /**
     * @var array list of attributes that are set, but yet to be saved
     */
    private $dirtyAttributes = array();

    /**
     * @var bool whether to enable to use translation fallbacks
     */
    public $messageSourceComponent;

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

            return Yii::t(
                $this->getCategory($withoutSuffix),
                $this->getSourceMessage($withoutSuffix),
                array(),
                $this->messageSourceComponent,
                $lang
            );
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

        if ($lang == Yii::app()->sourceLanguage) {
            $sourceMessageAttribute = "_" . $withoutSuffix;
            $this->owner->$sourceMessageAttribute = $value;
            return true;
        }

        // Without suffix
        if (in_array($withoutSuffix, $this->translationAttributes)) {
            $this->dirtyAttributes[$withSuffix] = $value; // Always store with suffix since we are interested in the language while setting the attribute, not while saving
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

    public function getCategory($originalAttribute)
    {
        return substr('a-' . get_class($this->owner) . '-' . $originalAttribute, 0, 32);
    }

    public function getSourceMessage($originalAttribute)
    {
        /*
        return $this->owner->primaryKey;
        */
        $sourceLanguageAttribute = '_' . $originalAttribute;
        return $this->owner->$sourceLanguageAttribute;
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

        $this->messageSourceComponent = $this->displayedMessageSourceComponent;
    }

    public function afterSave($event)
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
            if ($language == Yii::app()->sourceLanguage) {
                continue;
            }

            $sourceMessage = $this->getSourceMessage($originalAttribute);
            if (!isset($sourceMessages[md5($sourceMessage)])) {
                $sourceMessages[md5($sourceMessage)] = array(
                    'message' => $sourceMessage,
                    'category' => $this->getCategory($originalAttribute),
                    'translations' => array()
                );
            }

            $sourceMessages[md5($sourceMessage)]['translations'][] = array(
                'language' => $language,
                'translation' => $value
            );
        }

        // do nothing if we have nothing to save
        if (empty($sourceMessages)) {
            return true;
        }

        // find a suitable method of saving
        $component = Yii::app()->{$this->messageSourceComponent};
        if (method_exists($component, 'saveTranslations')) {

            $component->saveTranslations($sourceMessages);

            return $this->afterSavingTranslations();
        }
        if ($component instanceof CPhpMessageSource) {
            throw new CException("Cannot save translations with CPhpMessageSource");
        }
        if ($component instanceof CDbMessageSource) {

            // save the translations
            foreach ($sourceMessages as $sourceMessage) {

                $attributes = array('category' => $sourceMessage['category'], 'message' => $sourceMessage['message']);
                if (($model = SourceMessage::model()->find(
                        'message=:message AND category=:category',
                        $attributes
                    )) === null
                ) {
                    $model = new SourceMessage();
                    $model->attributes = $attributes;
                    if (!$model->save()) {
                        throw new CException('Attribute source message ' . $attributes['category'] . ' - ' . $attributes['message'] . ' could not be added to the SourceMessage table. Errors: ' . print_r(
                            $model->errors,
                            true
                        ));
                    }
                }
                if ($model->id) {
                    foreach ($sourceMessage['translations'] as $translation) {
                        $attributes = array('id' => $model->id, 'language' => $translation['language']);
                        if (($messageModel = Message::model()->find(
                                'id=:id AND language=:language',
                                $attributes
                            )) === null
                        ) {
                            $messageModel = new Message;
                        }
                        $messageModel->id = $attributes['id'];
                        $messageModel->language = $attributes['language'];
                        $messageModel->translation = $translation['translation'];
                        if (!$messageModel->save()) {
                            throw new CException('Attribute message ' . $attributes['category'] . ' - ' . $attributes['message'] . ' - ' . $language . ' - ' . $value . ' could not be saved to the Message table. Errors: ' . print_r(
                                $messageModel->errors,
                                true
                            ));
                        }
                    }
                }
            }

            return $this->afterSavingTranslations();
        }

        throw new CException("Cannot save translations with " . get_class(Yii::app()->messages));
    }

    protected function afterSavingTranslations()
    {
        // Clear the dirty attributes that have now been saved
        $this->dirtyAttributes = array();

        // We need to reset the messages component to prevent stale data on next usage
        Yii::app()->setComponent($this->messageSourceComponent, null);

        return true;
    }

    public function edited()
    {
        $clone = clone $this;
        $ownerClone = clone $this->owner;
        $ownerClone->attachBehavior($this->name, $clone);
        $ownerClone->asa($this->name)->messageSourceComponent = $this->editedMessageSourceComponent;
        return $ownerClone;
    }

    /**
     * Expose the behavior
     */
    public function getI18nAttributeMessagesBehavior()
    {
        return $this;
    }
}
