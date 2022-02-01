<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\helper\imagepopup\handler;

use openpsa_testcase;
use midcom;
use midcom_db_attachment;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class uploadTest extends openpsa_testcase
{
    protected static $_images;
    protected static $_tmp_folder;
    protected static $_tmp_names;

    public static function setUpBeforeClass() : void
    {
        self::$_images = [];
        self::$_tmp_names = [];
        // We need two images to run tests
        self::$_images = self::create_images(2);
    }

    public function testHandler_upload_guid()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $node = self::get_component_node('net.nehmer.static');
        $img = array_shift(self::$_images);
        $request = new Request([], [], [], [], ['file' => new UploadedFile($img['tmp_name'], $img['name'], $img['type'])]);

        // Do it goes with guid ?
        $data = $this->run_handler($node, ['__ais', 'imagepopup', 'upload', 'image', $node->guid], $request);
        $this->assertEquals('upload_image', $data['handler_id']);
        $attachment = $this->get_attachment($data);

        // Do new attachment exists ? Has a location and name ?
        $this->assertNotNull($attachment->location);
        $this->assertEquals('image/jpeg', $attachment->mimetype);

        midcom::get()->auth->drop_sudo();
    }

    private function get_attachment(array $data) : \midcom_db_attachment
    {
        $url = $data['__openpsa_testcase_response']->location;
        $this->assertMatchesRegularExpression('/\/midcom-serveattachmentguid-.+?/', $url);
        $guid = preg_replace('/\/midcom-serveattachmentguid-(.+?)\/.+/', '$1', $url);
        return new \midcom_db_attachment($guid);
    }

    public function testHandler_upload_noguid()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $img = array_shift(self::$_images);
        $request = new Request([], [], [], [], ['file' => new UploadedFile($img['tmp_name'], $img['name'], $img['type'])]);

        // Do it goes without guid ?
        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'upload', 'image'], $request);
        $this->assertEquals('upload_image_noobject', $data['handler_id']);
        $attachment = $this->get_attachment($data);

        // Do new attachment exists ? Has a location and name ?
        $this->assertNotNull($attachment->location);
        $this->assertEquals('image/jpeg', $attachment->mimetype);

        midcom::get()->auth->drop_sudo();
    }

    private static function create_images(int $how_many) : array
    {
        $images = [];
        $path = sys_get_temp_dir() . "/" . md5(rand());
        self::$_tmp_folder = $path;
        if (!mkdir($path)) {
            throw new Exception("mkdir() failed.");
        }

        for ($i = 0; $i < $how_many; $i++) {
            $filename = $path . "/file" . $i . ".jpg";
            $myFile = fopen($filename, "w");
            if (!$myFile) {
                throw new Exception("Cannot open file handle.");
            }
            fclose($myFile);
            array_push(self::$_tmp_names, $filename);
            $name = "imagetools" . $i . ".jpg";

            $images[] = ['name' => $name, 'type' => 'image/jpeg', 'tmp_name' => $filename];
        }

        return $images;
    }

    public static function tearDownAfterClass() : void
    {
        foreach (self::$_tmp_names as $temp_name) {
            unlink($temp_name);
        }
        rmdir(self::$_tmp_folder);
    }
}
