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
class midcom_response_json extends midcom_response
{
    public function __construct(array $data = [])
    {
        parent::__construct();
        $this->_data = $data;
        $this->headers->set('Content-type', 'application/json; charset=' . $this->encoding);
    }

    /**
     * Sends the response to the client and shuts down the environment
     */
    public function sendContent()
    {
        $exporter = new midcom_helper_exporter_json();
        echo $exporter->array2data($this->_data);
    }
}
