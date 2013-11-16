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
        return; // Using Codeception Db Module dump features instead
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
        $book->title = 'test en';
        $this->assertEquals('test en', $book->title);
        $this->assertEquals($book->title, $book->title_en);

        Yii::app()->language = 'en_us';
        $book->title = 'test en_us';
        $this->assertEquals('test en_us', $book->title);
        $this->assertEquals($book->title, $book->title_en_us);

        Yii::app()->language = 'sv';
        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals($book->title, $book->title_en_us); // Equals because of fallback
        $this->assertNotEquals($book->title, $book->title_en); // Equals because of fallback
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
        $book->title_en_us = 'test';
        $this->assertEquals('test', $book->title_en_us);
        $this->assertEquals($book->title, $book->title_en_us);

        Yii::app()->language = 'sv';
        $this->assertEquals($book->title, $book->title_sv);

        $this->assertEquals($book->title, $book->title_en); // Equals because of fallback
    }

    /**
     * @test
     */
    public function saveSingleWithSourceMessage()
    {

        $image = new Image;
        $saveResult = $image->save();

        $this->assertEmpty($image->errors);
        $this->assertTrue($saveResult);

        $book = new Book;
        $book->id = 1;
        $book->image_id = $image->id;

        Yii::app()->language = Yii::app()->sourceLanguage;
        $book->title = 'The Alchemist';

        Yii::app()->language = 'sv';
        $book->title = 'Alkemisten';
        $saveResult = $book->save();

        $this->assertEmpty($book->errors);
        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals('Alkemisten', $book->title);
        $this->assertEquals('Alkemisten', $book->title_sv);

        Yii::app()->language = 'en';
        $book->title = 'The Alchemist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_en);
        $this->assertEquals('The Alchemist', $book->title);
        $this->assertEquals('The Alchemist', $book->title_en);

        Yii::app()->language = 'de';
        $book->title = 'Der Alchimist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_de);
        $this->assertEquals('Der Alchimist', $book->title);
        $this->assertEquals('Der Alchimist', $book->title_de);
        $this->assertEquals('The Alchemist', $book->title_en);

        // Refresh from db
        $book->refresh();

        Yii::app()->language = 'sv';
        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals('Alkemisten', $book->title);
        $this->assertEquals('Alkemisten', $book->title_sv);

        Yii::app()->language = 'en';
        $this->assertEquals($book->title, $book->title_en);
        $this->assertEquals('The Alchemist', $book->title);
        $this->assertEquals('The Alchemist', $book->title_en);

        Yii::app()->language = 'en_us';
        $this->assertEquals($book->title, $book->title_en_us);
        $this->assertEquals('The Alchemist', $book->title);
        $this->assertEquals('The Alchemist', $book->title_en_us);

        Yii::app()->language = 'en_us';
        $book->title = 'The Alchemist and the Frog';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_en_us);
        $this->assertEquals('The Alchemist and the Frog', $book->title);
        $this->assertEquals('The Alchemist and the Frog', $book->title_en_us);
        $this->assertEquals('The Alchemist and the Frog', $book->title_sv); // Equals because of fallback - above translations are no longer valid after the source message changes
        $this->assertEquals('The Alchemist and the Frog', $book->title_de); // Equals because of fallback - above translations are no longer valid after the source message changes
        $this->assertEquals('The Alchemist and the Frog', $book->title_ch); // Equals because of fallback - above translations are no longer valid after the source message changes

        $books = Book::model()->findAll();
        $this->assertEquals(1, count($books));

    }

    /**
     * @test
     */
    public function saveSingleWithoutSourceMessage()
    {

        $image = new Image;
        $saveResult = $image->save();

        $this->assertEmpty($image->errors);
        $this->assertTrue($saveResult);

        $book = new Book;
        $book->id = 2;
        $book->image_id = $image->id;

        Yii::app()->language = 'sv';
        $book->title = 'Alkemisten';
        $saveResult = $book->save();

        $this->assertEmpty($book->errors);
        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals($book->title_en_us, $book->title); // Equals because of fallback
        $this->assertEquals($book->title_en_us, $book->title_sv); // Equals because of fallback

        Yii::app()->language = 'en';
        $book->title = 'The Alchemist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_en);
        $this->assertEquals($book->title_en_us, $book->title); // Equals because of fallback
        $this->assertEquals($book->title_en_us, $book->title_en); // Equals because of fallback

        Yii::app()->language = 'de';
        $book->title = 'Der Alchimist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals($book->title, $book->title_de);
        $this->assertEquals($book->title_en_us, $book->title); // Equals because of fallback
        $this->assertEquals($book->title_en_us, $book->title_de); // Equals because of fallback

        $books = Book::model()->findAll();
        $this->assertEquals(2, count($books));

        // Refresh from db
        $book->refresh();

        Yii::app()->language = 'sv';
        $this->assertEquals($book->title, $book->title_sv);
        $this->assertEquals($book->title_en_us, $book->title);
        $this->assertEquals($book->title_en_us, $book->title_sv);

        Yii::app()->language = 'en';
        $this->assertEquals($book->title, $book->title_en);
        $this->assertEquals($book->title_en_us, $book->title);
        $this->assertEquals($book->title_en_us, $book->title_en);

        Yii::app()->language = 'en_us';
        $this->assertEquals($book->title, $book->title_en_us);
        $this->assertEquals(null, $book->title);
        $this->assertEquals(null, $book->title_en_us);

    }

    /**
     * @test
     */
    public function fetchSingleWithoutSuffix()
    {

        $books = Book::model()->findAll();
        $book = $books[0];

        Yii::app()->language = 'en';

        $this->assertEquals('The Alchemist and the Frog', $book->title);
        $this->assertEquals('The Alchemist and the Frog', $book->title_en);
        $this->assertEquals('The Alchemist and the Frog', $book->title_sv);
        $this->assertEquals('The Alchemist and the Frog', $book->title_en_us);

    }

    /**
     * @test
     */
    public function reusePreviousTranslation()
    {

        $books = Book::model()->findAll();
        $book = $books[0];

        Yii::app()->language = 'en_us';
        $book->title = 'The Alchemist';
        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        Yii::app()->language = 'en';

        $this->assertEquals('The Alchemist', $book->title);
        $this->assertEquals('The Alchemist', $book->title_en);
        $this->assertEquals('The Alchemist', $book->title_en_us);

        Yii::app()->language = 'de';

        $this->assertEquals('Der Alchimist', $book->title);
        $this->assertEquals('Der Alchimist', $book->title_de);
        $this->assertEquals('The Alchemist', $book->title_en_us);

        Yii::app()->language = 'sv';

        $this->assertEquals('Alkemisten', $book->title);
        $this->assertEquals('Alkemisten', $book->title_sv);
        $this->assertEquals('The Alchemist', $book->title_en_us);

    }

    /**
     * @test
     */
    public function updateExisting()
    {

        $books = Book::model()->findAll();
        $book = $books[0];

        $this->assertEquals(2, count($books));

        Yii::app()->language = 'pt';

        $book->title_en_us = 'The Devil Wears Prada';
        $book->title = 'O Diabo Veste Prada';
        $book->title_en = 'The Devil Wears Prada';
        $book->title_sv = 'Djävulen bär Prada';

        $saveResult = $book->save();

        $this->assertTrue($saveResult);

        $this->assertEquals('O Diabo Veste Prada', $book->title_pt);
        $this->assertEquals('Djävulen bär Prada', $book->title_sv);
        $this->assertEquals('The Devil Wears Prada', $book->title_en_us);
        $this->assertEquals('The Devil Wears Prada', $book->title_en);

        $books = Book::model()->findAll();
        $book = $books[0];

        $this->assertEquals(2, count($books));

        Yii::app()->language = 'en_us';

        $this->assertEquals('O Diabo Veste Prada', $book->title_pt);
        $this->assertEquals('Djävulen bär Prada', $book->title_sv);
        $this->assertEquals('The Devil Wears Prada', $book->title_en_us);
        $this->assertEquals('The Devil Wears Prada', $book->title_en);

        Yii::app()->language = 'de';

        $this->assertEquals('O Diabo Veste Prada', $book->title_pt);
        $this->assertEquals('Djävulen bär Prada', $book->title_sv);
        $this->assertEquals('The Devil Wears Prada', $book->title_en_us);
        $this->assertEquals($book->title_en_us, $book->title);

    }

    /**
     * Note: This test assumes default Yii::t() fallback behavior
     * @test
     */
    public function furtherFallbackBehaviorTests()
    {

        $books = Book::model()->findAll();
        $book = $books[0];

        $this->assertEquals(2, count($books));

        $fooText = "Lean on me";

        Yii::app()->language = Yii::app()->sourceLanguage;
        $this->assertEquals($fooText, Yii::t('app', $fooText));

        Yii::app()->language = 'ch';
        $this->assertEquals($fooText, Yii::t('app', $fooText));

        $chapter = new Chapter();
        $chapter->_book_id = $book->id;
        $chapter->_title = $fooText;

        Yii::app()->language = 'en_us';
        $this->assertEquals($fooText, $chapter->title);

        Yii::app()->language = 'de';
        $this->assertEquals($fooText, $chapter->title);

        $saveResult = $chapter->save();

        $this->assertTrue($saveResult);

        $chapters = Chapter::model()->findAll();
        $chapter = $chapters[0];

        $this->assertEquals(1, count($chapters));

        $this->assertEquals($fooText, $chapter->title);
        $this->assertEquals($fooText, $chapter->title_de);
        $this->assertEquals($fooText, $chapter->title_ch);

        Yii::app()->language = 'ch';

        $this->assertEquals($book->title_en_us, $book->title_ch);

        $this->assertEquals($book->title_pt, 'O Diabo Veste Prada');
        $this->assertEquals($book->title_sv, 'Djävulen bär Prada');
        $this->assertEquals($book->title_en_us, 'The Devil Wears Prada');
        $this->assertEquals($book->title_en, 'The Devil Wears Prada');

    }

}