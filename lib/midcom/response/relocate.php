<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wrapper for HTTP relocate responses
 *
 * @package midcom
 */
class midcom_response_relocate extends midcom_response
{
    private $_url;

    public function __construct($url, $code = 302)
    {
        $this->_url = $url;
        $this->code = $code;
    }

    public function send()
    {
        if (! preg_match('|^https?://|', $this->_url))
        {
            if (   $this->_url == ''
                || substr($this->_url, 0, 1) != "/")
            {
                $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
                if ($prefix == '')
                {
                    $prefix = midcom::get()->get_page_prefix();
                }
                $this->_url =  "{$prefix}{$this->_url}";
                debug_add("This is a relative URL from the local site, prepending anchor prefix: {$this->_url}");
            }
            else
            {
                $this->_url = midcom::get()->get_host_name() . $this->_url;
                debug_add("This is an absolute URL from the local host, prepending host name: {$this->_url}");
            }

            $location = "Location: {$this->_url}";
        }
        else
        {
            // This is an external URL
            $location = "Location: {$this->_url}";
        }

        midcom::get('cache')->content->no_cache();

        midcom::get()->finish();
        debug_add("Relocating to {$location}");
        midcom::get()->header($location, $response_code);
        _midcom_stop_request();
    }
}
?>