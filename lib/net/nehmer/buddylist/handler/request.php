<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Buddylist request page handler
 *
 * @package net.nehmer.buddylist
 */

class net_nehmer_buddylist_handler_request extends midcom_baseclasses_components_handler
{
    /**
     * The user for which we add a buddylist entry.
     *
     * @param midcom_core_user
     * @access protected
     */
    private $_buddy_user = null;

    /**
     * Processing message.
     *
     * @var net_nehmer_buddylist_entry
     * @access protected
     */
    private $_processing_msg = null;

    /**
     * Untranslated processing message.
     *
     * @var net_nehmer_buddylist_entry
     * @access protected
     */
    private $_processing_msg_raw = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        if ($this->_processing_msg_raw)
        {
            $this->_processing_msg = $this->_l10n->get($this->_processing_msg_raw);
        }

        $this->_request_data['buddy_user'] =& $this->_buddy_user;
        $this->_request_data['processing_msg_raw'] =& $this->_processing_msg_raw;
        $this->_request_data['processing_msg'] =& $this->_processing_msg;
    }

    /**
     * The welcome handler loads the newest asks / bids according to the configuration
     * settings and prepares the type listings.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_request($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Setup.
        $this->_buddy_user = $_MIDCOM->auth->get_user($args[0]);
        if (! $this->_buddy_user)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The user guid {$args[0]} is unknown.");
        }

        $relocate_to = '';
        if (array_key_exists('relocate_to', $_REQUEST))
        {
            $relocate_to = $_REQUEST['relocate_to'];
        }

        if (net_nehmer_buddylist_entry::is_on_buddy_list($this->_buddy_user))
        {
            $this->_processing_msg_raw = 'user already on your buddylist.';
        }
        else
        {
            $entry = new net_nehmer_buddylist_entry();
            $entry->account = $_MIDCOM->auth->user->guid;
            $entry->buddy = $this->_buddy_user->guid;
            $entry->create();
            $this->_processing_msg_raw = 'buddy request sent.';
        }

        if ($relocate_to != '')
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('buddy request'), $this->_l10n->get($this->_processing_msg_raw), 'ok');
            $_MIDCOM->relocate($relocate_to);
        }

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata(time(), null);
        $_MIDCOM->set_pagetitle($this->_topic->extra);
        $this->add_breadcrumb("", $this->_l10n->get('buddy request'));

        return true;
    }

    /**
     * Shows the request page.
     *
     * Normally, you should completely customize this page anyway, therefore the
     * default styles are rather primitive at this time.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_request($handler_id, &$data)
    {
        midcom_show_style('request-sent');
    }
}
?>