<?php
/**
 * @package org.openpsa.mypage
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\helper\autocomplete;

/**
 * My page today handler
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_handler_today extends midcom_baseclasses_components_handler
{
    use org_openpsa_mypage_handler;

    private function _populate_toolbar()
    {
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('weekreview', ['date' => $this->_request_data['this_day']]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week review'),
                MIDCOM_TOOLBAR_GLYPHICON => 'list',
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('day', ['date' => $this->_request_data['prev_day']]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                MIDCOM_TOOLBAR_GLYPHICON => 'chevron-left',
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('day', ['date' => $this->_request_data['next_day']]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                MIDCOM_TOOLBAR_GLYPHICON => 'chevron-right',
            ]
        ];
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_today($handler_id, array $args, array &$data)
    {
        if ($handler_id == 'today') {
            $data['requested_time'] = new DateTime;
        } else {
            $data['requested_time'] = new DateTime($args[0]);
        }

        $this->prepare_timestamps($data['requested_time']);

        $this->_populate_toolbar();

        $data['title'] = $this->_l10n->get_formatter()->date($data['requested_time']);
        midcom::get()->head->set_pagetitle($data['title']);

        // Add the JS file for workingon widget
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.mypage/jquery.epiclock.min.js");
        autocomplete::add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.mypage/mypage.js");

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.mypage/mypage.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.projects/projects.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        //needed js/css-files for journal entries
        org_openpsa_widgets_grid::add_head_elements();
        midcom\workflow\datamanager::add_head_elements();
        org_openpsa_widgets_calendar::add_head_elements();
        org_openpsa_widgets_ui::enable_ui_tab();

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['calendar_url'] = $siteconfig->get_node_relative_url('org.openpsa.calendar');
        $data['projects_relative_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');
        $data['expenses_url'] = $siteconfig->get_node_full_url('org.openpsa.expenses');
        $data['wiki_url'] = $siteconfig->get_node_relative_url('net.nemein.wiki');
        $data['wiki_guid'] = $siteconfig->get_node_guid('net.nemein.wiki');
        $data['journal_url'] = '__mfa/org.openpsa.relatedto/journalentry/list/' . $data['day_start'] . '/';

        return $this->show('show-today');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_updates($handler_id, array $args, array &$data)
    {
        $indexer = midcom::get()->indexer;

        $start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $query = '__TOPIC_URL:"' . midcom::get()->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, 0);
        $data['today'] = $indexer->query($query, $filter);

        $start = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $end = mktime(23, 59, 59, date('m'), date('d') - 1, date('Y'));
        $query = '__TOPIC_URL:"' . midcom::get()->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, $end);
        $data['yesterday'] = $indexer->query($query, $filter);

        return $this->show('show-updates');
    }
}
