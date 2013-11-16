<?php
use Codeception\Util\Stub;

class BasicTest extends \Codeception\TestCase\Test
{
    /**
     * @var \CodeGuy
     */
    protected $codeGuy;

    protected function _before()
    {
        $this->cleanTestDb();

        // Necessary to actually have a chance to spot errors (Yii Autoloader issue?)
        restore_error_handler();
        restore_exception_handler();

    }

    protected function _after()
    {
        $this->cleanTestDb();
    }

    protected function cleanTestDb()
    {
        Yii::app()->db->createCommand("DELETE FROM book")->execute();
        Yii::app()->db->createCommand("DELETE FROM chapter")->execute();
        Yii::app()->db->createCommand("DELETE FROM image")->execute();
    }

    /**
     * @test
     */
    public function ensureEmptyDb()
    {
        // Verify empty database
        $books = Book::model()->findAll();
        $this->assertEmpty($books);
    }

    /**
     * @test
     */
    public function ensureKnownSourceLanguage()
    {
        $this->assertEquals('en_us', Yii::app()->sourceLanguage);
    }

    /**
     * @test
     */
    public function seeBehavior()
    {
        $book = new Book;
        $this->assertTrue($book->getI18nAttributeMessagesBehavior() instanceof I18nAttributeMessagesBehavior);
    }

    /**
     * @test
     */
    public function interpretLanguageSuffix()
    {
        $book = new Book;
        $this->assertEquals('en', $book->getI18nAttributeMessagesBehavior()->getLanguageSuffix('foo_en'));
        $this->assertEquals('en_us', $book->getI18nAttributeMessagesBehavior()->getLanguageSuffix('foo_en_us'));
        $this->assertEquals('sv', $book->getI18nAttributeMessagesBehavior()->getLanguageSuffix('foo_sv'));
    }

    /**
     * @test
     */
    public function get()
    {

        $book = new Book;

        Yii::app()->language = 'en';
        $this->assertEquals($book->title, $book->title_en);

        Yii::app()->language = 'en_us';
        $this->assertEquals($book->title, $book->title_en_us);

        Yii::app()->language = 'sv';
        $this->assertEquals($book->title, $book->title_sv);
    }

    /**
     * @test
     */
    public function setWithoutSuffix()
    {

        $book = new Book;

        Yii::app()->language = 'en';
        $book->title = 'test';
        $this->assertEquals('test', $book->title);
        $this->assertEquals($book->title, $book->title_en);

        Yii::app()->language = 'en_us';
        $book->title = 'test';
        $this->assertEquals('test', $book->title);
        $this->assertEquals($book->title, $book->title_en_us);

        Yii::app()->language = 'sv';
        $this->assertNotEquals($book->title, $book->title_en);
        $this->assertNotEquals($book->title, $book->title_en_us);
        $this->assertEquals($book->title, $book->title_sv);
    }

    /**
     * @test
     */
    public function setWithSuffix()
    {

        $book = new Book;

        Yii::app()->language = 'en';
        $book->title_en = 'test';
        $this->assertEquals('test', $book->title_en);
        $this->assertEquals($book->title, $book->title_en);

        Yii::app()->language = 'en_us';
        $book->title_en = 'test';
        $this->assertEquals('test', $book->title_en_us);
        $this->assertEquals($book->title, $book->title_en_us);

        Yii::app()->language = 'sv';
        $this->assertNotEquals($book->title, $book->title_en);
        $this->assertEquals($book->title, $book->title_sv);
    }

    /**
     * @test
     */
    public function saveSingleWithoutSuffix()
    {

        $image = new Image;
        $saveResult = $image->save();

        $this->assertEmpty($image->errors);
        $this->assertTrue($saveResult);

        $book = new Book;
        $book->id = 1;
        $book->image_id = $image->id;

        Yii::app()->language = 'sv';
        $book->title = 'Alkemisten';
        $saveResult = $book->save();

        $this->assertEmpty($book->errors);
        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals($book->title, 'Alkemisten');
        $this->assertEquals($book->title_sv, 'Alkemisten');

        Yii::app()->language = 'en_us';
        $book->title = 'The Alchemist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_en_us);
        $this->assertEquals($book->title, 'The Alchemist');
        $this->assertEquals($book->title_en_us, 'The Alchemist');

        Yii::app()->language = 'en';
        $book->title = 'The Alchemist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_en);
        $this->assertEquals($book->title, 'The Alchemist');
        $this->assertEquals($book->title_en, 'The Alchemist');

        $books = Book::model()->findAll();
        $this->assertEquals(1, count($books));

        // Refresh from db
        $book->refresh();

        Yii::app()->language = 'sv';
        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals($book->title, 'Alkemisten');
        $this->assertEquals($book->title_sv, 'Alkemisten');

        Yii::app()->language = 'en';
        $this->assertEquals($book->title, $book->title_en);
        $this->assertEquals($book->title, 'The Alchemist');
        $this->assertEquals($book->title_en, 'The Alchemist');

        Yii::app()->language = 'en_us';
        $this->assertEquals($book->title, $book->title_en_us);
        $this->assertEquals($book->title, 'The Alchemist');
        $this->assertEquals($book->title_en_us, 'The Alchemist');

    }

    /**
     * @test
     */
    public function fetchSingleWithoutSuffix()
    {

        $books = Book::model()->findAll();
        $book = $books[0];

        Yii::app()->language = 'en';

        $this->assertEquals($book->title, 'The Alchemist');
        $this->assertEquals($book->title_en, 'The Alchemist');
        $this->assertEquals($book->title_en_us, 'The Alchemist');

        Yii::app()->language = 'en_us';

        $this->assertEquals($book->title, 'The Alchemist');
        $this->assertEquals($book->title_sv, 'Alkemisten');
        $this->assertEquals($book->title_en_us, 'The Alchemist');

        Yii::app()->language = 'sv';

        $this->assertEquals($book->title, 'Alkemisten');
        $this->assertEquals($book->title_sv, 'Alkemisten');
        $this->assertEquals($book->title_en, 'The Alchemist');
        $this->assertEquals($book->title_en_us, 'The Alchemist');
    }

    /**
     * @test
     */
    public function saveMultiple()
    {

        $book = Book::model()->findByPk(1);

        $this->assertEquals(1, count($book));

        Yii::app()->language = 'pt';

        $book->title = 'O Diabo Veste Prada';
        $book->title_en = 'The Devil Wears Prada';
        $book->title_sv = 'Djävulen bär Prada';

        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title_pt, 'O Diabo Veste Prada');
        $this->assertEquals($book->title_sv, 'Djävulen bär Prada');
        $this->assertEquals($book->title_en, 'The Devil Wears Prada');

        $book = Book::model()->findByPk(1);

        Yii::app()->language = 'en_us';

        $this->assertEquals($book->title_pt, 'O Diabo Veste Prada');
        $this->assertEquals($book->title_sv, 'Djävulen bär Prada');
        $this->assertEquals($book->title_en, 'The Devil Wears Prada');
        $this->assertEquals($book->title, 'The Devil Wears Prada');
    }

}