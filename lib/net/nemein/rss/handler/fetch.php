<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Feed management class.
 *
 * @package net.nemein.rss
 */
class net_nemein_rss_handler_fetch extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_fetch($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');
        midcom::get()->cache->content->enable_live_mode();

        midcom::get()->disable_limits();
        $data['error'] = '';
        if ($handler_id == '____feeds-rss-feeds_fetch') {
            $data['feed'] = new net_nemein_rss_feed_dba($args[0]);

            $fetcher = new net_nemein_rss_fetch($data['feed']);
            $data['items'] = $fetcher->import();
            $data['error'] = $fetcher->lasterror;
            midcom::get()->metadata->set_request_metadata($data['feed']->metadata->revised, $data['feed']->guid);
            $this->bind_view_to_object($data['feed']);
        } else {
            $data['items'] = array();
            $qb = net_nemein_rss_feed_dba::new_query_builder();
            $qb->add_order('title');
            $qb->add_constraint('node', '=', $this->_topic->id);
            $data['feeds'] = $qb->execute();
            foreach ($data['feeds'] as $feed) {
                $fetcher = new net_nemein_rss_fetch($feed);
                $items = $fetcher->import();
                $data['error'] .= $fetcher->lasterror . "<br /> \n";
                $data['items'] = array_merge($data['items'], $items);
            }
        }

        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_fetch($handler_id, array &$data)
    {
        midcom_show_style('net-nemein-rss-feed-fetch');
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param string $handler_id The current handler's ID
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $this->add_breadcrumb("__feeds/rss/list/", $this->_l10n->get('manage feeds'));

        switch ($handler_id) {
            case '____feeds-rss-feeds_fetch_all':
                $this->add_breadcrumb("__feeds/rss/fetch/all/", $this->_l10n->get('refresh all feeds'));
                break;
            case '____feeds-rss-feeds_fetch':
                $this->add_breadcrumb("__feeds/rss/fetch/{$this->_request_data['feed']->guid}/", $this->_l10n->get('refresh feed'));
                break;
        }
        net_nemein_rss_manage::add_toolbar_buttons($this->_node_toolbar);
    }
}
