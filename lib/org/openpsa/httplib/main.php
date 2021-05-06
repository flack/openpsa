<?php
/**
 * @package org.openpsa.httplib
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;

/**
 * HTTP content fetching library
 *
 * @package org.openpsa.httplib
 */
class org_openpsa_httplib
{
    private $params = [
        'timeout' => 30,
        'ssl_verify_peer' => false,
        'follow_redirects' => true
    ];

    public $error = '';

    public $basicauth = [
        'user' => false,
        'password' => false,
    ];

    /**
     * Set one of the Guzzle parameters
     */
    public function set_param(string $name, $value)
    {
        $this->params[$name] = $value;
    }

    private function get_client() : Client
    {
        $config = [];
        foreach ($this->params as $key => $value) {
            switch ($key) {
                case 'timeout':
                    $config['timeout'] = $value;
                    break;
                case 'ssl_verify_peer':
                    $config['verify'] = $value;
                    break;
                case 'follow_redirects':
                    $value = ($value) ? 10 : 0;
                    $config['allow_redirects'] = RedirectMiddleware::$defaultSettings;
                    $config['allow_redirects']['max'] = $value;
                    break;
                default:
                    debug_add('Unsupported client parameter ' . $key, MIDCOM_LOG_WARN);
            }
        }
        return new Client($config);
    }

    /**
     * Get contents of given URL
     *
     * @param string $url Fully qualified URL
     * @return string Contents
     */
    public function get(string $url, array $headers = [], string $username = null, string $password = null)
    {
        $request = new Request('GET', $url, $headers);

        return $this->send($request, $username, $password);
    }

    /**
     * Post variables and get the resulting page
     *
     * @param string $uri Fully qualified URL
     * @param array $variables The data to POST (key => value pairs)
     * @param array $headers Additional HTTP headers
     * @return string Contents
     */
    public function post(string $uri, array $variables, array $headers = [])
    {
        // Handle the variables to POST
        if (empty($variables)) {
            $this->error = '$variables is empty';
            debug_add($this->error, MIDCOM_LOG_ERROR);
            return false;
        }
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        $request = new Request('POST', $uri, $headers, http_build_query($variables, null, '&'));

        return $this->send($request, $this->basicauth['user'], $this->basicauth['password']);
    }

    private function send(Request $request, ?string $username, ?string $password)
    {
        $request = $request->withHeader('User-Agent', 'Midgard/' . substr(mgd_version(), 0, 4));

        // Handle basic auth
        if (   !empty($username)
            && !empty($password)) {
            // Set basic auth
            $request = $request->withHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $password));
        }

        $client = $this->get_client();

        try {
            $response = $client->send($request);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            debug_add("Got error '{$this->error}' from HTTP request", MIDCOM_LOG_INFO);
            return false;
        }
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->error = $response->getReasonPhrase();
            debug_add("Got error '{$this->error}' from '{$request->getUri()}'", MIDCOM_LOG_INFO);
            return false;
        }
        return (string) $response->getBody();
    }
}
