<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wrapper for HTTP responses
 *
 * @package midcom
 */
abstract class midcom_response
{
    /**
     * Character encoding to use
     *
     * @todo determine on the fly?
     * @var string Character encoding
     */
    public $encoding = 'UTF-8';

    /**
     * Response code to use
     *
     * @var int HTTP response code
     */
    public $code = 200;

    /**
     * The data to be transmitted
     *
     * @var array
     */
    protected $_data = array();

    public function __get($name)
    {
        if (!isset($this->_data[$name]))
        {
            return null;
        }
        return $this->_data[$name];
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Sends the response to the client and shuts down the environment
     */
    abstract public function send();
}
?>