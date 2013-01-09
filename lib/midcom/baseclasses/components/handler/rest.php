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
	protected $request = array();
	
	/**
	 * storing response data
	 * @var array
	 */
	protected $response = null;
	
	/**
	 * the response status
	 * @var int
	 */
	protected $responseStatus = 0;
	
	/**
	 * the object we're working on
	 * @var midcom_baseclasses_core_dbobject
	 */
	protected $object;
	
	/**
	 * the mode we're in (reflecting the called methods name)
	 * @var string
	 */
    protected $mode;
	
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
	 * on init start processing raw HTTP request headers & body
	 */
	protected function _init()
	{	    
	    $this->request['resource'] = (isset($_GET['RESTurl']) && !empty($_GET['RESTurl'])) ? $_GET['RESTurl'] : 'index';
	    
	    $this->request['method'] = strtolower($_SERVER['REQUEST_METHOD']);
	    
	    switch($this->request['method'])
	    {
	        case 'get':
	            $this->request['params'] = $_GET;
	            break;
	        case 'post':
	            $this->request['params'] = array_merge($_POST, $_GET);
	            break;
	        case 'put':
	            parse_str(file_get_contents('php://input'), $this->request['params']);
	            break;
	        case 'delete':
	            $this->request['params'] = $_GET;
	            break;
	        default:
	            break;
	    }
	    
	    // parse values
	    foreach ($this->request['params'] as $key => $value)
	    {
	        $this->request['params'][$key] = $this->_parse_value($value);
	    }
	}
		
	private function _parse_value($value)
	{
	    return trim($value);
	}
	
	/**
	 * binds the request data to the object
	 */
	protected function bind_data()
	{
	     $this->_check_object();
	     
	     foreach ($this->request['params'] as $field => $value)
	     {
	         $this->object->{$field} = $value;
	     }
	}
	
	/**
	 * sets the current object
	 * if a id was passed by the request, try to initiate that object
	 * 
	 * @param string $classname the dba object classname
	 */
	protected function retrieve_object($classname)
	{
	    if (isset($this->request['params']['id']))
	    {
	        $qb = $classname::new_query_builder();
	        $qb->add_constraint("id", "=", $this->request['params']['id']);
	        $results = $qb->execute();
	        
	        if (count($results) == 0)
	        {
	            $this->_stop("Unable to find object", 500);
	        }
	        $this->object = $results[0];
	        return true;
	    }
	    // no id given, create new
	    $this->object = new $classname();
	    return true;
	}
			
	/**
	 * do the processing
	 */
	protected function _process_request()
	{
	    try
	    {        
	        // call corresponding method
	        if ($this->request['method'] == 'get')
	        {
	            $this->mode = 'get';
	            $this->handle_get();
	        }
	        // post and put might be used for create/update
	        if ($this->request['method'] == 'post' || $this->request['method'] == 'put')
	        {
	            if (isset($this->request['params']['id']))
	            {
	                $this->mode = 'update';
	                $this->handle_update();
	            }
	            else
	            {
	                $this->mode = 'create';
	                $this->handle_create();
	            }
	        }
	        if ($this->request['method'] == 'delete')
	        {
	            $this->mode = 'delete';
	            $this->handle_delete();
	        }
	        	    
	        // no response has been set
	        if(is_null($this->response))
	        {
	            throw new Exception('Method not allowed', 405);
	        }
	    }
	    catch (Exception $e)
	    {
	        $this->_stop($e->getMessage(), $e->getCode());
	    }
	    
	    // send the response
        $this->_finish();
	}

	protected function _finish()
	{	     	    	
	    $response = new midcom_response_json($this->response);
	    $response->send();
	}
	
	protected function _stop($message, $statuscode)
	{
	    $this->responseStatus = $statuscode;
	    $this->response = array('code' => $statuscode, 'message' => $message);
        $this->_finish();
	}
	
	
	private function _check_object()
	{
	    if (!$this->object)
	    {
	        $this->_stop("No object given", 500);
	    }	    
	}
	
	/**
	 * wrapper for quickly managing the dba object
	 * 
	 * @param string $classname
	 */
	protected function perform($classname)
	{
	    // get a object (create new or find existing)
	    $this->retrieve_object($classname);
	    // bind the request data
	    $this->bind_data();
	    // and submit changes to db
	    $this->persist_object();
	}
	
	/**
	 * writes the changes to the dba object to the db
	 */
	protected function persist_object()
	{
        $this->_check_object();
        
        $stat = false;
        if ($this->mode == "create")
        {
	        $stat = $this->object->create();
        }
        if ($this->mode == "update")
        {
            $stat = $this->object->update();
        }
        
	    if ($stat)
	    {
	        $this->response = array("id" => $this->object->id, "message" => $this->mode . " ok");
	        $this->responseStatus = 200;
	    }
	    else
	    {
	        $this->response = array("error" => "Failed to " . $this->mode . " object");
	        $this->responseStatus = 500;
	    }
	}
		
	// these RESTful methods need to be overwritten
	abstract public function handle_get();
	abstract public function handle_create();
	abstract public function handle_update();
	abstract public function handle_delete();	    
}
?>
