<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Wrapper for HTTP relocate responses
 *
 * @package midcom
 */
class midcom_response_relocate extends RedirectResponse
{
    public function setTargetUrl($url)
    {
        if (   substr($url, 0, 1) != "/"
            && !preg_match('|^https?://|', $url)) {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            if ($prefix == '') {
                $prefix = '/';
            }
            $url = $prefix . $url;
            debug_add("This is a relative URL from the local site, prepending anchor prefix: {$url}");
        }
        return parent::setTargetUrl($url);
    }

    public function send()
    {
        if (defined('OPENPSA2_UNITTEST_RUN')) {
            throw new openpsa_test_relocate($this->targetUrl, $this->getStatusCode());
        }

        midcom::get()->cache->content->no_cache();
        debug_add("Relocating to {$this->targetUrl}");
        parent::send();

        midcom::get()->finish();
        return $this; // in reality this is unreachable
    }
}
