<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA DM2 test helper
 *
 * @package openpsa.test
 */
class openpsa_test_dm2_helper
{
    private $_object;
    private $_schemadb;
    private $_controller;

    public $defaults = [];

    public function __construct($object = null)
    {
        $this->_object = $object;
        $schemadb_raw = [
            'default' => [
                'description' => __CLASS__ . ' testcase schema',
                'fields' => []
            ]
        ];
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($schemadb_raw);

        if ($this->_object) {
            if (!empty($this->_object->id)) {
                $controller_type = 'simple';
            } else {
                $controller_type = 'create';
            }
        } else {
            $controller_type = 'nullstorage';
        }
        $this->_controller = midcom_helper_datamanager2_controller::create($controller_type);
        $this->_controller->schemaname = 'default';
        $this->_controller->schemadb =& $this->_schemadb;

        switch ($controller_type) {
            case 'create':
                $this->_controller->callback_object =& $this;

            case 'nullstorage':
                $this->_controller->defaults =& $this->defaults;
                break;
        }
    }

    public function get_widget($widget_class, $type_class, array $config = [], $name = null)
    {
        if (null === $name) {
            $name = 'test_' . $widget_class . '_' . sizeof($this->_schemadb['default']->fields);
        }

        $config['name'] = $name;
        $config['title'] = $name;
        $config['type'] = $type_class;
        $config['widget'] = $widget_class;

        if (!array_key_exists('storage', $config)) {
            $config['storage'] = null;
        }

        $this->_schemadb['default']->append_field($name, $config);

        if ($this->_controller instanceof midcom_helper_datamanager2_controller_simple) {
            $this->_controller->set_storage($this->_object, 'default');
        }

        if (!$this->_controller->initialize()) {
            throw new Exception('Failed to initialize DM2 controller.');
        }

        return $this->_controller->formmanager->widgets[$name];
    }

    public function & dm2_create_callback(&$controller)
    {
        return $this->_object;
    }
}
