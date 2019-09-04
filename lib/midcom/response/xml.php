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
    public function __construct()
    {
        parent::__construct();
        $this->headers->set('Content-type', 'text/xml; charset=' . $this->encoding);
    }

    /**
     * Sends the response to the client and shuts down the environment
     */
    public function sendContent()
    {
        echo '<?xml version="1.0" encoding="' . $this->encoding . '" standalone="yes"?>' . "\n";
        echo "<response>\n";

        foreach ($this->_data as $field => $value) {
            echo $this->_render_tag($field, $value);
        }

        echo "</response>\n";
    }

    private function _render_tag($field, $value)
    {
        $output = '';
        if (is_array($value)) {
            $subtypes = [];
            foreach ($value as $key => $subvalue) {
                if (is_int($key)) {
                    $output .= $this->_render_tag($field, $subvalue);
                } else {
                    $subtypes[$key] = $subvalue;
                }
            }

            if (!empty($subtypes)) {
                $subtype_string = '';
                foreach ($subtypes as $key => $subvalue) {
                    $subtype_string .= $this->_render_tag($key, $subvalue);
                }
                $output .= $this->_render_tag($field, $subtype_string);
            }
        } else {
            $output .= '<' . $field . '>' . $value . '</' . $field . ">\n";
        }
        return $output;
    }
}
