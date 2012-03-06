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
class midcom_response_xml extends midcom_response
{
    /**
     * Sends the response to the client and shuts down the environment
     */
    public function send()
    {
        midcom::get()->skip_page_style = true;
        midcom::get('cache')->content->content_type('text/xml');
        midcom::get()->header('Content-type: text/xml; charset=' . $this->encoding);

        echo '<?xml version="1.0" encoding="' . $this->encoding . '" standalone="yes"?>' . "\n";
        echo "<response>\n";

        foreach ($this->_data as $field => $value)
        {
            echo '  <' . $field . '>' . $value . '</' . $field . ">\n";
        }

        echo "</response>\n";

        midcom::get()->finish();
        _midcom_stop_request();
    }
}
?>