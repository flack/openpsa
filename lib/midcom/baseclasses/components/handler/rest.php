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
	 * @var array
	 */
	protected $_request = array();
	
	/**
	 * storing response data
	 * @var array
	 */
	protected $_response = null;
	
	/**
	 * the response status
	 * @var int
	 */
	protected $_responseStatus = 0;
	
	/**
	 * the object we're working on
	 * @var midcom_baseclasses_core_dbobject
	 */
	protected $_object;
	
	/**
	 * the request mode (get, create, update, delete)
	 * @var string
	 */
    protected $_mode;
	
	/**
	 * (non-PHPdoc)
	 * @see midcom_baseclasses_components_handler::_on_initialize()
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
	 * @param string $classname the dba object classname
	 * @return midcom_baseclasses_core_dbobject
	 */
	public function retrieve_object($classname)
	{
	    if (isset($this->_request['params']['id']))
	    {
	        // try finding existing object
	        try
	        {
	            $obj_id = intval($this->_request['params']['id']);  
	            $this->_object = new $classname($obj_id);
	            return $this->_object;  
	        }
	        catch (Exception $e)
            {
                $this->_stop($e->getMessage(), $e->getCode());
            }
	    }
	    
	    // no id given, create new
	    $this->_object = new $classname();
	    return $this->_object;
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
	        $this->_response = array("id" => $this->_object->id, "message" => $this->_mode . " ok");
	        $this->_responseStatus = 200;
	    }
	    else
	    {
	        $this->_response = array("error" => "Failed to " . $this->_mode . " object");
	        $this->_responseStatus = 500;
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
	            if (isset($this->_request['params']['id']))
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
	        if(is_null($this->_response))
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
	 */
	protected function _send_response()
	{	     	    	
	    $response = new midcom_response_json($this->_response);
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
	    $this->_response = array('code' => $statuscode, 'message' => $message);
        $this->_send_response();
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
	
	/**
	 * wrapper for quickly managing the dba object
	 * 
	 * @param string $classname
	 */
	public function perform($classname)
	{
	    // get a object (create new or find existing)
	    $this->retrieve_object($classname);
	    // bind the request data
	    $this->bind_data();
	    // and submit changes to db
	    $this->persist_object();
	}
			
	// these RESTful methods need to be overwritten
	abstract public function handle_get();
	abstract public function handle_create();
	abstract public function handle_update();
	abstract public function handle_delete();	    
}
?>
