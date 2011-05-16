<?php
/**
 * @package org.openpsa.httplib
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * HTTP content fetching library
 *
 * @package org.openpsa.httplib
 */
class org_openpsa_httplib extends midcom_baseclasses_components_purecode
{
    private $_client = null;
    
    private $_params = array
    (
        'connect_timeout' => 15,
        'timeout' => 30,
        'ssl_verify_peer' => false
    );
    var $error = '';
    var $basicauth = array
    (
        'user' => false,
        'password' => false,
    );
    var $files = array();
    var $http_timeout = 15;
    var $http_read_timeout = 30;

    /**
     * Initializes the class
     */
    public function __construct()
    {
         require_once('HTTP/Request2.php');
         $this->_component = 'org.openpsa.httplib';
         parent::__construct();
    }

    /**
     * Check whether a HTTP response code is a "successful" one
     *
     * @param int $response_code HTTP response code to check
     * @return boolean Whether HTTP response code is successfull
     */
    private function _is_success($response_code)
    {
        if (   $response_code >= 200
            && $response_code < 300)
        {
            return true;
        }
        debug_add("Got HTTP response code {$response_code}, reporting failure");
        return false;
    }

    /**
     * Get contents of given URL
     *
     * @param string $url Fully qualified URL
     * @param array $headers Additional HTTP headers
     * @param string $username Username, if any
     * @param string $password Password, if any
     * @return string Contents
     */
    public function get($url, $headers = null, $username = null, $password = null)
    {
        $this->_client = new HTTP_Request2($url, HTTP_Request2::METHOD_GET, $this->_params);
        $c =& $this->_client;

        $c->setHeader('User-Agent', $this->_user_agent());

        // Handle basic auth
        if (   $username !== null
            && $password !== null)
        {
            // Set basic auth
            $c->setAuth($username, $password);
        }

        // add custom headers
        if (!empty($headers))
        {
            foreach ($headers as $key => $value)
            {
                $c->setHeader($key, $value);
            }
        }

        try
        {
            $response = $c->send();
        }
        catch (Exception $e)
        {
            $this->error = $e->getMessage();
            debug_add("Got error '{$this->error}' from HTTP_Request", MIDCOM_LOG_INFO);
            return '';
        }
        $code = $response->getStatus();
        if (!$this->_is_success((int)$code))
        {
            $this->error = $this->_http_code2error($code);
            debug_add("Got error '{$this->error}' from '{$uri}'", MIDCOM_LOG_INFO);
            return '';
        }
        return $response->getBody();
    }

    /**
     * Post variables and get the resulting page
     *
     * @param string $url Fully qualified URL
     * @param array &$variables The data to POST (key => value pairs)
     * @param array $headers Additional HTTP headers
     * @return string Contents
     */
    function post($uri, &$variables, $headers = null)
    {
        $this->_client = new HTTP_Request2($uri, HTTP_Request2::METHOD_POST, $this->_params);
        $c =& $this->_client;

        $c->setHeader('User-Agent', $this->_user_agent());

        // Handle basic auth
        if (   isset($this->basicauth['user'])
            && $this->basicauth['user'] !== false
            && isset($this->basicauth['password'])
            && $this->basicauth['password'] !== false)
        {
            // Set basic auth
            $c->setAuth($this->basicauth['user'], $this->basicauth['password']);
        }

        // Handle the variables to POST
        if (   !is_array($variables)
            || empty($variables))
        {
            $this->error = '$variables is not array or is empty';
            debug_add($this->error, MIDCOM_LOG_ERROR);
            return false;
        }
        foreach ($variables as $name => $value)
        {
            $c->addPostParameter($name, $value);
        }
        // add custom headers
        if (!empty($headers))
        {
            foreach ($headers as $key => $value)
            {
                $c->setHeader($key, $value);
            }
        }

        try
        {
            $response = $c->send();
        }
        catch (Exception $e)
        {
            $this->error = $e->getMessage();
            debug_add("Got error '{$this->error}' from HTTP_Request", MIDCOM_LOG_INFO);
            return false;
        }
        $code = $response->getStatus();
        if (!$this->_is_success((int)$code))
        {
            $this->error = $this->_http_code2error($code);
            debug_add("Got error '{$this->error}' from '{$uri}'", MIDCOM_LOG_INFO);
            return false;
        }
        return $response->getBody();
    }

    private function _http_code2error($code)
    {
        switch((int)$code)
        {
            case 200:
                return false;
            case 404:
                return 'Page not found';
            case 401:
                return 'Unauthorized';
            case 403:
                return 'Forbidden';
            // TODO: rest of them
            default:
                return 'Unknown error: ' . $code;
                break;
        }
    }

    private function _user_agent()
    {
        return 'Midgard/' . substr(mgd_version(), 0, 4);
    }
}
?>