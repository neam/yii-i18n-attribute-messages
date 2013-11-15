<?php

/**
 * This is temporary harness to support current code which is tightly coupled to Yii application object.
 * It should be called once before each test, and instantiates our minimal CApplication object.
 */
// Include Yii
define('YII_PATH', __DIR__ . '/../vendor/yiisoft/yii/framework');
require_once(YII_PATH . '/YiiBase.php');
require_once(__DIR__ . '/fakes/Yii.php');

// Set up the shorthands for test app paths
define('APP_ROOT', realpath(__DIR__));
define('APP_RUNTIME', realpath(APP_ROOT . '/runtime'));
define('APP_ASSETS', realpath(APP_ROOT . '/assets'));

// Instantiated the test app
require_once(__DIR__ . '/fakes/MinimalApplication.php');
Yii::createApplication(
    'MinimalApplication',
    array(
        'aliases' => array(
            'tests' => APP_ROOT,
            'i18n-columns' => APP_ROOT . '/..',
        ),
        'import' => array(
            'i18n-columns.behaviors.I18nColumnsBehavior',
        ),
        'language' => 'en',
        'basePath' => APP_ROOT,
        'runtimePath' => APP_RUNTIME,
        'components' => array(
            'db' => array(
                'connectionString' => 'sqlite:' . APP_ROOT . '/db/test.db',
            ),
            'assetManager' => array(
                'basePath' => APP_ASSETS // do not forget to clean this folder sometimes
            )
        )
    )
);

// See the `Boostrap.init()` method for explanation why it is needed
define('IS_IN_TESTS', true);



