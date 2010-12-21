<?php
/**
 * @package org.openpsa.mypage
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.mypage site interface class.
 *
 * Personal summary page into OpenPSA
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler, $args)
    {
        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();
        $_MIDCOM->load_library('org.openpsa.contactwidget');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_savefilter($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        if (array_key_exists('org_openpsa_workgroup_filter', $_POST))
        {
            $session = new midcom_services_session('org.openpsa.core');
            $session->set('org_openpsa_core_workgroup_filter', $_POST['org_openpsa_workgroup_filter']);
            // TODO: Check that session actually was saved
            $ajax = new org_openpsa_helpers_ajax();
            $ajax->simpleReply(true, 'Session saved');
        }
        else
        {
            $ajax = new org_openpsa_helpers_ajax();
            $ajax->simpleReply(false, 'No filter given');
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_userinfo($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        if ($_MIDCOM->auth->user)
        {
            $this->_request_data['virtual_groups']['all'] = $this->_l10n->get('all groups');
            $this->_request_data['virtual_groups'] += org_openpsa_helpers_list::workgroups();
        }

        // This handler uses Ajax, include the handler javascripts
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL."/org.openpsa.helpers/ajaxutils.js");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_userinfo($handler_id, &$data)
    {
        if ($_MIDCOM->auth->user)
        {
            midcom_show_style("show-userinfo");
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_updates($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // Instantiate indexer
        $indexer = $_MIDCOM->get_service('indexer');

        $start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $query = '__TOPIC_URL:"' . $_MIDCOM->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, 0);
        $this->_request_data['today'] = $indexer->query($query, $filter);
        $start = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
        $end = mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));
        $query = '__TOPIC_URL:"' . $_MIDCOM->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, $end);
        $this->_request_data['yesterday'] = $indexer->query($query, $filter);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_updates($handler_id, &$data)
    {
        midcom_show_style("show-updates");
    }
}
?>