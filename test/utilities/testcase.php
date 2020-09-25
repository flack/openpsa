<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\engine;
use midcom\datamanager\renderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use PHPUnit\Framework\TestCase;

/**
 * Base class for unittests, provides some helper methods
 *
 * @package openpsa.test
 */
abstract class openpsa_testcase extends TestCase
{
    private static $_class_objects = [];
    private static $nodes = [];

    private $_testcase_objects = [];

    public static function create_user($login = false) : midcom_db_person
    {
        $person = new midcom_db_person();
        $person->_use_rcs = false;
        $person->extra = substr('p_' . time(), 0, 11);
        $username = uniqid(__CLASS__ . '-user-');

        midcom::get()->auth->request_sudo('midcom.core');
        if (!$person->create()) {
            throw new Exception('Person could not be created. Reason: ' . midcom_connection::get_error_string());
        }

        $account = new midcom_core_account($person);
        $account->set_password($person->extra);
        $account->set_username($username);
        if (!$account->save()) {
            throw new Exception('Account could not be saved. Reason: ' . midcom_connection::get_error_string());
        }
        midcom::get()->auth->drop_sudo();
        if ($login) {
            if (!midcom::get()->auth->login($username, $person->extra)) {
                throw new Exception('Login for user ' . $username . ' failed. Reason: ' . midcom_connection::get_error_string());
            }
        }
        self::$_class_objects[$person->guid] = $person;
        return $person;
    }

    public static function get_component_node($component)
    {
        if (!isset(self::$nodes[$component])) {
            $siteconfig = org_openpsa_core_siteconfig::get_instance();
            midcom::get()->auth->request_sudo($component);
            if ($topic_guid = $siteconfig->get_node_guid($component)) {
                self::$nodes[$component] = new midcom_db_topic($topic_guid);
            } else {
                $qb = midcom_db_topic::new_query_builder();
                $qb->add_constraint('component', '=', $component);
                $qb->set_limit(1);
                $qb->add_order('id');
                $result = $qb->execute();
                if (!empty($result)) {
                    self::$nodes[$component] = $result[0];
                } else {
                    $root_topic = midcom_db_topic::get_cached(midcom::get()->config->get('midcom_root_topic_guid'));

                    $topic_attributes = [
                        'up' => $root_topic->id,
                        'component' => $component,
                        'name' => 'handler_' . get_called_class() . time()
                    ];
                    self::$nodes[$component] = self::create_class_object(midcom_db_topic::class, $topic_attributes);
                }
            }
            midcom::get()->auth->drop_sudo();
        }

        return self::$nodes[$component];
    }

    /**
     * Sets a config value for a component.
     *
     * This is useful e.g. in cases of form submissions, because the values set directly over the baseclass may
     * get lost when the submit_dm helpers run the handler more than once
     *
     * @param string $component
     * @param string $key
     * @param mixed $value
     */
    public function set_config($component, $key, $value)
    {
        $config = midcom_baseclasses_components_configuration::get($component, 'config');
        $config->set($key, $value);
        midcom_baseclasses_components_configuration::set($component, 'config', new midcom_helper_configuration($config->get_all()));
    }

    public function run_handler($topic, array $args = [])
    {
        if (is_object($topic)) {
            $component = $topic->component;
        } else {
            $component = $topic;
            $topic = $this->get_component_node($component);
        }

        $context = midcom_core_context::enter(midcom_connection::get_url('self') . implode('/', $args) . '/', $topic);

        $request = Request::createFromGlobals();
        $request->attributes->set('context', $context);

        $result = $GLOBALS['kernel']->handle($request, KernelInterface::SUB_REQUEST);

        $this->assertTrue($result !== false, $component . ' handle returned false on ./' . implode('/', $args) . '/');
        $data = $context->get_custom_key('request_data');
        $data['__openpsa_testcase_response'] = $result;

        // added to simulate http uri composition
        $_SERVER['REQUEST_URI'] = '/' . $topic->name . $context->get_key(MIDCOM_CONTEXT_URI);

        return $data;
    }

    /**
     * @deprecated This is redundant, since styles are evaluated when the response is constructed
     * @param array $data
     * @return string
     */
    public function show_handler($data)
    {
        $context = midcom_core_context::get();
        $show_handler = $context->get_key(MIDCOM_CONTEXT_SHOWCALLBACK);

        midcom::get()->style->enter_context($context);
        ob_start();
        call_user_func($show_handler, $context->id);
        $output = ob_get_contents();
        ob_end_clean();
        midcom::get()->style->leave_context();
        return $output;
    }

    public function set_post_data(array $post_data)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $post_data;
        $_REQUEST = $_POST;
    }

    public function set_get_data(array $get_data)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $get_data;
        $_REQUEST = $_GET;
    }

    public function set_dm2_formdata(midcom_helper_datamanager2_controller $controller, array $formdata)
    {
        $formname = substr($controller->formmanager->namespace, 0, -1);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $form_values = $controller->formmanager->form->exportValues();
        $_POST = array_merge($form_values, $formdata);

        $_POST['_qf__' . $formname] = '';
        $_POST['midcom_helper_datamanager2_save'] = [''];
        $_REQUEST = $_POST;
    }

    public function submit_dm2_form($controller_key, array $formdata, $component, array $args = [])
    {
        $this->reset_server_vars();
        $data = $this->run_handler($component, $args);
        $this->set_dm2_formdata($data[$controller_key], $formdata);

        try {
            $data = $this->run_handler($component, $args);
            if (array_key_exists($controller_key, $data)) {
                $this->assertEquals([], $data[$controller_key]->formmanager->form->_errors, 'Form validation failed');
            }
            $this->assertInstanceOf(midcom_response_relocate::class, $data['__openpsa_testcase_response'], 'Form did not relocate');
            return $data['__openpsa_testcase_response']->getTargetUrl();
        } catch (openpsa_test_relocate $e) {
            $url = $e->getMessage();
            $url = preg_replace('/^\//', '', $url);
            return $url;
        }
    }

    /**
     * same logic as submit_dm2_form, but this method does not expect a relocate
     */
    public function submit_dm2_no_relocate_form($controller_key, array $formdata, $component, array $args = [])
    {
        $this->reset_server_vars();
        $data = $this->run_handler($component, $args);
        $this->set_dm2_formdata($data[$controller_key], $formdata);
        $data = $this->run_handler($component, $args);

        $this->assertEquals([], $data[$controller_key]->formmanager->form->_errors, 'Form validation failed');

        return $data;
    }

    public function set_dm_formdata(controller $controller, array $formdata, $button = 'save')
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        include_once 'datamanager/form.php';

        $dm = $controller->get_datamanager();
        $view = $dm->get_form()->createView();
        $renderer = new renderer(new engine);
        $renderer->set_template($view, new datamanager_form($renderer));
        $data = eval('return ' . $renderer->block($view, 'form') . ';');
        $formname = key($data);
        $data[$formname]['form_toolbar'][$button . '0'] = '';

        $_POST = [
            $formname => array_merge($data[$formname], $formdata)
        ];

        $_REQUEST = $_POST;
    }

    public function submit_dm_form($controller_key, array $formdata, $component, array $args = [], $button = 'save')
    {
        $this->reset_server_vars();
        $data = $this->run_handler($component, $args);
        if (   array_key_exists('__openpsa_testcase_response', $data)
            && $data['__openpsa_testcase_response'] instanceof RedirectResponse) {
            $this->fail('Handler relocated to ' . $data['__openpsa_testcase_response']->getTargetUrl() . ' during form setup');
        }
        $this->set_dm_formdata($data[$controller_key], $formdata, $button);

        try {
            $data = $this->run_handler($component, $args);
            if (array_key_exists($controller_key, $data)) {
                $this->assertEquals([], $data[$controller_key]->get_errors(), 'Form validation failed');
            }
            $this->assertInstanceOf(RedirectResponse::class, $data['__openpsa_testcase_response'], 'Form did not relocate');
            return $data['__openpsa_testcase_response']->getTargetUrl();
        } catch (openpsa_test_relocate $e) {
            $url = $e->getMessage();
            $url = preg_replace('/^\//', '', $url);
            return $url;
        }
    }

    public function submit_dm_no_relocate_form($controller_key, array $formdata, $component, array $args = [], $button = 'save')
    {
        $this->reset_server_vars();
        $data = $this->run_handler($component, $args);
        $this->set_dm_formdata($data[$controller_key], $formdata, $button);
        $data = $this->run_handler($component, $args);

        $errors = $data[$controller_key]->get_datamanager()->get_form()->getErrors(true);

        if ($errors->count() > 0) {
            $message = 'Form validation failed: ';

            foreach ($errors as $error) {
                $message .= "\n". $error->getOrigin()->getName() . ': ' . $error->getMessage();
            }

            $this->fail($message);
        }

        return $data;
    }

    public function get_dialog_url()
    {
        $head_elements = midcom::get()->head->get_jshead_elements();
        foreach (array_reverse($head_elements) as $element) {
            if (   !empty($element['content'])
                && preg_match('/refresh_opener\(.*?\);/', $element['content'])) {
                return preg_replace('/refresh_opener\("*\/*(.*?)"*, .*\);/', '$1', $element['content']);
            }
        }
        $this->fail('No refresh URL found');
    }

    public function run_relocate_handler($component, array $args = [])
    {
        $url = null;
        try {
            $data = $this->run_handler($component, $args);
            if (!array_key_exists('__openpsa_testcase_response', $data)) {
                $data['__openpsa_testcase_response'] = null;
            }
            $this->assertInstanceOf(midcom_response_relocate::class, $data['__openpsa_testcase_response'], 'handler did not relocate');
            $url = $data['__openpsa_testcase_response']->getTargetUrl();
        } catch (openpsa_test_relocate $e) {
            $url = $e->getMessage();
        }

        $url = preg_replace('/^\//', '', $url);
        return $url;
    }

    /**
     * @param string $classname
     * @param array $data
     * @return midcom_core_dbaobject
     */
    public function create_object($classname, array $data = [])
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
        foreach ($array as $object) {
            $this->_testcase_objects[$object->guid] = $object;
        }
    }

    private static function _create_object($classname, array $data)
    {
        $presets = [
            '_use_rcs' => false,
        ];
        $data = array_merge($presets, $data);
        $object = self::prepare_object($classname, $data);

        midcom::get()->auth->request_sudo('midcom.core');
        if (!$object->create()) {
            throw new Exception('Object of type ' . $classname . ' could not be created. Reason: ' . midcom_connection::get_error_string());
        }
        midcom::get()->auth->drop_sudo();
        return $object;
    }

    public static function prepare_object($classname, array $data)
    {
        $object = new $classname();

        foreach ($data as $field => $value) {
            if (strpos($field, '.') !== false) {
                $parts = explode('.', $field);
                $object->{$parts[0]}->{$parts[1]} = $value;
                continue;
            }
            $object->$field = $value;
        }
        return $object;
    }

    public static function create_class_object($classname, array $data = [])
    {
        $object = self::_create_object($classname, $data);
        self::$_class_objects[$object->guid] = $object;
        return $object;
    }

    public static function create_persisted_object($classname, array $data = [])
    {
        return self::_create_object($classname, $data);
    }

    public static function delete_linked_objects($classname, $link_field, $id)
    {
        midcom::get()->auth->request_sudo('midcom.core');
        $qb = call_user_func([$classname, 'new_query_builder']);
        $qb->add_constraint($link_field, '=', $id);
        $results = $qb->execute();

        foreach ($results as $result) {
            $result->_use_rcs = false;
            $result->delete();
            $result->purge();
        }
        midcom::get()->auth->drop_sudo();
    }

    public function reset_server_vars()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            unset($_SERVER['REQUEST_METHOD']);
        }
        if (!empty($_POST)) {
            $_POST = [];
        }
        if (!empty($_FILES)) {
            $_FILES = [];
        }
        if (!empty($_GET)) {
            $_GET = [];
        }
        if (!empty($_REQUEST)) {
            $_REQUEST = [];
        }
    }

    public function tearDown() : void
    {
        $this->reset_server_vars();

        while (midcom_core_context::get()->id != 0) {
            midcom_core_context::leave();
        }

        if (!midcom::get()->config->get('auth_allow_sudo')) {
            midcom::get()->config->set('auth_allow_sudo', true);
        }

        while (midcom::get()->auth->is_component_sudo()) {
            midcom::get()->auth->drop_sudo();
        }

        //if object is also in class queue, we delay its deletion
        $queue = array_diff_key($this->_testcase_objects, self::$_class_objects);

        self::_process_delete_queue('method', $queue);
        $this->_testcase_objects = [];
        org_openpsa_mail_backend_unittest::flush();
        midcom_compat_environment::flush_registered_headers();
        midcom_baseclasses_components_configuration::reset();
    }

    public static function TearDownAfterClass() : void
    {
        self::_process_delete_queue('class', self::$_class_objects);
        self::$_class_objects = [];
        midcom::get()->auth->logout();
    }

    private static function _process_delete_queue($queue_name, $queue)
    {
        midcom::get()->auth->request_sudo('midcom.core');
        $limit = count($queue) * 5;
        $iteration = 0;
        // we reverse the queue here because parents are usually created
        // before their children. Normally, mgd core should catch parent
        // deletion when children exist, but this doesn't always seem to work
        $queue = array_reverse($queue);
        while (!empty($queue)) {
            $object = array_pop($queue);
            $object->_use_rcs = false;
            try {
                $stat = $object->refresh();
                if ($stat === false) {
                    // we can only assume this means that the object is already deleted.
                    // Normally, the error codes from core should tell us later on, too, but
                    // they don't seem to be reliable in all versions
                    continue;
                }
                $stat = $object->delete();
            } catch (midcom_error $e) {
                $stat = false;
            }
            if (!$stat) {
                if (   midcom_connection::get_error() == MGD_ERR_HAS_DEPENDANTS
                    || midcom_connection::get_error() == MGD_ERR_OK) {
                    array_unshift($queue, $object);
                } elseif (midcom_connection::get_error() == MGD_ERR_NOT_EXISTS) {
                    continue;
                } else {
                    throw new midcom_error('Cleanup ' . get_class($object) . ' ' . $object->guid . ' failed, reason: ' . midcom_connection::get_error_string());
                }
            } else {
                $object->purge();
                if (   $object instanceof midcom_db_topic
                    && !empty(self::$nodes[$object->component])
                    && self::$nodes[$object->component]->guid == $object->guid) {
                    unset(self::$nodes[$object->component]);
                }
            }
            if ($iteration++ > $limit) {
                $classnames = [];
                foreach ($queue as $obj) {
                    $ref = midcom_helper_reflector::get($obj);
                    $obj_class = get_class($obj) . ' ' . $ref->get_object_label($obj);
                    if (!in_array($obj_class, $classnames)) {
                        $classnames[] = $obj_class;
                    }
                }
                $classnames_string = implode(', ', $classnames);
                self::fail('Maximum retry count for ' . $queue_name . ' cleanup reached (' . count($queue) . ' remaining entries: ' . $classnames_string . '). Last Midgard error was: ' . midcom_connection::get_error_string());
                $queue = [];
            }
        }

        midcom::get()->auth->drop_sudo();
    }
}
