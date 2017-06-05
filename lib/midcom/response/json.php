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
        $this->_data = $data;
    }

    /**
     * Sends the response to the client and shuts down the environment
     */
    public function send()
    {
        midcom::get()->skip_page_style = true;
        midcom::get()->cache->content->content_type('application/json');
        midcom::get()->header('Content-type: application/json; charset=' . $this->encoding, $this->code);

        $exporter = new midcom_helper_exporter_json();
        echo $exporter->array2data($this->_data);

        midcom::get()->finish();
    }
}
