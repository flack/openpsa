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
class org_openpsa_slideshow_handler_indexTest extends openpsa_testcase
{
    public function test_handler_index()
    {
        $data = $this->run_handler('org.openpsa.slideshow');
        $this->assertEquals('index', $data['handler_id']);
    }

    public function test_handler_index_subfolders()
    {
        $data = $this->run_handler('org.openpsa.slideshow', ['subfolders']);
        $this->assertEquals('index_subfolders', $data['handler_id']);
    }
}
