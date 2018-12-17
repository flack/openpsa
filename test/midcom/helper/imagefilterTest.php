<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midgard\portable\api\blob;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_imagefilterTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_filepath;
    protected static $_filename;

    public static function setUpBeforeClass()
    {
        self::$_topic = self::create_class_object(midcom_db_topic::class);
        self::$_filepath = dirname(__FILE__) . '/__files/';
        self::$_filename = self::$_filepath . 'midgard-16x16.png';
    }

    public function testConstruct()
    {
        $attachment = $this->create_object(midcom_db_attachment::class, ['parentguid' => self::$_topic->guid]);

        midcom::get()->auth->request_sudo('midcom.core');
        $attachment->copy_from_file(self::$_filename);
        midcom::get()->auth->drop_sudo();

        $filter = new midcom_helper_imagefilter($attachment);
        $this->assertNotEquals(self::$_filename, $filter->get_file(), 'Tmp file should be named differently');
    }

    /**
     * construct a filter initialized with an attachment containing the test file
     */
    private function _get_prepared_filter()
    {
        $filter = new midcom_helper_imagefilter(null);
        $tmpname = $filter->create_tmp_copy(self::$_filename);
        $filter->set_file($tmpname);

        return $filter;
    }

    public function testCreate_tmp_copy()
    {
        $filter = new midcom_helper_imagefilter(null);
        $stat = stat(self::$_filename);
        $tmpname = $filter->create_tmp_copy(self::$_filename);
        $dest_stat = stat($tmpname);

        // the files should have the same size
        $this->assertEquals($stat["size"], $dest_stat["size"], "Original image and tmp copy filesize mismatch!");
        $this->assertEquals(filesize(self::$_filename), filesize($tmpname));

        // now write the file into an attachment
        $attachment = $this->create_object(midcom_db_attachment::class, ['parentguid' => self::$_topic->guid, 'title'=>'someImg']);
        $filter->set_file($tmpname);
        $filter->write($attachment);

        // check if files are equal
        $blob = new blob($attachment->__object);
        $this->assertFileEquals(self::$_filename, $blob->get_path());
        $this->assertEquals(filesize(self::$_filename), filesize($blob->get_path()));

        // test with attachment
        $filter = new midcom_helper_imagefilter($attachment);
        $filename = $filter->get_file();
        $this->assertNotEquals($filename, self::$_filename);
        $this->assertEquals(filesize(self::$_filename), filesize($filename));
    }

    public function testImageMagick_available()
    {
        // this should be the same each time its called
        $stat = midcom_helper_imagefilter::imagemagick_available();
        $this->assertEquals($stat, midcom_helper_imagefilter::imagemagick_available());
    }

    private function _testDimensions($filename, $expected_width, $expected_height)
    {
        list($width, $height) = getimagesize($filename);
        $this->assertEquals($expected_width, $width);
        $this->assertEquals($expected_height, $height);
    }

    private function _testMimetype($filename, $expected_mime)
    {
        $data = getimagesize($filename);
        $this->assertEquals($expected_mime, $data["mime"]);
    }

    public function testProcess_Chain()
    {
        $filter = $this->_get_prepared_filter();
        $filter->process_chain("resize(5,5)");
    }

    /**
     * @expectedException midcom_error
     */
    public function testProcess_Chain_invalid()
    {
        $filter = $this->_get_prepared_filter();
        $filter->process_chain(";ThisCommandIsTotallyUnknown");
    }

    public function testGamma()
    {
        $filter = $this->_get_prepared_filter();
        $filter->gamma(0/100);
    }

    public function testResize()
    {
        $filter = $this->_get_prepared_filter();
        $filter->resize(3, 3);
        $this->_testDimensions($filter->get_file(), 3, 3);
    }

    public function testConvert()
    {
        $filter = $this->_get_prepared_filter();
        $this->_testMimetype($filter->get_file(), "image/png");

        $filter->convert('jpg');
        $this->_testMimetype($filter->get_file(), "image/jpeg");
    }

    public function testRotate()
    {
        $filter = $this->_get_prepared_filter();
        $filter->rotate(45);
    }

    public function testExifRotate()
    {
        $filter = $this->_get_prepared_filter();
        $filter->exifrotate();
    }

    public function testCrop()
    {
        $filter = $this->_get_prepared_filter();
        $filter->crop(8, 8);
        $this->_testDimensions($filter->get_file(), 8, 8);
    }

    public function testSquareThumb()
    {
        $filter = $this->_get_prepared_filter();
        $filter->squarethumb(12);
        $this->_testDimensions($filter->get_file(), 12, 12);
    }

    public function testFill()
    {
        $filter = $this->_get_prepared_filter();
        $filter->fill(24, 24, '#000000');
        $this->_testDimensions($filter->get_file(), 24, 24);
    }
}
