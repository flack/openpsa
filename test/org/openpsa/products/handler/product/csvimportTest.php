<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_products_handler_product_csvimportTest extends openpsa_testcase
{
    public function testHandler_csvimport()
    {
        midcom::get('auth')->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', array('import', 'product', 'csv'));
        $this->assertEquals('import_product_csv', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>