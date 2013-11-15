Yii Extension: I18nColumns
==========================

Transparent language/locale-dependent attributes and relations for ActiveRecords, without using lookup tables for translated field contents.

Features
--------

 * Eases the creation of multilingual ActiveRecords in a project
 * Automatically loads the application language by default
 * Translations are stored directly in the model using separate columns for each language
 * Console command automatically creates migrations for the necessary database changes
 * Leverages Gii code generation to provide CRUD for translation work
 * Not only translations - any attribute or relation that is dependent on language or locale can be managed with this extension

Requirements
------------------

 * Yii 1.1 or above
 * Use of Yii console
 * Use of Gii (preferably [Gtc](https://github.com/schmunk42/gii-template-collection/))
 * MySQL 5.1.10+, SQL Server 2012 or similarly recent database (For the console command. The behavior itself works with any Yii-supported database)

Setup
-----

### Download and install

Ensure that you have the following in your composer.json:

    "repositories":[
        {
            "type": "vcs",
            "url": "https://github.com/neam/yii-i18n-columns"
        },
        ...
    ],
    "require":{
        "neam/yii-i18n-columns":"@dev",
        ...
    },

Then install through composer:

    php composer.php install neam/yii-i18n-columns

If you don't use composer, clone or download this project into /path/to/your/app/vendor/neam/yii-i18n-columns

### Add Alias to both main.php and console.php
    'aliases' => array(
        ...
        'vendor'  => dirname(__FILE__) . '/../../vendor',
        'i18n-columns' => 'vendor.neam.yii-i18n-columns',
        ...
    ),

### Import the behavior in main.php

    'import' => array(
        ...
        'i18n-columns.behaviors.I18nColumnsBehavior',
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
            'i18n-columns' => array(
                 'class' => 'I18nColumnsBehavior',
                 /* The multilingual attributes */
                 'translationAttributes' => array(
                      'title',
                      'slug',
                      'image_id',
                      'etc',
                 ),
                /* Specify multilingual belongsTo relations in the form 'RelatedModel' => array('relationName' => 'foreignKey') */
                'multilingualRelations' => array(
                    'Image' => array('image' => 'image_id'),
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

...can be found in tests/unit/I18nColumnsTest.php

Changelog
---------

### 0.3.1 (development release)

- Command action to remove columns, related to an unused language, from the schema

### 0.3.0 (development release)

- Virtual access to multilingual foreign keys (ie $model->relationName is mapped to $model->relationNameId{Lang})
- Command action to remove columns, related to an unused language, from the schema
- Some bug fixes

### 0.2.0 (latest stable)

- Supporting multilingual foreign keys
- Incremental addition of new i18n columns as more languages are added
- Source code is formatted according to the PSR2 standard

### 0.1.0

- Renamed to I18nColumns (to clarify the underlying concept)
- More accurate model detection (not searching model source files for a hard-coded string...)
- Cleaned up (does not contain a complete Yii application, only the necessary extension files)
- Composer support
- Improved instructions directly in README
- Updated to work with Yii 1.1.13
- Unit tests

### 0.0.0

- Forked [https://github.com/firstrow/STranslateableBehavior](https://github.com/firstrow/STranslateableBehavior)

Credits
-------

- [@firstrow](https://github.com/firstrow) for creating STranslateableBehavior which introduced the concept of column-based i18n for Yii
- [@mikehaertl](https://github.com/mikehaertl) for [the getter/setter logic](https://github.com/mikehaertl/translatable/blob/master/Translatable.php#L60)
- [@schmunk42](https://github.com/schmunk42), [@tonydspaniard](https://github.com/tonydspaniard) and [@Crisu83](https://github.com/Crisu83) for advice and healthy critique
- [@clevertech](https://github.com/clevertech) for initial tests directory structure

FAQ
---

### Why use suffixed columns instead of one or many lookup tables?

#### 1. Compatibility with Gii and other Yii extensions

Your multilingual models will keep working as ordinary models, albeit with more fields than before. You can generate new CRUD and instantly have a translation interface for all your languages.

This means that **you will quickly be able to add multilingual content earlier in the development cycle**.

Having a simple multilingual datamodel most likely means better compatibility with other extensions offering magic tooling, such as saving many-many relations, providing search/filtering features, form-generators, editable grid views, etc. It is our experience that these extensions need more custom fitting the higher the amount of joins necessary to show relevant information.

#### 2. You are no longer dependent on magic tooling (such as ActiveRecord)

There is no need to create advanced join-helpers to access the translated attributes, they are simply attributes in the table to begin with. Thus, creating SQL to interact with translations is very straightforward:

`SELECT id, title_en AS title FROM book WHERE title_en = 'The Alchemist';`

This may not be a notable difference when you prototype your application, but becomes more important when you move away from ActiveRecord and write queries using QueryBuilder, pure SQL, or in a barebone PHP/C layer/app side by side with your Yii application (for performance reasons).

#### 3. Matter of taste scalability-wise

Let's pone that we have 40 languages and 300.000 *book* records with 8 translatable fields each, as well as 2 million *chapter* records, with 4 translatable fields each.

#### What do you prefer?

##### A. Suffixed columns

    Table book with 320 columns and 300.000 records
    Table chapter with 160 columns and 2 million records

##### B. One translation table for book and one for chapter

    Table book with 8 columns and 300.000 records
    Table chapter with 4 columns and 2 million records
    Table book_translation with 3 columns and 96 million records
    Table chapter_translation with 3 columns and 320 million records

##### C. One translation table for all records:

    Table book with 8 columns and 300.000 records
    Table chapter with 4 columns and 2 million records
    Table translation with 3 columns and 416 million records

#### 4. Decreased complexity = Flexibility

Say you have a query that without translated fields requires 7 joins, a group by clause and 1-2 subqueries. Then add the necessity to add one more join for each translated field and still achieve a high-performant query. It certainly is possible, but with the cost of added complexity.

In essence, the concept of having suffixed columns instead of translation table(s) is similar to the concept of code generation. You add extra complexity to generate the code/datamodel and receive the benefit of a code base that is easier to maintain and customize.

#### 5. Why not?

Despite #1-4 above, this approach does not fit the bill for most projects. It can be seen as anti-quick and too simplistic to be a truly robust solution.

For instance, if you actually have as many translations as noted in #3 above, you'd probably be better of with a single translation table stored in a key-value-based NoSQL solution. Then, however, you will no longer be able to join the translated data into SQL-queries directly (for whatever that's worth), and handling translations is probably best done using crowd-sourced platforms than Yii-powered backends.

Also, when the system should be developer independent once shipped, adding languages on-the-fly would need either to be done by adding "all" languages to the datamodel from the beginning (and then merely activating more languages as you go along), or automated with a script similar to (to add the extra columns to the models):

    #!/bin/bash
    ./yiic i18n-columns
    ./yiic migrate
    curl https://.…gii-generate-base-models

In general, it will not fit larger projects that are aimed at high-performing stable frontends, but will be more suitable when the main priority is to quickly build feature-complete backends for multilingual content, for which the data model changes often. Migrations and CRUD generation is a part of the daily workflow while developing such solutions, and simplicity and easy customization of the generated code often more important than developer independence.

Then again, this extension is written to be as similar as possible to [mike's translatable behavior](https://github.com/mikehaertl/translatable) in usage and configuration, making it easy to later migrate to a translation-table-based approach after initial prototyping.

What other reasons do you have to not use this horizontal approach to multilingual tables? We'd love to hear your views, [open an issue](https://github.com/neam/yii-i18n-columns/issues) and tell us! :)

Running tests
-------------

    cd vendor/neam/yii-i18n-columns
    php path/to/composer.phar install --dev
    cd tests
    ../vendor/bin/phpunit --verbose --debug

This should result in an output similar to:

	PHPUnit 3.7.22 by Sebastian Bergmann.

	Configuration read from /path/to/app/vendor/neam/yii-i18n-columns/tests/phpunit.xml


	Starting test 'I18nColumnsTest::ensureEmptyDb'.
	.
	Starting test 'I18nColumnsTest::getWithoutSuffix'.
	.
	Starting test 'I18nColumnsTest::setWithoutSuffix'.
	.
	Starting test 'I18nColumnsTest::saveSingleWithoutSuffix'.
	.
	Starting test 'I18nColumnsTest::fetchSingleWithoutSuffix'.
	.
	Starting test 'I18nColumnsTest::saveMultiple'.
	.

	Time: 0 seconds, Memory: 10.00Mb

	OK (6 tests, 28 assertions)