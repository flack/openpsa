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
class net_nemein_rss_manage extends midcom_baseclasses_components_plugin
{
    public function _on_initialize()
    {
        // Ensure we get the correct styles and config
        // @todo: This should be done by midcom core
        $this->_component = 'net.nemein.rss';
        midcom::get('style')->prepend_component_styledir('net.nemein.rss');

        $this->_request_data['node'] = $this->_topic;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_opml($handler_id, array $args, array &$data)
    {
        midcom::get('cache')->content->content_type("text/xml; charset=UTF-8");
        midcom::get()->header("Content-type: text/xml; charset=UTF-8");

        midcom::get()->skip_page_style = true;

        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_order('title');
        $qb->add_constraint('node', '=', $this->_topic->id);
        $data['feeds'] = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_opml($handler_id, array &$data)
    {
        $opml = new OPMLCreator();
        $opml->title = $this->_topic->extra;

        foreach ($data['feeds'] as $feed)
        {
            $item = new FeedItem();
            $item->title = $feed->title;
            $item->xmlUrl = $feed->url;
            $opml->addItem($item);
        }

        echo $opml->createFeed();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_order('title');
        $qb->add_constraint('node', '=', $this->_topic->id);
        $data['feeds'] = $qb->execute();

        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['folder'] = $this->_topic;
        midcom_show_style('net-nemein-rss-feeds-list-header');

        foreach ($data['feeds'] as $feed)
        {
            $data['feed'] = $feed;
            $data['feed_category'] = 'feed:' . md5($feed->url);

            $data['topic'] = $this->_topic;
            midcom_show_style('net-nemein-rss-feeds-list-item');
        }

        midcom_show_style('net-nemein-rss-feeds-list-footer');
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
            if (   $rss
                && isset($rss->channel['title']))
            {
                $feed_title = $rss->channel['title'];
            }
        }

        // Find out if the URL is already subscribed, and update it in that case
        $qb = net_nemein_rss_feed_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->id);
        $qb->add_constraint('url', '=', $feed_url);
        $feeds = $qb->execute();
        if (count($feeds) > 0)
        {
            // If we're updating existing feed
            $feed = $feeds[0];
            $feed->title = $feed_title;
            if ($feed->update())
            {
                $this->_request_data['feeds_updated'][$feed->id] = $feed->url;
                return true;
            }
            return false;
        }
        else
        {
            // Otherwise create new feed
            $feed = new net_nemein_rss_feed_dba();
            $feed->node = $this->_topic->id;
            $feed->url = $feed_url;
            $feed->title = $feed_title;
            if ($feed->create())
            {
                $this->_request_data['feeds_subscribed'][$feed->id] = $feed->url;
                return true;
            }
            return false;
        }
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

            return new midcom_response_relocate('feeds/list/');
        }

        // OPML subscription list import support
        if (   array_key_exists('net_nemein_rss_manage_opml', $_FILES)
            && is_uploaded_file($_FILES['net_nemein_rss_manage_opml']['tmp_name']))
        {
            $opml_file = $_FILES['net_nemein_rss_manage_opml']['tmp_name'];

            // We have OPML file, parse it
            $opml_handle = fopen($opml_file, 'r');
            $opml_data = fread($opml_handle, filesize($opml_file));
            fclose($opml_handle);
            unlink($opml_file);

            $opml_parser = xml_parser_create();
            xml_parse_into_struct($opml_parser, $opml_data, $opml_values );
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

            return new midcom_response_relocate('feeds/list/');
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

    private function _load_controller(&$data)
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
                return new midcom_response_relocate('feeds/list/');
        }

        midcom::get('metadata')->set_request_metadata($data['feed']->metadata->revised, $data['feed']->guid);
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
     * Displays a downloadpage delete confirmation view.
     *
     * Note, that the downloadpage for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation downloadpage
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $data['feed'] = new net_nemein_rss_feed_dba($args[0]);
        $data['feed']->require_do('midgard:delete');

        $this->_load_controller($data);

        if (array_key_exists('net_nemein_rss_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (!$data['feed']->delete())
            {
                throw new midcom_error("Failed to delete feed {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            // Delete ok, relocating to welcome.
            return new midcom_response_relocate('feeds/list/');
        }

        if (array_key_exists('net_nemein_rss_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            return new midcom_response_relocate('feeds/list/');
        }

        midcom::get('metadata')->set_request_metadata($data['feed']->metadata->revised, $data['feed']->guid);
        $this->_view_toolbar->bind_to($data['feed']);
        midcom::get('head')->set_pagetitle("{$this->_topic->extra}: {$data['feed']->title}");

        $this->_update_breadcrumb_line($handler_id);
    }


    /**
     * Shows the loaded downloadpage.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete ($handler_id, array &$data)
    {
        midcom_show_style('net-nemein-rss-feed-delete');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_fetch($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');
        midcom::get('cache')->content->enable_live_mode();

        midcom::get()->disable_limits();

        if ($handler_id == 'feeds_fetch')
        {
            $data['feed'] = new net_nemein_rss_feed_dba($args[0]);

            $fetcher = new net_nemein_rss_fetch($data['feed']);
            $data['items'] = $fetcher->import();

            midcom::get('metadata')->set_request_metadata($data['feed']->metadata->revised, $data['feed']->guid);
            $this->bind_view_to_object($data['feed']);
        }
        else
        {
            $data['items'] = array();
            $qb = net_nemein_rss_feed_dba::new_query_builder();
            $qb->add_order('title');
            $qb->add_constraint('node', '=', $this->_topic->id);
            $data['feeds'] = $qb->execute();
            foreach ($data['feeds'] as $feed)
            {
                $fetcher = new net_nemein_rss_fetch($feed);
                $items = $fetcher->import();
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
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id The current handler's ID
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $this->add_breadcrumb("feeds/list/", $this->_l10n->get('manage feeds'));

        switch ($handler_id)
        {
            case 'feeds_subscribe':
                $this->add_breadcrumb("feeds/subscribe/", $this->_l10n->get('subscribe feeds'));
                break;
            case 'feeds_edit':
                $this->add_breadcrumb("feeds/edit/{$this->_request_data['feed']->guid}/", $this->_l10n_midcom->get('edit'));
                break;
            case 'feeds_delete':
                $this->add_breadcrumb("feeds/delete/{$this->_request_data['feed']->guid}/", $this->_l10n_midcom->get('delete'));
                break;
            case 'feeds_fetch_all':
                $this->add_breadcrumb("feeds/fetch/all/", $this->_l10n->get('refresh all feeds'));
                break;
            case 'feeds_fetch':
                $this->add_breadcrumb("feeds/fetch/{$this->_request_data['feed']->guid}/", $this->_l10n->get('refresh feed'));
                break;
        }
    }
}
?>