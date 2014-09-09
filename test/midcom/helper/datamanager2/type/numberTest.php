<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once OPENPSA_TEST_ROOT . 'midcom/helper/datamanager2/__helper/dm2.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_type_numberTest extends openpsa_testcase
{
    public function test_convert_to_storage()
    {
        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('text', 'number');
        $type = $widget->_type;
        $type->set_value('3,4');
        $this->assertSame(3.4, $type->convert_to_storage());
    }
}
