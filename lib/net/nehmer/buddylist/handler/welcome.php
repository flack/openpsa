<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: welcome.php 17555 2008-09-16 19:19:11Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Buddylist welcome page handler
 *
 * @package net.nehmer.buddylist
 */

class net_nehmer_buddylist_handler_welcome extends midcom_baseclasses_components_handler
{
    /**
     * Your buddy list in the form of a username => midcom_core_user listing.
     *
     * @var Array
     * @access protected
     */
    var $_buddies = array();

    /**
     * A listing of meta-information for the current buddylist, indexed by the username,
     * you will have the following keys available:
     *
     * - string new_mail_url (might be null, depending on the net_nehmer_mail_integration config option)
     * - string view_account_url (might be null, depending on the net_nehmer_account_integration config
     *   option)
     * - string delete_checkbox_name
     * - boolean isonline
     *
     * @var Array
     * @access protected
     */
    var $_buddies_meta = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        $this->_request_data['buddies'] =& $this->_buddies;
        $this->_request_data['buddies_meta'] =& $this->_buddies_meta;
        $this->_request_data['delete_form_action'] = "{$prefix}delete.html";
        $this->_request_data['delete_submit_button_name'] = 'net_nehmer_buddylist_delete';
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
    function _handler_welcome($handler_id, $args, &$data)
    {
        $_MIDCOM->load_library('org.openpsa.qbpager');
        $qb = new org_openpsa_qbpager('net_nehmer_buddylist_entry', 'net_nehmer_buddylist');
        $data['qb'] =& $qb;
        $qb->results_per_page = $this->_config->get('buddies_per_page');
        $qb->add_constraint('account', '=', $data['user']->guid);
        $qb->add_constraint('isapproved', '=', true);
        $qb->add_constraint('blacklisted', '=', false);
        $buddies = $qb->execute();

        foreach ($buddies as $buddy)
        {
            $user =& $buddy->get_buddy_user();
            $this->_buddies[$user->username] =& $user;
        }

        $this->_prepare_buddies_meta();

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata(time(), null);
        $_MIDCOM->set_pagetitle($this->_topic->extra);

        return true;
    }

    /**
     * Prepares the metadata for each buddy.
     */
    function _prepare_buddies_meta()
    {
        if ($this->_config->get('net_nehmer_mail_integration'))
        {
            $mail_prefix = $_MIDCOM->get_page_prefix() . $this->_config->get('net_nehmer_mail_integration') . 'mail/new/';
        }
        else
        {
            $mail_prefix = null;
        }
        if ($this->_config->get('net_nehmer_account_integration'))
        {
            $account_prefix = $_MIDCOM->get_page_prefix() . $this->_config->get('net_nehmer_account_integration') . 'view/';
        }
        else
        {
            $account_prefix = null;
        }

        $this->_buddies_meta = Array();

        if ($_MIDCOM->auth->user)
        {
            $online_buddies = net_nehmer_buddylist_entry::list_online_buddies();
        }
        else
        {
            $online_buddies = array();
        }
        foreach ($this->_buddies as $username => $copy)
        {
            $user =& $this->_buddies[$username];
            $this->_buddies_meta[$username] = Array();
            if ($mail_prefix)
            {
                $this->_buddies_meta[$username]['new_mail_url'] = "{$mail_prefix}{$user->guid}.html";
            }
            else
            {
                $this->_buddies_meta[$username]['new_mail_url'] = null;
            }
            if ($account_prefix)
            {
                $this->_buddies_meta[$username]['view_account_url'] = "{$account_prefix}{$user->guid}.html";
            }
            else
            {
                $this->_buddies_meta[$username]['view_account_url'] = null;
            }
            $this->_buddies_meta[$username]['delete_checkbox_name'] = "account_$user->guid";
            $this->_buddies_meta[$username]['is_online'] = array_key_exists($username, $online_buddies);
        }
    }

    /**
     * Shows the welcome page.
     *
     * Normally, you should completely customize this page anyway, therefore the
     * default styles are rather primitive at this time.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_welcome($handler_id, &$data)
    {
        midcom_show_style('welcome');
    }
}
?>