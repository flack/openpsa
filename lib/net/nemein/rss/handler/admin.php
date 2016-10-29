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
class net_nemein_rss_handler_admin extends midcom_baseclasses_components_handler
{
    private function _load_controller(array &$data)
    {
        $data['schemadb'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_feed'));
        $data['controller'] = midcom_helper_datamanager2_controller::create('simple');
        $data['controller']->schemadb =& $data['schemadb'];
        $data['controller']->set_storage($data['feed']);
        if (! $data['controller']->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for feed {$data['feed']->id}.");
        }
    }

    private function _subscribe_feed($feed_url, $feed_title = null)
    {
        // Try to fetch the new feed
        $rss = net_nemein_rss_fetch::raw_fetch($feed_url);
        // TODO: display error on invalid feed

        if (!$feed_title)
        {
            // If we didn't get the channel title preset
            $feed_title = '';
            if ($rss->get_title())
            {
                $feed_title = $rss->get_title();
            }
        }

        // Find out if the URL is already subscribed, and update it in that case
        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->id);
        $qb->add_constraint('url', '=', $feed_url);
        $feeds = $qb->execute();
        if (count($feeds) == 0)
        {
            $feed = new net_nemein_rss_feed_dba();
            $feed->node = $this->_topic->id;
            $feed->url = $feed_url;
            $feed->title = $feed_title;
            $stat = $feed->create();
        }
        else
        {
            // If we're updating existing feed
            $feed = $feeds[0];
            $feed->title = $feed_title;
            $stat = $feed->update();
        }
        if ($stat)
        {
            $this->_request_data['feeds_subscribed'][$feed->id] = $feed->url;
        }
        return $stat;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_subscribe($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');

        // Arrays for containing data on subscribed and updated feeds
        $data['feeds_subscribed'] = array();
        $data['feeds_updated'] = array();

        // Single feed addition
        if (!empty($_POST['net_nemein_rss_manage_newfeed']['url']))
        {
            $this->_subscribe_feed($_POST['net_nemein_rss_manage_newfeed']['url']);
            // TODO: display error messages
            // TODO: redirect user to edit page if creation succeeded

            return new midcom_response_relocate('__feeds/rss/list/');
        }

        // OPML subscription list import support
        if (   array_key_exists('net_nemein_rss_manage_opml', $_FILES)
            && is_uploaded_file($_FILES['net_nemein_rss_manage_opml']['tmp_name']))
        {
            $opml_file = $_FILES['net_nemein_rss_manage_opml']['tmp_name'];

            // We have OPML file, parse it
            $opml_data = file_get_contents($opml_file);
            unlink($opml_file);

            $opml_parser = xml_parser_create();
            xml_parse_into_struct($opml_parser, $opml_data, $opml_values);
            foreach ($opml_values as $opml_element)
            {
                if ($opml_element['tag'] === 'OUTLINE')
                {
                    // Subscribe to found channels
                    if (isset($opml_element['attributes']['TITLE']))
                    {
                        $this->_subscribe_feed($opml_element['attributes']['XMLURL'], $opml_element['attributes']['TITLE']);
                    }
                    else
                    {
                        $this->_subscribe_feed($opml_element['attributes']['XMLURL']);
                    }
                }
            }
            xml_parser_free($opml_parser);

            return new midcom_response_relocate('__feeds/rss/list/');
        }

        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_subscribe($handler_id, array &$data)
    {
        $data['folder'] = $this->_topic;
        midcom_show_style('net-nemein-rss-feeds-subscribe');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $data['feed'] = new net_nemein_rss_feed_dba($args[0]);
        $data['feed']->require_do('midgard:update');

        $this->_load_controller($data);

        switch ($data['controller']->process_form())
        {
            case 'save':
                // TODO: Fetch the feed here?
                // *** FALL-THROUGH ***

            case 'cancel':
                return new midcom_response_relocate('__feeds/rss/list/');
        }

        midcom::get()->metadata->set_request_metadata($data['feed']->metadata->revised, $data['feed']->guid);
        $this->bind_view_to_object($data['feed']);

        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('net-nemein-rss-feed-edit');
    }

    /**
     * Displays a delete confirmation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $feed = new net_nemein_rss_feed_dba($args[0]);
        $workflow = $this->get_workflow('delete', array
        (
            'object' => $feed,
            'success_url' => '__feeds/rss/list/'
        ));
        return $workflow->run();
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param string $handler_id The current handler's ID
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $this->add_breadcrumb("__feeds/rss/list/", $this->_l10n->get('manage feeds'));

        if ($handler_id == '____feeds-rss-feeds_subscribe')
        {
            $this->add_breadcrumb("__feeds/rss/subscribe/", $this->_l10n->get('subscribe feeds'));
        }
        else if ($handler_id == '____feeds-rss-feeds_edit')
        {
            $this->add_breadcrumb("__feeds/rss/edit/{$this->_request_data['feed']->guid}/", $this->_l10n_midcom->get('edit'));
        }
        net_nemein_rss_manage::add_toolbar_buttons($this->_node_toolbar);
    }
}
