<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'My Console Application',

	// preloading 'log' component
	'preload'=>array('log'),

    // i18n-columns
    'aliases' => array(
        'i18n-columns' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..',
    ),
    'import' => array(
        'i18n-columns.behaviors.I18nColumnsBehavior',
    ),
    'language' => 'en',
    'commandMap' => array(
        'i18n-columns'    => array(
            'class' => 'i18n-columns.commands.I18nColumnsCommand',
        ),
    ),

	// application components
	'components'=>array(
        'db' => array(
            'connectionString' => 'sqlite:'.dirname(__FILE__).'/../../../db/test.db',
        ),
		// uncomment the following to use a MySQL database
		/*
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=testdrive',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => '',
			'charset' => 'utf8',
		),
		*/
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
			),
		),
	),
);