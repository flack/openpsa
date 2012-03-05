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
     * The response mode (currently only json is implemented)
     *
     * @var string
     */
    private $_mode = 'json';

    /**
     * The data to be transmitted
     *
     * @var array
     */
    private $_data = array();

    /**
     * Standard constructor
     *
     * @param string $mode the response mode (currently only json is supported)
     */
    public function __construct($mode)
    {
        $this->_mode = $mode;
    }

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
    public function send()
    {
        if ($this->_mode == 'json')
        {
            midcom::get()->skip_page_style = true;
            midcom::get('cache')->content->content_type('application/json');
            midcom::get()->header('Content-type: application/json; charset=UTF-8');

            echo json_encode($this->_data);
        }

        midcom::get()->finish();
        _midcom_stop_request();
    }
}
?>