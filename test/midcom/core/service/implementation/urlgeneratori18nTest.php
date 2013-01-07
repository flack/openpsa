<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once OPENPSA_TEST_ROOT . 'rootfile.php';
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_core_service_implementation_urlgeneratori18nTest extends openpsa_testcase
{
    public function test_from_string()
    {
        $generator = midcom::get('serviceloader')->load('midcom_core_service_urlgenerator');
        $clean1 = $generator->from_string('foobar & barfoo');
        $clean2 = $generator->from_string($clean1);
        $this->assertEquals($clean1, $clean2);
    }
}
?>