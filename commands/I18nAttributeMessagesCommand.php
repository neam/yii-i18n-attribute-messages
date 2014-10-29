<?php

/**
 * I18nColumnsCommand
 *
 * Finds models that are using I18nColumnsBehavior and creates relevant migrations
 *
 * @uses CConsoleCommand
 * @license MIT
 * @author See https://github.com/neam/yii-i18n-attribute-messages/graphs/contributors
 */

class I18nAttributeMessagesCommand extends CConsoleCommand
{
    /**
     * @var string
     */
    public $migrationPath = 'application.migrations';

    /**
     * @var string
     */
    public $modelAlias = 'application.models';

    /**
     * @var array
     */
    public $models = array();

    /**
     * @var array
     */
    public $languages = array();

    /**
     * Source language
     *
     * @var string
     */
    public $sourceLanguage;

    /**
     * @var array
     */
    public $up = array();

    /**
     * @var array
     */
    public $down = array();

    /**
     * If we should be verbose
     *
     * @var bool
     */
    private $_verbose = false;

    /**
     * Array of columns to create
     *
     * @var array
     */
    public $columns = array();

    /**
     * Write a string to standard output if we're verbose
     *
     * @param $string
     */
    public function d($string)
    {
        if ($this->_verbose) {
            print "\033[37m" . $string . "\033[30m";
        }
    }

    /**
     * Execute the command
     *
     * @param array $args
     * @return bool|int
     */
    private function load()
    {

        // Sqlite check
        if ((Yii::app()->db->schema instanceof CSqliteSchema) !== false) {
            throw new CException("Sqlite does not support adding foreign keys, renaming columns or even add new columns that have a NOT NULL constraint, so this command can not support sqlite. Sorry.");
        }
        $this->models = $this->_getModels();
        if (sizeof($this->models) == 0) {
            throw new CException("Found no models with I18nAttributeMessages behavior attached");
        }
    }

    /**
     * This will rename the fields that are defined in translationAttributes to fieldname_defaultlanguagecode and add columns for the remaining languages.
     * @param $langcode
     */
    public function actionProcess($verbose = false)
    {
        $this->_verbose = $verbose;
        $this->load();

        $this->d("Creating the migration...\n");
        foreach ($this->models as $modelName => $model) {
            $this->d("\t...$modelName: \n");
            $behaviors = $model->behaviors();
            foreach ($behaviors['i18n-attribute-messages']['translationAttributes'] as $attribute) {
                $this->_processAttribute($model, $attribute);
            }
        }

        $this->_createMigrationFile();
    }

    /**
     * Rename columns back and forth for the source language column
     * @param $lang
     * @param $model
     */
    protected function _processAttribute($model, $translationAttribute)
    {
        $from = $translationAttribute;
        $to = '_' . $translationAttribute;

        $this->d("\t\t$from -> $to\n");

        $te = null;
        $fi = null;
        if ($fe = $this->_checkColumnExists($model, $from) && !($te = $this->_checkColumnExists($model, $to))) {

            // Foreign key checks
            $fromFk = $this->attributeFk($model, $from);

            // Remove fks before rename
            if (!is_null($fromFk)) {
                $this->up[] = $this->down[] = '$this->dropForeignKey(\'' . $fromFk["CONSTRAINT_NAME"]
                    . '\', \'' . $model->tableName() . '\');';
            }
            $this->up[] = '$this->renameColumn(\'' . $model->tableName() . '\', \'' . $from
                . '\', \'' . $to . '\');';
            $this->down[] = '$this->renameColumn(\'' . $model->tableName() . '\', \''
                . $to . '\', \'' . $from . '\');';
            // Add fks again after rename
            if (!is_null($fromFk)) {
                $this->up[] = '$this->addForeignKey(\'' . $fromFk["CONSTRAINT_NAME"]
                    . '\', \'' . $model->tableName()
                    . '\', \'' . $to
                    . '\', \'' . $model->metaData->tableSchema->foreignKeys[$from][0]
                    . '\', \'' . $model->metaData->tableSchema->foreignKeys[$from][1]
                    . '\', \'' . $fromFk["rules"]["DELETE_RULE"]
                    . '\', \'' . $fromFk["rules"]["UPDATE_RULE"] . '\');';
                $this->down[] = '$this->addForeignKey(\'' . $fromFk["CONSTRAINT_NAME"]
                    . '\', \'' . $model->tableName()
                    . '\', \'' . $from
                    . '\', \'' . $model->metaData->tableSchema->foreignKeys[$from][0]
                    . '\', \'' . $model->metaData->tableSchema->foreignKeys[$from][1]
                    . '\', \'' . $fromFk["rules"]["DELETE_RULE"]
                    . '\', \'' . $fromFk["rules"]["UPDATE_RULE"] . '\');';
            }
        } else {
            if ($te) {
                $this->d("\t\t\tNote: $to already exists - skipping rename...\n");
            }
            if (!$fe) {
                $this->d("\t\t\tNote: $from doesn't exist - skipping rename...\n");
            }
        }
    }

    /**
     * @param $model
     * @param $column
     * @return bool
     */
    protected function _checkColumnExists($model, $column)
    {
        return isset($model->metaData->columns[$column]);
    }

    /**
     * @param $model
     * @param $column
     * @return string
     */
    protected function _getColumnDbType($model, $column)
    {
        $data = $model->metaData->columns[$column];
        $isNull = $data->allowNull ? "null" : "not null";

        return $data->dbType . ' ' . $isNull;
    }

    protected function attributeFk($model, $attribute)
    {
        $attributeFk = null;
        if (isset($model->metaData->tableSchema->foreignKeys[$attribute])) {

            $attributeFk = Yii::app()->db->createCommand(
                "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = :table_name AND COLUMN_NAME = :column_name"
            )->queryRow(
                    true,
                    array(
                        ':table_name' => $model->tableName(),
                        ':column_name' => $attribute,
                    )
                );

            $attributeFk["rules"] = Yii::app()->db->createCommand(
                "SELECT UPDATE_RULE, DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME = :table_name AND CONSTRAINT_NAME = :constraint_name"
            )->queryRow(
                    true,
                    array(
                        ':table_name' => $model->tableName(),
                        ':constraint_name' => $attributeFk["CONSTRAINT_NAME"],
                    )
                );

        }
        return $attributeFk;
    }

    /**
     * Create migration file
     */
    protected function _createMigrationFile()
    {
        if (count($this->up) == 0) {
            exit("Database up to date\n");
        }

        $migrationName = 'm' . gmdate('ymd_His') . '_i18n';

        $phpCode = '<?php
class ' . $migrationName . ' extends CDbMigration
{
    public function up()
    {
        ' . implode("\n        ", $this->up) . '
    }

    public function down()
    {
      ' . implode("\n      ", $this->down) . '
    }
}' . "\n";

        $migrationsDir = Yii::getPathOfAlias($this->migrationPath);
        if (!realpath($migrationsDir)) {
            die(sprintf('Please create migration directory %s first', $migrationsDir));
        }

        $migrationFile = $migrationsDir . '/' . $migrationName . '.php';
        $f = fopen($migrationFile, 'w') or die("Can't open file");
        fwrite($f, $phpCode);
        fclose($f);

        print "Migration successfully created.\n";
        print "See $migrationName\n";
        print "To apply migration enter: ./yiic migrate\n";
    }

    // Adapted from gii-template-collection / fullCrud / FullCrudCode.php
    protected function _getModels()
    {
        $models = array();
        $aliases = array();
        $aliases[] = $this->modelAlias;
        foreach (Yii::app()->getModules() as $moduleName => $config) {
            if ($moduleName != 'gii') {
                $aliases[] = $moduleName . ".models";
            }
        }

        foreach ($aliases as $alias) {
            if (!is_dir(Yii::getPathOfAlias($alias))) {
                continue;
            }
            $files = scandir(Yii::getPathOfAlias($alias));
            Yii::import($alias . ".*");
            foreach ($files as $file) {
                if ($fileClassName = $this->_checkFile($file, $alias)) {
                    $classname = sprintf('%s.%s', $alias, $fileClassName);
                    Yii::import($classname);
                    try {
                        $model = @new $fileClassName;
                        if (method_exists($model, 'behaviors')) {
                            $behaviors = $model->behaviors();
                            if (isset($behaviors['i18n-attribute-messages']) && strpos(
                                    $behaviors['i18n-attribute-messages']['class'],
                                    'I18nAttributeMessagesBehavior'
                                ) !== false
                            ) {
                                $models[$classname] = $model;
                            }
                        }
                    } catch (ErrorException $e) {
                        $this->d("\tErrorException: " . $e->getMessage() . "\n");
                        $this->d("\Skipping $file and continuing\n");
                        continue;
                    } catch (CDbException $e) {
                        $this->d("\CDbException: " . $e->getMessage() . "\n");
                        $this->d("\Skipping $file and continuing\n");
                        continue;
                    } catch (Exception $e) {
                        $this->d("\Exception: " . $e->getMessage() . "\n");
                        $this->d("\Skipping $file and continuing\n");
                        continue;
                    }
                }
            }
        }

        return $models;
    }

    // Imported from gii-template-collection / fullCrud / FullCrudCode.php
    protected function _checkFile($file, $alias = '')
    {
        if (substr($file, 0, 1) !== '.' && substr($file, 0, 2) !== '..' && substr(
                $file,
                0,
                4
            ) !== 'Base' && $file != 'GActiveRecord' && strtolower(substr($file, -4)) === '.php'
        ) {
            $fileClassName = substr($file, 0, strpos($file, '.'));
            if (class_exists($fileClassName) && is_subclass_of($fileClassName, 'CActiveRecord')) {
                $fileClass = new ReflectionClass($fileClassName);
                if ($fileClass->isAbstract()) {
                    return null;
                } else {
                    return $models[] = $fileClassName;
                }
            }
        }
    }

}
