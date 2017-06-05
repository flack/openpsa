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
class midcom_response
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
    public $code = MIDCOM_ERROK;

    /**
     * The data to be transmitted
     *
     * @var array
     */
    public $_data = [];

    public function __get($name)
    {
        if (!isset($this->_data[$name])) {
            return null;
        }
        return $this->_data[$name];
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function set_data($data)
    {
        $this->_data = $data;
    }
}
