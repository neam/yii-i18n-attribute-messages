Yii Extension: I18nAttributeMessages
==========================

Transparent attribute translation for ActiveRecords, leveraging Yii's built-in translation features for translated field contents

Features
--------

 * Eases the creation of multilingual ActiveRecords in a project
 * Automatically loads the application language by default
 * Translations are stored directly in the model using separate columns for each language
 * Console command automatically creates migrations for the necessary database changes
 * Leverages Gii code generation to provide CRUD for translation work

Requirements
------------------

 * Yii 1.1 or above
 * Use of Yii console

Setup
-----

### Download and install

Ensure that you have the following in your composer.json:

    "repositories":[
        {
            "type": "vcs",
            "url": "https://github.com/neam/yii-i18n-attribute-messages"
        },
        ...
    ],
    "require":{
        "neam/yii-i18n-attribute-messages":"@dev",
        ...
    },

Then install through composer:

    php composer.php install neam/yii-i18n-attribute-messages

If you don't use composer, clone or download this project into /path/to/your/app/vendor/neam/yii-i18n-attribute-messages

### Add Alias to both main.php and console.php
    'aliases' => array(
        ...
        'vendor'  => dirname(__FILE__) . '/../../vendor',
        'i18n-attribute-messages' => 'vendor.neam.yii-i18n-attribute-messages',
        ...
    ),

### Import the behavior in main.php

    'import' => array(
        ...
        'i18n-attribute-messages.behaviors.I18nAttributeMessagesBehavior',
        ...
    ),


### Reference the translate command in console.php

    'commandMap' => array(
        ...
        'i18n-columns'    => array(
            'class' => 'i18n-columns.commands.I18nColumnsCommand',
        ),
        ...
    ),


### Configure models to be multilingual

#### 1. Add the behavior to the models that you want multilingual

    public function behaviors()
    {
        return array(
            'i18n-attribute-messages' => array(
                 'class' => 'I18nAttributeMessagesBehavior',
                 /* The multilingual attributes */
                 'translationAttributes' => array(
                      'title',
                      'slug',
                      'image_id',
                      'etc',
                 ),
            ),
        );
    }

#### 2. Create migration from command line:

`./yiic i18n-columns process`

Prior to this, you should already have configured a default language (`$config['language']`) and available languages (`$config['components']['langHandler']['languages']`) for your app.

Run with `--verbose` to see more detailed output.

#### 3. Apply the generated migration:

`./yiic migrate`

This will rename the fields that are defined in translationAttributes to fieldname_defaultlanguagecode and add columns for the remaining languages.

Sample migration file:

	<?php
	class m130708_165204_i18n extends CDbMigration
	{
	    public function up()
	    {
		$this->renameColumn('section', 'title', 'title_en');
		$this->renameColumn('section', 'slug', 'slug_en');
		$this->addColumn('section', 'title_sv', 'varchar(255) null');
		$this->addColumn('section', 'slug_sv', 'varchar(255) null');
		$this->addColumn('section', 'title_de', 'varchar(255) null');
		$this->addColumn('section', 'slug_de', 'varchar(255) null');
	    }

	    public function down()
	    {
	      $this->renameColumn('section', 'title_en', 'title');
	      $this->renameColumn('section', 'slug_en', 'slug');
	      $this->dropColumn('section', 'title_sv');
	      $this->dropColumn('section', 'slug_sv');
	      $this->dropColumn('section', 'title_de');
	      $this->dropColumn('section', 'slug_de');
	    }
	}

#### 4. Re-generate models

Use Gii as per the official documentation. After this, you have multilingual Active Records at your disposal :)

Usage
-----

Example usage with a Book model that has a multilingual *title* attribute.

All translations will be available through attribute suffix, ie `$book->title_en` for the english translation, `$book->title_sv` for the swedish translation. `$book->title` will be an alias for the currently selected language's translation.

### Fetching translations

     $book = Book::model()->findByPk(1);
     Yii::app()->language = 'en';
     echo $book->title; // Outputs 'The Alchemist'
     Yii::app()->language = 'sv';
     echo $book->title; // Outputs 'Alkemisten'
     echo $book->title_en; // Outputs 'The Alchemist'

### Saving a single translation

     Yii::app()->language = 'sv';
     $book->title = 'Djävulen bär Prada';
     $book->save(); // Saves 'Djävulen bär Prada' to Book.title_sv

### Saving multiple translations

     $book->title_en = 'The Devil Wears Prada';
     $book->title_sv = 'Djävulen bär Prada';
     $book->save(); // Saves both translations

### More examples

...can be found in tests/unit/I18nAttributeMessagesTest.php

Changelog
---------

### 0.1.0

-

### 0.0.0

- Forked [https://github.com/neam/yii-i18n-columns](https://github.com/neam/yii-i18n-columns) v0.3.1

