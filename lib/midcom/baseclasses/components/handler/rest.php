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
    protected $_request = array();

    /**
     * storing response data
     *
     * @var array
     */
    protected $_response = null;

    /**
     * the response status
     *
     * @var int
     */
    protected $_responseStatus = 500;

    /**
     * the object we're working on
     *
     * @var midcom_baseclasses_core_dbobject
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
        midcom::get('auth')->_http_basic_auth();
    }
    
    /**
     * the base handler that should be pointed to by the routes
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_process($handler_id, array $args, array &$data)
    {
        $this->_init();
        $this->_process_request();
    }

    /**
     * on init start processing the request data
     */
    protected function _init()
    {
        $this->_request['method'] = strtolower($_SERVER['REQUEST_METHOD']);

        switch($this->_request['method'])
        {
            case 'get':
                $this->_request['params'] = $_GET;
                break;
            case 'post':
                $this->_request['params'] = array_merge($_POST, $_GET);
                break;
            case 'put':
                parse_str(file_get_contents('php://input'), $this->_request['params']);
                break;
            case 'delete':
                $this->_request['params'] = $_GET;
                break;
            default:
                break;
        }
        
        foreach ($this->_request['params'] as $key => $value)
        {
            $this->_request['params'][$key] = $this->_parse_value($value);
        }
        
        // determine id / guid
        if (isset($this->_request['params']['id']))
        {
            $this->_id = intval($this->_request['params']['id']);
        }
        if (isset($this->_request['params']['guid']))
        {
            $this->_id = $this->_request['params']['guid'];
        }
    }

    /**
     * helper function for parsing the request parameters
     *
     * @param string $value
     * @return string
     */
    private function _parse_value($value)
    {
        return trim($value);
    }

    /**
     * retrieve the object based on classname and request parameters
     * if we got an id, it will try to find an existing one, otherwhise it will create a new one
     *
     * @return midcom_baseclasses_core_dbobject
     */
    public function retrieve_object()
    {
        // already got an object
        if ($this->_object)
        {
            return $this->_object;
        }

        $classname = $this->get_object_classname();
        // create mode
        if ($this->_mode == "create")
        {
            $this->_object = new $classname;
            return $this->_object;
        }

        // for all other modes, we need an id or guid
        if (!$this->_id)
        {
            $this->_stop("Missing id / guid for " . $this->_mode . " mode", 500);
        }

        // try finding existing object
        try
        {
            $this->_object = new $classname($this->_id);
            return $this->_object;
        }
        catch (Exception $e)
        {
            $this->_stop($e->getMessage(), $e->getCode());
        }
    }

    /**
     * binds the request data to the object
     */
    public function bind_data()
    {
         $this->_check_object();

         foreach ($this->_request['params'] as $field => $value)
         {
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
        if ($this->_mode == "create")
        {
            $stat = $this->_object->create();
        }
        if ($this->_mode == "update")
        {
            $stat = $this->_object->update();
        }

        if ($stat)
        {
            $this->_responseStatus = 200;
            $this->_response["id"] = $this->_object->id;
            $this->_response["guid"] = $this->_object->guid;
            $this->_response["message"] = $this->_mode . " ok";
        }
        else
        {
            $this->_stop("Failed to " . $this->_mode . " object", 500);
        }
    }

    /**
     * do the processing: will call the corresponding handler method and set the mode
     */
    protected function _process_request()
    {
        try
        {
            // call corresponding method
            if ($this->_request['method'] == 'get')
            {
                $this->_mode = 'get';
                $this->handle_get();
            }
            // post and put might be used for create/update
            if ($this->_request['method'] == 'post' || $this->_request['method'] == 'put')
            {
                if ($this->_id)
                {
                    $this->_mode = 'update';
                    $this->handle_update();
                }
                else
                {
                    $this->_mode = 'create';
                    $this->handle_create();
                }
            }
            if ($this->_request['method'] == 'delete')
            {
                $this->_mode = 'delete';
                $this->handle_delete();
            }

            // no response has been set
            if (is_null($this->_response))
            {
                throw new Exception('Method not allowed', 405);
            }
        }
        catch (Exception $e)
        {
            $this->_stop($e->getMessage(), $e->getCode());
        }

        $this->_send_response();
    }

    /**
     * sends the response as json
     * containing the current response data
     *
     * @param string $message
     */
    protected function _send_response($message = false)
    {
        // always add status code and message
        $this->_response['code'] = $this->_responseStatus;
        if ($message)
        {
            $this->_response['message'] = $message;
        }

        $response = new midcom_response_json($this->_response);
        $response->code = $this->_response['code'];
        $response->send();
    }

    /**
     * stops the application and outputs the info message with corresponding statuscode
     *
     * @param string $message
     * @param int $statuscode
     */
    protected function _stop($message, $statuscode)
    {
        $this->_responseStatus = $statuscode;
        $this->_send_response($message);
    }

    /**
     * helper function for checking if an object has been
     * retrieved before using a function that needs one
     */
    private function _check_object()
    {
        if (!$this->_object)
        {
            $this->_stop("No object given", 500);
        }
    }

    // the classname of the object we expect
    abstract public function get_object_classname();

    // these RESTful methods might be overwritten, but contain a default implementation
    public function handle_get()
    {
        $this->retrieve_object();
        $this->_responseStatus = 200;
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
        $stat = $this->_object->delete();
        if ($stat)
        {
            // on success, return id
            $this->_responseStatus = 200;
            $this->_response["id"] = $this->_object->id;
            $this->_response["guid"] = $this->_object->guid;
            $this->_response["message"] = $this->_mode . "ok";
        }
        else
        {
            $this->_stop("Failed to delete object", 500);
        }
    }
}
?>
