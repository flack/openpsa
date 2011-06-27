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
class openpsa_testcase extends PHPUnit_Framework_TestCase
{
    private static $_class_objects = array();
    private $_testcase_objects = array();

    public static function create_user($login = false)
    {
        $_MIDCOM->auth->request_sudo('midcom.core');
        $person = new midcom_db_person();
        $password = substr('p_' . time(), 0, 11);
        $username = __CLASS__ . ' user ' . time();

        $_MIDCOM->auth->request_sudo('midcom.core');
        if (!$person->create())
        {
            throw new Exception('Person could not be created. Reason: ' . midcom_connection::get_error_string());
        }

        $account = midcom_core_account::get($person);
        $account->set_password($password);
        $account->set_username($username);
        $account->save();
        $_MIDCOM->auth->drop_sudo();
        if ($login)
        {
            if (!$_MIDCOM->auth->login($username, $password))
            {
                throw new Exception('Login for user ' . $username . ' failed');
            }
            $_MIDCOM->auth->_sync_user_with_backend();
        }
        self::$_class_objects[$person->guid] = $person;
        return $person;
    }

    public function run_handler($component, $args)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($topic_guid = $siteconfig->get_node_guid($component))
        {
            $topic = new midcom_db_topic($topic_guid);
        }
        else
        {
            $root_topic = midcom_db_topic::get_cached($GLOBALS['midcom_config']['root_topic']);
            $topic_attributes = array
            (
                'up' => $root_topic->id,
                'component' => $component,
                'name' => 'handler_test_' . time()
            );
            $topic = $this->create_object('midcom_db_topic', $topic_attributes);
        }

        $context = new midcom_core_context(null, $topic);
        $context->set_current();
        $context->set_key(MIDCOM_CONTEXT_URI, midcom_connection::get_url('self') . $topic->name . implode('/', $args));

        // Parser Init: Generate arguments and instantiate it.
        $context->parser = midcom::get('serviceloader')->load('midcom_core_service_urlparser');
        $context->parser->parse($args);
        $handler = $context->get_handler($topic);
        $this->assertTrue($handler->handle());
        return $handler->_context_data[$context->id]['handler']->_handler['handler'][0]->_request_data;
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
    public function register_objects($array)
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

        $_MIDCOM->auth->request_sudo('midcom.core');
        if (!$object->create())
        {
            throw new Exception('Object of type ' . $classname . ' could not be created. Reason: ' . midcom_connection::get_error_string());
        }
        $_MIDCOM->auth->drop_sudo();
        return $object;
    }

    public function prepare_object($classname, $data)
    {
        $object = new $classname();

        foreach ($data as $field => $value)
        {
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

    public static function delete_linked_objects($classname, $link_field, $id)
    {
        $_MIDCOM->auth->request_sudo('midcom.core');
        $qb = call_user_func(array($classname, 'new_query_builder'));
        $qb->add_constraint($link_field, '=', $id);
        $results = $qb->execute();
        foreach ($results as $result)
        {
            $result->_use_rcs = false;
            $result->_use_activitystream = false;
            $result->delete();
        }
        $_MIDCOM->auth->drop_sudo();
    }

    public function tearDown()
    {
        if (midcom_core_context::get()->id != 0)
        {
            midcom_core_context::get(0)->set_current();
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
    }

    public static function TearDownAfterClass()
    {
        self::_process_delete_queue(self::$_class_objects);
        self::$_class_objects = array();
    }

    private static function _process_delete_queue($queue)
    {
        $_MIDCOM->auth->request_sudo('midcom.core');
        $limit = sizeof($queue) * 5;
        $iteration = 0;
        while (!empty($queue))
        {
            $object = array_pop($queue);
            if (!$object->delete())
            {
                if (midcom_connection::get_error() == MGD_ERR_HAS_DEPENDANTS)
                {
                    array_unshift($queue, $object);
                }
                else if (midcom_connection::get_error() == MGD_ERR_NOT_EXISTS)
                {
                    continue;
                }
                else
                {
                    throw new midcom_error('Cleanup test object ' . $object->guid . ' failed, reason: ' . midcom_connection::get_error_string());
                }
            }
            if ($iteration++ > $limit)
            {
                throw new midcom_error('Maximum retry count for cleanup reached');
            }
        }

        $_MIDCOM->auth->drop_sudo();
    }
}
?>