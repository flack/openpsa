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
class midcom_helper_datamanager2_widget_autocompleteTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $config = array(
            'type_config' => array(
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'gid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
                'allow_multiple' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array(
                'class' => 'midcom_db_person',
                'id_field' => 'id',
                'searchfields' => array(
                    'lastname',
                ),
                'result_headers' => array(
                    array(
                        'name' => 'name',
                        'title' => 'Name',
                    ),
                ),
            ),
        );

        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = array('test_autocomplete_1' => array(1 => true));
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);

        $this->assertEquals(array('test_autocomplete_1' => array(1 => true)), $widget->get_default(), 'nullstorage/default test failed');

        $group = new midcom_db_group;
        $dm2_helper = new openpsa_test_dm2_helper($group);
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = array('test_autocomplete_1' => array(1 => true));
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);

        $this->assertEquals(array('test_autocomplete_1' => array(1 => true)), $widget->get_default(), 'create/default test failed');

        $group = $this->create_object('midcom_db_group');
        $dm2_helper = new openpsa_test_dm2_helper($group);
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);

        $this->assertNull($widget->get_default(), 'simple test failed');

        $person = $this->create_object('midcom_db_person');
        $member = $this->create_object('midcom_db_member', array('gid' => $group->id, 'uid' => $person->id));

        $dm2_helper = new openpsa_test_dm2_helper($group);
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);

        $this->assertEquals(array('test_autocomplete_0' => array($person->id => true)), $widget->get_default(), 'simple/storage test failed');
    }

    public function test_render_content()
    {
        $config = array(
            'type_config' => array(
                'mapping_class_name' => 'midcom_db_member',
                'master_fieldname' => 'gid',
                'member_fieldname' => 'uid',
                'master_is_id' => true,
                'allow_multiple' => true,
            ),
            'widget' => 'autocomplete',
            'widget_config' => array(
                'class' => 'midcom_db_person',
                'id_field' => 'id',
                'searchfields' => array(
                    'lastname',
                ),
                'result_headers' => array(
                    array(
                        'name' => 'name',
                        'title' => 'Name',
                    ),
                ),
            ),
        );

        $lastname = uniqid(__CLASS__ . '::' . __FUNCTION__);
        $group = $this->create_object('midcom_db_group');
        $person = $this->create_object('midcom_db_person', array('lastname' => $lastname));
        $this->create_object('midcom_db_member', array('gid' => $group->id, 'uid' => $person->id));
        $dm2_helper = new openpsa_test_dm2_helper($group);
        $widget = $dm2_helper->get_widget('autocomplete', 'mnrelation', $config);
        $string = $widget->render_content();
        $this->assertEquals($lastname, $string);
    }
}
