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
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_type_blobsTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_safe_filename
     */
    public function test_safe_filename($input, $extension, $output)
    {
        $converted = midcom_helper_datamanager2_type_blobs::safe_filename($input, $extension);
        $this->assertEquals($converted, $output);
    }

    public function provider_safe_filename()
    {
        return array
        (
            array('Minä olen huono tiedosto.foo.jpg', true, 'mina_olen_huono_tiedosto-foo.jpg'),
            array('Minä olen huono tiedosto.foo.jpg', false, 'mina_olen_huono_tiedosto.foo.jpg'),
            array('Minä olen huono tiedosto ilman päätettä', true, 'mina_olen_huono_tiedosto_ilman_paatetta'),
            array('Minä olen huono tiedosto ilman päätettä', false, 'mina_olen_huono_tiedosto_ilman_paatetta'),
        );
    }
}
?>