<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_imagepopup_handler_uploadTest extends openpsa_testcase
{
    protected static $_images;
    protected static $_tmp_folder;
    protected static $_tmp_names;
    
    public static function setupBeforeClass()
    {
        self::$_images = array();
        self::$_tmp_names = array();
        // We need two images to run tests
        self::$_images = self::create_images(2);
    }
    
    public function testHandler_upload_guid()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $node = self::get_component_node('net.nehmer.static');
        $_FILES['file'] = array_shift(self::$_images);
        
        // Do it goes with guid ?
        $data = $this->run_handler('net.nehmer.static', array ('__ais', 'imagepopup', 'upload', 'image', $node->guid));
        $this->assertEquals('____ais-imagepopup-upload_image', $data['handler_id']);
        
        // Do new attachment exists ? Has a location and name ?
        $this->assertInstanceOf('midcom_db_attachment', $data['attachment']);
        $this->assertNotNull($data['attachment']->location);
        $this->assertEquals('image/jpeg', $data['attachment']->mimetype);
        $location = midcom_db_attachment::get_url($data['attachment']);
        $this->assertEquals($location, $data['location']);
        
        midcom::get()->auth->drop_sudo();
    }
    
    public function testHandler_upload_noguid()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $node = self::get_component_node('net.nehmer.static');
        $_FILES['file'] = array_shift(self::$_images);
        
        // Sleep for 1 second to avoid the same modified filenames for both files in upload handler class
        sleep(1);
        
        // Do it goes without guid ?
        $data = $this->run_handler('net.nehmer.static', array ('__ais', 'imagepopup', 'upload', 'image'));
        $this->assertEquals('____ais-imagepopup-upload_image_noobject', $data['handler_id']);
        
        // Do new attachment exists ? Has a location and name ?
        $this->assertInstanceOf('midcom_db_attachment', $data['attachment']);
        $this->assertNotNull($data['attachment']->location);
        $this->assertEquals('image/jpeg', $data['attachment']->mimetype);
        $location = midcom_db_attachment::get_url($data['attachment']);
        $this->assertEquals($location, $data['location']);
        
        midcom::get()->auth->drop_sudo();
    }
    
    private static function create_images($how_many)
    {
        $images = array();
        $path = sys_get_temp_dir() . "/" . md5(rand());
        self::$_tmp_folder = $path;
        if(!mkdir($path))
        {
            throw new Exception("mkdir() failed.");
        }
        
        for($i = 0; $i < $how_many; $i++)
        {
            $filename = $path . "/file" . $i . ".jpg"; 
            $myFile = fopen($filename, "w");
            if(!$myFile)
            {
                throw new Exception("Cannot open file handle.");
            }
            fclose($myFile);
            array_push(self::$_tmp_names, $filename);
            $name = "imagetools" . $i . ".jpg";
            
            $image = array('name' => $name, 'type' => 'image/jpeg', 'tmp_name' => $filename, 'error' => 0, 'size' => 999);
            array_push($images, $image);
        }
        
        return $images;
    }
    
    public static function tearDownAfterClass()
    {
        foreach(self::$_tmp_names as $temp_name)
        {
            unlink($temp_name);
        }
        rmdir(self::$_tmp_folder);
    }
}