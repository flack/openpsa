<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package midcom.baseclasses
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
     */
    protected array $_request = [];

    /**
     * storing response data
     */
    protected array $_response;

    /**
     * the response status
     */
    protected int $_responseStatus = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected ?midcom_core_dbaobject $_object = null;

    /**
     * the request mode (get, create, update, delete)
     */
    protected string $_mode;

    /**
     * the id or guid of the requested object
     *
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
    public function _handler_process(Request $request)
    {
        $this->_request['method'] = strtolower($request->getMethod());

        switch ($this->_request['method']) {
            case 'get':
            case 'delete':
                $this->_request['params'] = $request->query->all();
                break;
            case 'post':
                $this->_request['params'] = array_merge($request->request->all(), $request->query->all());
                break;
            case 'put':
                parse_str(file_get_contents('php://input'), $this->_request['params']);
                break;
        }
        $this->_request['params'] = array_map('trim', $this->_request['params']);

        // determine id / guid
        if (isset($this->_request['params']['id'])) {
            $this->_id = (int) $this->_request['params']['id'];
        }
        if (isset($this->_request['params']['guid'])) {
            $this->_id = $this->_request['params']['guid'];
        }
        return $this->_process_request();
    }

    /**
     * retrieve the object based on classname and request parameters
     * if we got an id, it will try to find an existing one, otherwise it will create a new one
     */
    public function retrieve_object() : midcom_core_dbaobject
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
            throw new midcom_error("Missing id / guid for " . $this->_mode . " mode");
        }

        // try finding existing object
        $this->_object = new $classname($this->_id);
        return $this->_object;
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
            throw new midcom_error("Failed to " . $this->_mode . " object, last error was: " . midcom_connection::get_error_string());
        }
        $this->_responseStatus = Response::HTTP_OK;
        $this->_response["id"] = $this->_object->id;
        $this->_response["guid"] = $this->_object->guid;
        $this->_response["message"] = $this->_mode . " ok";
    }

    /**
     * Do the processing: will call the corresponding handler method and set the mode
     */
    protected function _process_request() : JsonResponse
    {
        try {
            // call corresponding method
            if ($this->_request['method'] == 'get') {
                $this->_mode = 'get';
                $this->handle_get();
            }
            // post and put might be used for create/update
            if (in_array($this->_request['method'], ['post', 'put'])) {
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
            if ($this->_response === null) {
                $this->_stop('Could not handle request, unknown method', Response::HTTP_METHOD_NOT_ALLOWED);
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
     */
    private function _send_response(?string $message = null) : JsonResponse
    {
        // always add status code and message
        $this->_response['code'] = $this->_responseStatus;
        if ($message) {
            $this->_response['message'] = $message;
        }
        $exporter = new midcom_helper_exporter_json();
        return JsonResponse::fromJsonString($exporter->array2data($this->_response), $this->_responseStatus);
    }

    /**
     * stops the application and outputs the info message with corresponding statuscode
     */
    protected function _stop(string $message, int $statuscode = Response::HTTP_INTERNAL_SERVER_ERROR) : never
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
            throw new midcom_error("No object given");
        }
    }

    /**
     * the classname of the object we expect
     */
    abstract public function get_object_classname() : string;

    // these RESTful methods might be overwritten, but contain a default implementation
    public function handle_get()
    {
        $this->retrieve_object();
        $this->_responseStatus = Response::HTTP_OK;
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
            throw new midcom_error("Failed to delete object, last error was: " . midcom_connection::get_error_string());
        }
        // on success, return id
        $this->_responseStatus = Response::HTTP_OK;
        $this->_response["id"] = $this->_object->id;
        $this->_response["guid"] = $this->_object->guid;
        $this->_response["message"] = $this->_mode . "ok";
    }
}
