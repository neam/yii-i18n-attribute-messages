Yii Extension: I18nAttributeMessages
==========================

Transparent attribute translation for ActiveRecords, leveraging Yii's built-in translation features to retrieve translated attribute contents.

All you'll need to do is to rename the fields from `$book->title` to `$book->_title` in your database. The included console command scans your database and configuration and creates a migration for all necessary renames.

The behavior then transparently turns `$book->title` into `Yii:t('attributes.Book.title', $book->_title)` and `$book->title_de` into `Yii:t('attributes.Book.title', $book->_title, array(), null, 'de')`, while providing transparent saving of translations simply by assigning and saving these attributes in the model (Note: CDbMessageSource only).

Features
--------

 * Eases the translation of user-generated content in a project
 * Eases the creation of UI for translators to perform translation work
 * Works with any Yii-compatible message source when retrieving translations
 * Saving of translations when using CDbMessageSource
 * Console command automatically creates migrations for the necessary database changes
 * The source message is left in the model for Gii compatibility (generated models will have the correct validation rules and field order for the translated attributes)
 * Rigorous unit tests
 * Use with any number of attributes/languages without worrying about database restrictions on row size and/or column counts being exceeded

Limitations
-------------
Not ideal for translated attributes that are supposed to be native in the active records' database tables, such as translated foreign keys, or multilingual look-up/search columns. Use [https://github.com/neam/yii-i18n-columns](https://github.com/neam/yii-i18n-columns) for those attributes instead.

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
        "neam/yii-i18n-attribute-messages":"dev-master",
        ...
    },

Then install through composer:

    php composer.phar update neam/yii-i18n-attribute-messages

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


### Reference the console command in console.php

    'commandMap' => array(
        ...
        'i18n-attribute-messages'    => array(
            'class' => 'i18n-attribute-messages.commands.I18nAttributeMessagesCommand',
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
                      'book_id',
                      //'etc',
                 ),

                /* An array of allowed language/locale ids that are to be used as suffixes, such as title_en, title_de etc */
                //'languageSuffixes' => array_keys(Yii::app()->params["languages"]),

                /* Configure if you want to use another translation component for this behavior. Default is 'messages' */
                //'messageSourceComponent' => 'attributeMessages',

            ),
        );
    }

#### 2. Create migration from command line:

    ./yiic i18n-attribute-messages process

Run with `--verbose` to see more detailed output.

#### 3. Apply the generated migration:

    ./yiic migrate

This will rename the fields that are defined in translationAttributes to _fieldname, which will be the placed that the source content is stored (the content that is to be translated).

Sample migration file:

	<?php
    class m131115_204413_i18n extends CDbMigration
    {
        public function up()
        {
            $this->renameColumn('book', 'title', '_title');
            $this->renameColumn('book', 'slug', '_slug');
            $this->renameColumn('chapter', 'title', '_title');
            $this->renameColumn('chapter', 'slug', '_slug');
            $this->dropForeignKey('fk_chapter_book', 'chapter');
            $this->renameColumn('chapter', 'book_id', '_book_id');
            $this->addForeignKey('fk_chapter_book', 'chapter', '_book_id', 'book', 'id', 'NO ACTION', 'NO ACTION');
        }

        public function down()
        {
          $this->renameColumn('book', '_title', 'title');
          $this->renameColumn('book', '_slug', 'slug');
          $this->renameColumn('chapter', '_title', 'title');
          $this->renameColumn('chapter', '_slug', 'slug');
          $this->dropForeignKey('fk_chapter_book', 'chapter');
          $this->renameColumn('chapter', '_book_id', 'book_id');
          $this->addForeignKey('fk_chapter_book', 'chapter', 'book_id', 'book', 'id', 'NO ACTION', 'NO ACTION');
        }
    }

#### 4. Add save-support

Save-support is only enabled if you use CDbMessageSource. Configure your app to use it and make sure the following tables (Note: with auto-increment for SourceMessage) exists:

    CREATE TABLE `SourceMessage` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `category` VARCHAR(32) NULL DEFAULT NULL,
      `message` TEXT NULL DEFAULT NULL,
      PRIMARY KEY (`id`))
    COLLATE = utf8_bin;

    CREATE TABLE `Message` (
      `id` INT(11) NOT NULL DEFAULT '0',
      `language` VARCHAR(16) NOT NULL DEFAULT '',
      `translation` TEXT NULL DEFAULT NULL,
      PRIMARY KEY (`id`, `language`),
      CONSTRAINT `FK_Message_SourceMessage`
        FOREIGN KEY (`id`)
        REFERENCES `SourceMessage` (`id`)
        ON DELETE CASCADE)
    COLLATE = utf8_bin;

Hint: You can still keep CPhpMessageSource as the default messages component for your app, and configure CDbMessageSource to be used only for attribute messages.

Your application config should have two message source components configured:

    ...
        // Static messages
        'messages' => array(
            'class' => 'CPhpMessageSource',
        ),
        // Attribute messages
        'attributeMessages' => array(
            'class' => 'CDbMessageSource',
        ),
    ...

And when configuring the behavior, set an appropriate 'messageSourceComponent' configuration option (see example configuration above).

#### 5. Re-generate models

Use Gii as per the official documentation. To be able to save translations, you'll need to generate the models Message and SourceMessage as well.

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
     $book->save(); // Saves 'Djävulen bär Prada' as if it was assigned to Book.title_sv

### Saving multiple translations

     $book->title_en = 'The Devil Wears Prada';
     $book->title_sv = 'Djävulen bär Prada';
     $book->save(); // Saves both translations

### More examples

...can be found in tests/codeception/unit/BasicTest.php

Changelog
---------

### 0.1.0

- Eases the translation of user-generated content in a project
- Eases the creation of UI for translators to perform translation work
- Works with any Yii-compatible message source when retrieving translations
- Saving of translations when using CDbMessageSource
- Console command automatically creates migrations for the necessary database changes
- The source message is left in the model for Gii compatibility (generated models will have the correct validation rules and field order for the translated attributes)
- Rigorous unit tests

### 0.0.0

- Forked [https://github.com/neam/yii-i18n-columns](https://github.com/neam/yii-i18n-columns) v0.3.1

Testing the extension
-------------

### One-time preparations

Switch to the extension's root directory

    cd vendor/neam/yii-i18n-attribute-messages

Create a database called yiam_test in your local mysql server installation. Create a user called yiam_test with yiam_test as the password and make sure that this user has access to the local database.

After this, you can run the following routine to test the extension:

### Test the command

#### 1. Set-up the test database

Load tests/db/unmodified.sql into the database.

#### 2. Run the console command

    tests/app/protected/yiic i18n-attribute-messages process

#### 3. Apply the migration

    tests/app/protected/yiic migrate

### Test the behavior

Run the unit tests

    php codecept.phar run unit

You should get output similar to:

    Codeception PHP Testing Framework v1.6.2
    Powered by PHPUnit 3.7.19 by Sebastian Bergmann.

    Suite unit started
    Trying to ensure empty db (BasicTest::ensureEmptyDb) - Ok
    Trying to ensure known source language (BasicTest::ensureKnownSourceLanguage) - Ok
    Trying to see behavior (BasicTest::seeBehavior) - Ok
    Trying to interpret language suffix (BasicTest::interpretLanguageSuffix) - Ok
    Trying to get (BasicTest::get) - Ok
    Trying to set without suffix (BasicTest::setWithoutSuffix) - Ok
    Trying to set with suffix (BasicTest::setWithSuffix) - Ok
    Trying to save single with source message (BasicTest::saveSingleWithSourceMessage) - Ok
    Trying to save single without source message (BasicTest::saveSingleWithoutSourceMessage) - Ok
    Trying to fetch single without suffix (BasicTest::fetchSingleWithoutSuffix) - Ok
    Trying to reuse previous translation (BasicTest::reusePreviousTranslation) - Ok
    Trying to update existing (BasicTest::updateExisting) - Ok
    Trying to further fallback behavior tests (BasicTest::furtherFallbackBehaviorTests) - Ok
    Trying to test test suite (EmptyTest::testTestSuite) - Ok


    Time: 0 seconds, Memory: 14.25Mb

    OK (14 tests, 124 assertions)