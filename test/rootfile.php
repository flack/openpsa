<?php
/**
 * Setup file for running unit tests
 *
 * Usage: phpunit --no-globals-backup ./
 */
$mgd_defaults = array
(
    'argv' => array(),

    'user' => 0,
    'admin' => false,
    'root' => false,

    'auth' => false,
    'cookieauth' => false,

    // General host setup
    'page' => 0,
    'debug' => false,

    'self' => '/',
    'prefix' => '',

    'host' => 0,
    'style' => 0,
    'author' => 0,
    'config' => array
    (
        'prefix' => '',
        'quota' => false,
        'unique_host_name' => 'openpsa',
        'auth_cookie_id' => 1,
    ),

    'schema' => array
    (
        'types' => array(),
    ),
);

// Check that the environment is a working one
if (extension_loaded('midgard2'))
{
    if (!ini_get('midgard.superglobals_compat'))
    {
        throw new Exception('You need to set midgard.superglobals_compat=On in your php.ini to run OpenPSA with Midgard2');
    }

    // Initialize the $_MIDGARD superglobal
    $_MIDGARD = $mgd_defaults;
}
else if (extension_loaded('midgard'))
{
    if (file_exists(OPENPSA_TEST_ROOT . 'mgd1-connection.inc.php'))
    {
        include(OPENPSA_TEST_ROOT . 'mgd1-connection.inc.php');
    }
    else
    {
        include(OPENPSA_TEST_ROOT . 'mgd1-connection-default.inc.php');
    }
    $_MIDGARD = array_merge($mgd_defaults, $_MIDGARD);
}
else
{
    throw new Exception("OpenPSA requires Midgard PHP extension to run");
}
if (!class_exists('midgard_topic'))
{
    throw new Exception('You need to install OpenPSA MgdSchemas from the "schemas" directory to the Midgard2 schema directory');
}

define('OPENPSA2_UNITTEST_RUN', true);
define('OPENPSA2_UNITTEST_OUTPUT_DIR', dirname(__FILE__) . '/__output');

function remove_output_dir($dir)
{
    if (is_dir($dir))
    {
        $objects = scandir($dir);
        foreach ($objects as $object)
        {
            if (   $object != "."
                && $object != "..")
            {
                if (filetype($dir . "/" . $object) == "dir")
                {
                    remove_output_dir($dir . "/" . $object);
                }
                else
                {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

remove_output_dir(OPENPSA2_UNITTEST_OUTPUT_DIR);

if (!mkdir(OPENPSA2_UNITTEST_OUTPUT_DIR))
{
    throw new Exception('could not create output directory');
}
if (!mkdir(OPENPSA2_UNITTEST_OUTPUT_DIR . '/rcs'))
{
    throw new Exception('could not create output RCS directory');
}

$GLOBALS['midcom_config_local'] = array();
$GLOBALS['midcom_config_local']['person_class'] = 'openpsa_person';
$GLOBALS['midcom_config_local']['theme'] = 'OpenPsa2';
$GLOBALS['midcom_config_local']['midcom_services_rcs_root'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/rcs';
$GLOBALS['midcom_config_local']['log_filename'] = OPENPSA2_UNITTEST_OUTPUT_DIR . '/midcom.log';


if (file_exists(OPENPSA_TEST_ROOT . 'config.inc.php'))
{
    include(OPENPSA_TEST_ROOT . 'config.inc.php');
}
else
{
    include(OPENPSA_TEST_ROOT . '../config-default.inc.php');
}

// Path to the MidCOM environment
if (!defined('MIDCOM_ROOT'))
{
    define('MIDCOM_ROOT', realpath(OPENPSA_TEST_ROOT . '/../lib'));
}
if (!defined('OPENPSA2_PREFIX'))
{
    define('OPENPSA2_PREFIX', dirname($_SERVER['SCRIPT_NAME']) . '/..');
}
if (! defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/openpsa2-static');
}

$_SERVER = Array();
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_SOFTWARE'] = 'PHPUnit';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REMOTE_ADDR'] = 'unittest dummy connection';
$_SERVER['REQUEST_URI'] = '/midcom-test-init';
$_SERVER['REQUEST_TIME'] = time();

// Include the MidCOM environment for running OpenPSA
require(MIDCOM_ROOT . '/midcom.php');

//Clean up residue cache entries from previous runs
$_MIDCOM->cache->invalidate_all();

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
        self::$_class_objects[] = $person;
        return $person;
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
        $_MIDCOM->auth->request_sudo('midcom.core');
        $limit = sizeof($this->_testcase_objects) * 5;
        $iteration = 0;
        while (!empty($this->_testcase_objects))
        {
            $object = array_pop($this->_testcase_objects);
            if (array_key_exists($object->guid, self::$_class_objects))
            {
                continue;
            }
            if (!$object->delete())
            {
                if (midcom_connection::get_error() == MGD_ERR_HAS_DEPENDANTS)
                {
                    array_unshift($this->_testcase_objects, $object);
                }
                else
                {
                    throw new midcom_error('Cleanup test object ' . $object->guid . 'failed, reason: ' . midcom_connection::get_error_string());
                }
            }
            if ($iteration++ > $limit)
            {
                throw new midcom_error('Maximum retry count for cleanup reached');
            }
        }

        $_MIDCOM->auth->drop_sudo();
    }

    public static function TearDownAfterClass()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        foreach (self::$_class_objects as $object)
        {
            $object->delete();
        }

        $_MIDCOM->auth->drop_sudo();
        self::$_class_objects = array();
    }
}
?>
