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
        'i18n-attribute-messages' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..',
    ),
    'import' => array(
        'i18n-attribute-messages.behaviors.I18nAttributeMessagesBehavior',
    ),
    'language' => 'en',
    'commandMap' => array(
        'i18n-attribute-messages'    => array(
            'class' => 'i18n-attribute-messages.commands.I18nAttributeMessagesCommand',
        ),
    ),

	// application components
	'components'=>array(
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=yiam_test',
			'emulatePrepare' => true,
			'username' => 'yiam_test',
			'password' => 'yiam_test',
			'charset' => 'utf8',
		),
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