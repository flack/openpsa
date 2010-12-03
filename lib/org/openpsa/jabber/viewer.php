<?php
/**
 * @package org.openpsa.jabber
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.jabber site interface class.
 *
 * Instant Messaging powered by JabberApplet
 *
 * @package org.openpsa.jabber
 */
class org_openpsa_jabber_viewer extends midcom_baseclasses_components_request
{
    /**
     * Constructor.
     */
    public function _on_initialize()
    {
        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();

        // Match /applet
        $this->_request_switch[] = array
        (
            'fixed_args' => 'applet',
            'handler' => 'applet'
        );

        // Match /summary
        $this->_request_switch[] = array
        (
            'fixed_args' => 'summary',
            'handler' => 'summary'
        );

        // Match /
        $this->_request_switch[] = array
        (
            'handler' => 'frontpage'
        );
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_applet($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // We're using a popup here
        $_MIDCOM->skip_page_style = true;
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_applet($handler_id, &$data)
    {
        midcom_show_style("jabber-applet");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_summary($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_summary($handler_id, &$data)
    {
        midcom_show_style("show-summary");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_frontpage($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_frontpage($handler_id, &$data)
    {
        midcom_show_style("show-frontpage");
    }
}