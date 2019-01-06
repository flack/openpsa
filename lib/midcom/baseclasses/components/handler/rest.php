<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package midcom.baseclasses
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * generic REST handler baseclass
 * this needs to be extended by every REST handler in the application
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_handler_rest extends midcom_baseclasses_components_handler
{
    /**
     * storing request data
     *
     * @var array
     */
    protected $_request = [];

    /**
     * storing response data
     *
     * @var array
     */
    protected $_response;

    /**
     * the response status
     *
     * @var int
     */
    protected $_responseStatus = MIDCOM_ERRCRIT;

    /**
     * the object we're working on
     *
     * @var midcom_core_dbaobject
     */
    protected $_object = false;

    /**
     * the request mode (get, create, update, delete)
     *
     * @var string
     */
    protected $_mode;

    /**
     * the id or guid of the requested object
     * @var mixed
     */
    protected $_id = false;

    /**
     * @inheritDoc
     */
    public function _on_initialize()
    {
        // try logging in over basic auth
        midcom::get()->auth->require_valid_user('basic');
    }

    /**
     * the base handler that should be pointed to by the routes
     */
    public function _handler_process()
    {
        $this->_init();
        return $this->_process_request();
    }

    /**
     * on init start processing the request data
     */
    protected function _init()
    {
        $this->_request['method'] = strtolower($_SERVER['REQUEST_METHOD']);

        switch ($this->_request['method']) {
            case 'get':
            case 'delete':
                $this->_request['params'] = $_GET;
                break;
            case 'post':
                $this->_request['params'] = array_merge($_POST, $_GET);
                break;
            case 'put':
                parse_str(file_get_contents('php://input'), $this->_request['params']);
                break;
        }
        $this->_request['params'] = array_map('trim', $this->_request['params']);

        // determine id / guid
        if (isset($this->_request['params']['id'])) {
            $this->_id = intval($this->_request['params']['id']);
        }
        if (isset($this->_request['params']['guid'])) {
            $this->_id = $this->_request['params']['guid'];
        }
    }

    /**
     * retrieve the object based on classname and request parameters
     * if we got an id, it will try to find an existing one, otherwise it will create a new one
     *
     * @return midcom_core_dbaobject
     */
    public function retrieve_object()
    {
        // already got an object
        if ($this->_object) {
            return $this->_object;
        }

        $classname = $this->get_object_classname();
        // create mode
        if ($this->_mode == "create") {
            $this->_object = new $classname;
            return $this->_object;
        }

        // for all other modes, we need an id or guid
        if (!$this->_id) {
            $this->_stop("Missing id / guid for " . $this->_mode . " mode", MIDCOM_ERRCRIT);
        }

        // try finding existing object
        try {
            $this->_object = new $classname($this->_id);
            return $this->_object;
        } catch (Exception $e) {
            $this->_stop($e->getMessage(), $e->getCode());
        }
    }

    /**
     * binds the request data to the object
     */
    public function bind_data()
    {
        $this->_check_object();

        foreach ($this->_request['params'] as $field => $value) {
            $this->_object->{$field} = $value;
        }
    }

    /**
     * writes the changes to the dba object to the db
     */
    public function persist_object()
    {
        $this->_check_object();

        $stat = false;
        if ($this->_mode == "create") {
            $stat = $this->_object->create();
        }
        if ($this->_mode == "update") {
            $stat = $this->_object->update();
        }

        if (!$stat) {
            $this->_stop("Failed to " . $this->_mode . " object, last error was: " . midcom_connection::get_error_string(), MIDCOM_ERRCRIT);
        }
        $this->_responseStatus = MIDCOM_ERROK;
        $this->_response["id"] = $this->_object->id;
        $this->_response["guid"] = $this->_object->guid;
        $this->_response["message"] = $this->_mode . " ok";
    }

    /**
     * Do the processing: will call the corresponding handler method and set the mode
     *
     * @return midcom_response_json
     */
    protected function _process_request()
    {
        try {
            // call corresponding method
            if ($this->_request['method'] == 'get') {
                $this->_mode = 'get';
                $this->handle_get();
            }
            // post and put might be used for create/update
            if ($this->_request['method'] == 'post' || $this->_request['method'] == 'put') {
                if ($this->_id) {
                    $this->_mode = 'update';
                    $this->handle_update();
                } else {
                    $this->_mode = 'create';
                    $this->handle_create();
                }
            }
            if ($this->_request['method'] == 'delete') {
                $this->_mode = 'delete';
                $this->handle_delete();
            }

            // no response has been set
            if (is_null($this->_response)) {
                $this->_stop('Could not handle request, unknown method', 405);
            }
        } catch (midcom_error $e) {
            $this->_responseStatus = $e->getCode();
            return $this->_send_response($e->getMessage());
        }

        return $this->_send_response();
    }

    /**
     * sends the response as json
     * containing the current response data
     *
     * @param string $message
     * @return midcom_response_json
     */
    private function _send_response($message = false)
    {
        // always add status code and message
        $this->_response['code'] = $this->_responseStatus;
        if ($message) {
            $this->_response['message'] = $message;
        }

        $response = new midcom_response_json($this->_response);
        $response->code = $this->_response['code'];
        return $response;
    }

    /**
     * stops the application and outputs the info message with corresponding statuscode
     *
     * @param string $message
     * @param int $statuscode
     */
    protected function _stop($message, $statuscode = MIDCOM_ERRCRIT)
    {
        throw new midcom_error($message, $statuscode);
    }

    /**
     * helper function for checking if an object has been
     * retrieved before using a function that needs one
     */
    private function _check_object()
    {
        if (!$this->_object) {
            $this->_stop("No object given", MIDCOM_ERRCRIT);
        }
    }

    // the classname of the object we expect
    abstract public function get_object_classname();

    // these RESTful methods might be overwritten, but contain a default implementation
    public function handle_get()
    {
        $this->retrieve_object();
        $this->_responseStatus = MIDCOM_ERROK;
        $this->_response["object"] = $this->_object;
        $this->_response["message"] = "get ok";
    }

    public function handle_create()
    {
        $this->retrieve_object();
        $this->bind_data();
        $this->persist_object();
    }

    public function handle_update()
    {
        $this->retrieve_object();
        $this->bind_data();
        $this->persist_object();
    }

    public function handle_delete()
    {
        $this->retrieve_object();
        if (!$this->_object->delete()) {
            $this->_stop("Failed to delete object, last error was: " . midcom_connection::get_error_string(), MIDCOM_ERRCRIT);
        }
        // on success, return id
        $this->_responseStatus = MIDCOM_ERROK;
        $this->_response["id"] = $this->_object->id;
        $this->_response["guid"] = $this->_object->guid;
        $this->_response["message"] = $this->_mode . "ok";
    }
}
