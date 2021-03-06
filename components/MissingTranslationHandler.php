<?php

/**
 * Attach (permanently or temporarily) to the message source component's
 * onMissingTranslation event to alter the return value when no translation exists
 *
 * Class MissingTranslationHandler
 */
class MissingTranslationHandler
{
    /**
     * Simply returns null on missing translation. Use in translation UI forms
     * where the field for translations are not to be pre-filled with the source message.
     *
     * @param $event
     * @return mixed
     */
    static public function returnNull($event)
    {
        $event->message = null;
        return $event;
    }

    /**
     * Simplistic fallback returning the contents in the source language on missing translation
     * @param $event
     * @return string
     */
    static public function returnSourceLanguageContents($event)
    {
        $fallback = Yii::t($event->category, $event->message, array(), null, Yii::app()->sourceLanguage);
        return $fallback;
    }
}

