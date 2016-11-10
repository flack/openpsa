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
    /**
     * The URL to redirect to
     *
     * @var string The URL to redirect to
     */
    public $url;

    public function __construct($url, $code = 302)
    {
        $this->url = $url;
        $this->code = $code;
    }

    public function send()
    {
        if (   $this->url == ''
            || (   substr($this->url, 0, 1) != "/")
                && !preg_match('|^https?://|', $this->url)) {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            if ($prefix == '') {
                $prefix = '/';
            }
            $this->url =  "{$prefix}{$this->url}";
            debug_add("This is a relative URL from the local site, prepending anchor prefix: {$this->url}");
        }
        $location = "Location: {$this->url}";

        midcom::get()->cache->content->no_cache();

        debug_add("Relocating to {$location}");
        midcom::get()->header($location, $this->code);
        midcom::get()->finish();
    }
}
