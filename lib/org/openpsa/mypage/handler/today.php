<?php
/**
 * @package org.openpsa.mypage
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

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
        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('weekreview', ['date' => $this->_request_data['this_day']]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('week review'),
            MIDCOM_TOOLBAR_GLYPHICON => 'list',
        ]);
        org_openpsa_widgets_ui::add_navigation_toolbar([[
            MIDCOM_TOOLBAR_URL => $this->router->generate('day', ['date' => $this->_request_data['prev_day']]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chevron-left',
        ], [
            MIDCOM_TOOLBAR_URL => $this->router->generate('day', ['date' => $this->_request_data['next_day']]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chevron-right',
        ]]);
    }

    public function _handler_today(array &$data, string $date = 'now')
    {
        $data['requested_time'] = new DateTime($date);

        $this->prepare_timestamps($data['requested_time']);

        $this->_populate_toolbar();

        $title = $this->_l10n->get_formatter()->date($data['requested_time']);
        midcom::get()->head->set_pagetitle($title);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.mypage/mypage.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['calendar_url'] = $siteconfig->get_node_relative_url('org.openpsa.calendar');
        $data['projects_relative_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');
        $data['wiki_url'] = $siteconfig->get_node_relative_url('net.nemein.wiki');
        $data['wiki_guid'] = $siteconfig->get_node_guid('net.nemein.wiki');
        $data['journal_url'] = '__mfa/org.openpsa.relatedto/journalentry/list/' . $data['day_start'] . '/';

        return $this->show('show-today');
    }

    public function _handler_updates(array &$data)
    {
        $indexer = midcom::get()->indexer;

        $start = strtotime('today');
        $query = '__TOPIC_URL:"' . midcom::get()->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, 0);
        $data['today'] = $indexer->query($query, $filter);

        $start = strtotime('yesterday');
        $end = strtotime('today') - 1;
        $query = '__TOPIC_URL:"' . midcom::get()->get_host_name() . '*"';
        $filter = new midcom_services_indexer_filter_date('__EDITED', $start, $end);
        $data['yesterday'] = $indexer->query($query, $filter);

        return $this->show('show-updates');
    }
}
