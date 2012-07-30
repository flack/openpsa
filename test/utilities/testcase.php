<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Base class for unittests, provides some helper methods
 *
 * @package openpsa.test
 */
abstract class openpsa_testcase extends PHPUnit_Framework_TestCase
{
    private static $_class_objects = array();
    private $_testcase_objects = array();

    public static function create_user($login = false)
    {
        $person = new midcom_db_person();
        $person->_use_rcs = false;
        $person->_use_activitystream = false;
        $password = substr('p_' . time(), 0, 11);
        $username = __CLASS__ . ' user ' . microtime();

        midcom::get('auth')->request_sudo('midcom.core');
        if (!$person->create())
        {
            throw new Exception('Person could not be created. Reason: ' . midcom_connection::get_error_string());
        }

        $account = midcom_core_account::get($person);
        $account->set_password($password);
        $account->set_username($username);
        $account->save();
        midcom::get('auth')->drop_sudo();
        if ($login)
        {
            if (!midcom::get('auth')->login($username, $password))
            {
                throw new Exception('Login for user ' . $username . ' failed');
            }
            midcom::get('auth')->_sync_user_with_backend();
        }
        self::$_class_objects[$person->guid] = $person;
        return $person;
    }

    public static function get_component_node($component)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        midcom::get('auth')->request_sudo($component);
        if ($topic_guid = $siteconfig->get_node_guid($component))
        {
            $topic = new midcom_db_topic($topic_guid);
        }
        else
        {
            $qb = midcom_db_topic::new_query_builder();
            $qb->add_constraint('component', '=', $component);
            $qb->set_limit(1);
            $qb->add_order('id');
            $result = $qb->execute();
            if (sizeof($result) == 1)
            {
                midcom::get('auth')->drop_sudo();
                return $result[0];
            }

            $root_topic = midcom_db_topic::get_cached($GLOBALS['midcom_config']['root_topic']);
            $topic_attributes = array
            (
                'up' => $root_topic->id,
                'component' => $component,
                'name' => 'handler_test_' . time()
            );
            $topic = self::create_class_object('midcom_db_topic', $topic_attributes);
        }
        midcom::get('auth')->drop_sudo();
        return $topic;
    }

    public function run_handler($topic, array $args = array())
    {
        if (is_object($topic))
        {
            $component = $topic->component;
        }
        else
        {
            $component = $topic;
            $topic = $this->get_component_node($component);
        }

        $context = new midcom_core_context(null, $topic);
        $context->set_current();
        $context->set_key(MIDCOM_CONTEXT_URI, midcom_connection::get_url('self') . $topic->name . '/' . implode('/', $args) . '/');

        // Parser Init: Generate arguments and instantiate it.
        $context->parser = midcom::get('serviceloader')->load('midcom_core_service_urlparser');
        $context->parser->parse($args);
        $handler = $context->get_handler($topic);
        $context->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $topic);
        $this->assertTrue(is_a($handler, 'midcom_baseclasses_components_interface'), $component . ' found no handler for ./' . implode('/', $args) . '/');

        $result = $handler->handle();
        $this->assertTrue($result !== false, $component . ' handle returned false on ./' . implode('/', $args) . '/');
        $data =& $handler->_context_data[$context->id]['handler']->_handler['handler'][0]->_request_data;

        if (   is_object($result)
            && $result instanceof midcom_response)
        {
            $data['__openpsa_testcase_response'] = $result;
        }

        $data['__openpsa_testcase_handler'] = $handler->_context_data[$context->id]['handler']->_handler['handler'][0];
        $data['__openpsa_testcase_handler_method'] = $handler->_context_data[$context->id]['handler']->_handler['handler'][1];

        // added to simulate http uri composition
        $_SERVER['REQUEST_URI'] = $context->get_key(MIDCOM_CONTEXT_URI);

        return $data;
    }

    public function show_handler(&$data)
    {
        $handler = $data['__openpsa_testcase_handler'];
        $method = '_show_' . $data['__openpsa_testcase_handler_method'];

        midcom::get()->set_status(MIDCOM_STATUS_CONTENT);
        $context = midcom_core_context::get();
        midcom::get('style')->enter_context($context->id);
        ob_start();
        $context->set_custom_key('request_data', $data);
        $handler->_request_data =& $data;
        $handler->$method($data['handler_id'], $data);
        $output = ob_get_contents();
        ob_end_clean();
        midcom::get('style')->leave_context();
        midcom::get()->set_status(MIDCOM_STATUS_PREPARE);
        return $output;
    }

    public function set_post_data($post_data)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $post_data;
        $_REQUEST = $_POST;
    }

    public function set_get_data($get_data)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get_data;
        $_REQUEST = $_GET;
    }

    public function set_dm2_formdata($controller, $formdata)
    {
        $formname = substr($controller->formmanager->namespace, 0, -1);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = array_merge($controller->formmanager->form->_defaultValues, $formdata);

        $_POST['_qf__' . $formname] = '';
        $_POST['midcom_helper_datamanager2_save'] = array('');
        $_REQUEST = $_POST;
    }

    public function submit_dm2_form($controller_key, $formdata, $component, $args = array())
    {
        $data = $this->run_handler($component, $args);

        $this->set_dm2_formdata($data[$controller_key], $formdata);

        try
        {
            $data = $this->run_handler($component, $args);
            if (array_key_exists($controller_key, $data))
            {
                $this->assertEquals(array(), $data[$controller_key]->formmanager->form->_errors, 'Form validation failed');
            }
            $this->assertTrue($data['__openpsa_testcase_response'] instanceof midcom_response_relocate, 'Form did not relocate');
            return $data['__openpsa_testcase_response']->url;
        }
        catch (openpsa_test_relocate $e)
        {
            $url = $e->getMessage();
            $url = preg_replace('/^\//', '', $url);
            return $url;
        }
    }

    /**
     * same logic as submit_dm2_form, but this method does not expect a relocate
     */
    public function submit_dm2_no_relocate_form($controller_key, $formdata, $component, $args = array())
    {
        $data = $this->run_handler($component, $args);
        $this->set_dm2_formdata($data[$controller_key], $formdata);
        $data = $this->run_handler($component, $args);

        $this->assertEquals(array(), $data[$controller_key]->formmanager->form->_errors, 'Form validation failed');

        return $data;
    }

    public function run_relocate_handler($component, array $args = array())
    {
        $url = null;
        try
        {
            $data = $this->run_handler($component, $args);
            $this->assertTrue($data['__openpsa_testcase_response'] instanceof midcom_response_relocate, 'handler did not relocate');
            $url = $data['__openpsa_testcase_response']->url;
        }
        catch (openpsa_test_relocate $e)
        {
            $url = $e->getMessage();
        }

        $url = preg_replace('/^\//', '', $url);
        return $url;
    }

    public function create_object($classname, $data = array())
    {
        $object = self::_create_object($classname, $data);
        $this->_testcase_objects[$object->guid] = $object;
        return $object;
    }

    /**
     * Register an object created in a testcase. That way, it'll get properly deleted
     * if the test aborts
     */
    public function register_object($object)
    {
        $this->_testcase_objects[$object->guid] = $object;
    }

    /**
     * Register multiple objects created in a testcase. That way, they'll get properly deleted
     * if the test aborts
     */
    public function register_objects(array $array)
    {
        foreach ($array as $object)
        {
            $this->_testcase_objects[$object->guid] = $object;
        }
    }

    private static function _create_object($classname, $data)
    {
        $presets = array
        (
            '_use_rcs' => false,
            '_use_activitystream' => false,
        );
        $data = array_merge($presets, $data);
        $object = self::prepare_object($classname, $data);

        midcom::get('auth')->request_sudo('midcom.core');
        if (!$object->create())
        {
            throw new Exception('Object of type ' . $classname . ' could not be created. Reason: ' . midcom_connection::get_error_string());
        }
        midcom::get('auth')->drop_sudo();
        return $object;
    }

    public static function prepare_object($classname, $data)
    {
        $object = new $classname();

        foreach ($data as $field => $value)
        {
            if (strpos($field, '.') !== false)
            {
                $parts = explode('.', $field);
                $object->{$parts[0]}->{$parts[1]} = $value;
                continue;
            }
            $object->$field = $value;
        }
        return $object;
    }

    public static function create_class_object($classname, $data = array())
    {
        $object = self::_create_object($classname, $data);
        self::$_class_objects[$object->guid] = $object;
        return $object;
    }

    public static function create_persisted_object($classname, $data = array())
    {
        return self::_create_object($classname, $data);
    }

    public static function delete_linked_objects($classname, $link_field, $id)
    {
        midcom::get('auth')->request_sudo('midcom.core');
        $qb = call_user_func(array($classname, 'new_query_builder'));
        $qb->add_constraint($link_field, '=', $id);
        $results = $qb->execute();
        foreach ($results as $result)
        {
            $result->_use_rcs = false;
            $result->_use_activitystream = false;
            $result->delete();
            $result->purge();
        }
        midcom::get('auth')->drop_sudo();
    }

    public function tearDown()
    {
        if (isset($_SERVER['REQUEST_METHOD']))
        {
            unset($_SERVER['REQUEST_METHOD']);
        }
        if (!empty($_POST))
        {
            $_POST = array();
        }
        if (!empty($_GET))
        {
            $_GET = array();
        }
        if (!empty($_REQUEST))
        {
            $_REQUEST = array();
        }
        if (midcom_core_context::get()->id != 0)
        {
            midcom_core_context::get(0)->set_current();
        }

        if (!$GLOBALS['midcom_config']['auth_allow_sudo'])
        {
            $GLOBALS['midcom_config']['auth_allow_sudo'] = true;
        }

        while (midcom::get('auth')->is_component_sudo())
        {
            midcom::get('auth')->drop_sudo();
        }

        $queue = array();

        while (!empty($this->_testcase_objects))
        {
            $object = array_pop($this->_testcase_objects);
            if (array_key_exists($object->guid, self::$_class_objects))
            {
                continue;
            }
            $queue[] = $object;
        }

        self::_process_delete_queue($queue);
        $this->_testcase_objects = array();
        org_openpsa_mail_backend_unittest::flush();
        midcom_compat_unittest::flush_registered_headers();
    }

    public static function TearDownAfterClass()
    {
        self::_process_delete_queue(self::$_class_objects);
        self::$_class_objects = array();
        midcom::get('auth')->logout();
    }

    private static function _process_delete_queue($queue)
    {
        midcom::get('auth')->request_sudo('midcom.core');
        $limit = sizeof($queue) * 5;
        $iteration = 0;
        while (!empty($queue))
        {
            $object = array_pop($queue);
            if (!$object->delete())
            {
                if (   midcom_connection::get_error() == MGD_ERR_HAS_DEPENDANTS
                    || midcom_connection::get_error() == MGD_ERR_OK)
                {
                    array_unshift($queue, $object);
                }
                else if (midcom_connection::get_error() == MGD_ERR_NOT_EXISTS)
                {
                    continue;
                }
                else
                {
                    throw new midcom_error('Cleanup ' . get_class($object) . ' ' . $object->guid . ' failed, reason: ' . midcom_connection::get_error_string());
                }
            }
            else
            {
                $object->purge();
            }
            if ($iteration++ > $limit)
            {
                $classnames = array();
                foreach ($queue as $obj) {
                    $obj_class = get_class($obj);
                    if (!in_array($obj_class, $classnames)) {
                        $classnames[] = $obj_class;
                    }
                }
                $classnames_string = implode(', ', $classnames);
                throw new midcom_error('Maximum retry count for cleanup reached (' . sizeof($queue) . ' remaining entries with types ' . $classnames_string . '). Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }

        midcom::get('auth')->drop_sudo();
    }
}
?>
