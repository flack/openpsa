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
class midcom_helper_datamanager2_type_textTest extends openpsa_testcase
{
    public function test_validate_htmlpurifier()
    {
        $config = [
            'type_config' => [
                'purify' => true,
                'forbidden_patterns' => [
                    [
                        'type' => 'regex',
                        'pattern' => '%(<[^>]+>)%si',
                        'explanation' => 'HTML is not allowed',
                    ],
                ],
            ],
            'widget' => 'textarea',
        ];

        $topic = $this->create_object('midcom_db_topic');

        $dm2_helper = new openpsa_test_dm2_helper($topic);
        $widget = $dm2_helper->get_widget('textarea', 'text', $config);
        $type = $widget->_type;

        $type->value = '<p>TEST</p>';
        $this->assertFalse($type->validate());
        $this->assertStringEndsWith('HTML is not allowed', $type->validation_error);
    }
}
