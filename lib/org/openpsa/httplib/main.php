<?php
/**
 * @package org.openpsa.httplib
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Buzz\Browser;
use Buzz\Client\FileGetContents;
use Buzz\Message\Request;
use Buzz\Message\RequestInterface;
use Buzz\Message\Form\FormRequest;
use Buzz\Util\Url;

/**
 * HTTP content fetching library
 *
 * @package org.openpsa.httplib
 */
class org_openpsa_httplib extends midcom_baseclasses_components_purecode
{
    private $params = array(
        'timeout' => 30,
        'ssl_verify_peer' => false,
        'follow_redirects' => true
    );

    public $error = '';

    public $basicauth = array(
        'user' => false,
        'password' => false,
    );

    /**
     * Set one of the HTTP_Request2 parameters
     *
     * @param string $name The parameter's name
     * @param mixed $value The new value
     */
    public function set_param($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @return \Buzz\Browser
     */
    private function get_browser()
    {
        $client = new FileGetContents;
        foreach ($this->params as $key => $value) {
            switch ($key) {
                case 'timeout':
                    $client->setTimeout($value);
                    break;
                case 'ssl_verify_peer':
                    $client->setVerifyPeer($value);
                    break;
                case 'follow_redirects':
                    $value = ($value) ? 10 : 0;
                    $client->setMaxRedirects($value);
                    break;
                default:
                    debug_add('Unsupported client parameter ' . $key, MIDCOM_LOG_WARN);
            }
        }
        return new Browser($client);
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
    public function get($url, array $headers = array(), $username = null, $password = null)
    {
        $request = new Request(RequestInterface::METHOD_GET);

        return (string) $this->send($request, $url, $headers, $username, $password);
    }

    /**
     * Post variables and get the resulting page
     *
     * @param string $uri Fully qualified URL
     * @param array $variables The data to POST (key => value pairs)
     * @param array $headers Additional HTTP headers
     * @return string Contents
     */
    public function post($uri, array $variables, array $headers = array())
    {
        // Handle the variables to POST
        if (empty($variables)) {
            $this->error = '$variables is empty';
            debug_add($this->error, MIDCOM_LOG_ERROR);
            return false;
        }
        $request = new FormRequest;
        $request->setFields($variables);

        return $this->send($request, $uri, $headers, $this->basicauth['user'], $this->basicauth['password']);
    }

    private function send(Request $request, $uri, array $headers, $username, $password)
    {
        $url = new Url($uri);
        $url->applyToRequest($request);

        $request->addHeader('User-Agent: Midgard/' . substr(mgd_version(), 0, 4));

        // Handle basic auth
        if (   !empty($username)
            && !empty($password)) {
            // Set basic auth
            $request->addHeader('Authorization: Basic ' . base64_encode($username . ':' . $password));
        }
        // add custom headers
        if (!empty($headers)) {
            $request->addHeaders($headers);
        }

        $browser = $this->get_browser();

        try {
            $response = $browser->send($request);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            debug_add("Got error '{$this->error}' from HTTP request", MIDCOM_LOG_INFO);
            return false;
        }
        if (!$response->isSuccessful()) {
            $this->error = $response->getReasonPhrase();
            debug_add("Got error '{$this->error}' from '{$uri}'", MIDCOM_LOG_INFO);
            return false;
        }
        return $response->getContent();
    }
}
