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
     * @param array $data The local request data.
     * @param string $guid The object's GUID
     */
    public function _handler_fetch(array &$data, string $guid = null)
    {
        $this->_topic->require_do('midgard:create');
        midcom::get()->cache->content->no_cache();

        midcom::get()->disable_limits();
        $data['error'] = '';
        if ($guid !== null) {
            $data['feed'] = new net_nemein_rss_feed_dba($guid);

            $fetcher = new net_nemein_rss_fetch($data['feed']);
            $data['items'] = $fetcher->import();
            $data['error'] = $fetcher->lasterror;
            midcom::get()->metadata->set_request_metadata($data['feed']->metadata->revised, $data['feed']->guid);
            $this->bind_view_to_object($data['feed']);
        } else {
            $data['items'] = [];
            $qb = net_nemein_rss_feed_dba::new_query_builder();
            $qb->add_order('title');
            $qb->add_constraint('node', '=', $this->_topic->id);
            foreach ($qb->execute() as $feed) {
                $fetcher = new net_nemein_rss_fetch($feed);
                $items = $fetcher->import();
                $data['error'] .= $fetcher->lasterror . "<br /> \n";
                $data['items'] = array_merge($data['items'], $items);
            }
        }

        $this->_update_breadcrumb_line($guid);
        return $this->show('net-nemein-rss-feed-fetch');
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     */
    private function _update_breadcrumb_line(?string $guid)
    {
        $this->add_breadcrumb($this->router->generate('feeds_list'), $this->_l10n->get('manage feeds'));

        if ($guid === null) {
            $this->add_breadcrumb($this->router->generate('feeds_fetch_all'), $this->_l10n->get('refresh all feeds'));
        } else {
            $this->add_breadcrumb($this->router->generate('feeds_list', ['guid' => $guid]), $this->_l10n->get('refresh feed'));
        }

        net_nemein_rss_manage::add_toolbar_buttons($this->_node_toolbar);
    }
}
