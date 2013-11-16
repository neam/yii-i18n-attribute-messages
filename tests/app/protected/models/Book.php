<?php

/**
 * This is the model class for table "book".
 *
 * The followings are the available columns in table 'book':
 * @property string $id
 * @property string $_title
 * @property string $_slug
 * @property string $image_id
 * @property string $created
 * @property string $modified
 *
 * The followings are the available model relations:
 * @property Image $image
 * @property Chapter[] $chapters
 */
class Book extends CActiveRecord
{

	/**
     * Define behaviors
     * @return array
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            array(
                'i18n-attribute-messages' => array(
                    'class' => 'I18nAttributeMessagesBehavior',
                    'translationAttributes' => array(
                        'title',
                        'slug',
                    ),
                ),
            )
        );
    }

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Book the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'book';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('image_id', 'required'),
			array('_title, _slug', 'length', 'max'=>255),
			array('image_id', 'length', 'max'=>20),
			array('created, modified', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, _title, _slug, image_id, created, modified', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'image' => array(self::BELONGS_TO, 'Image', 'image_id'),
			'chapters' => array(self::HAS_MANY, 'Chapter', '_book_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'_title' => 'Title',
			'_slug' => 'Slug',
			'image_id' => 'Image',
			'created' => 'Created',
			'modified' => 'Modified',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('_title',$this->_title,true);
		$criteria->compare('_slug',$this->_slug,true);
		$criteria->compare('image_id',$this->image_id,true);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('modified',$this->modified,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}