<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage view handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_view extends midcom_baseclasses_components_handler
{
    /**
     * The wikipage we're viewing
     *
     * @var net_nemein_wiki_wikipage
     */
    private $_page = null;

    /**
     * The Datamanager 2 controller of the article to display
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $_controller = null;

    /**
     * The Datamanager 2 for article to display
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    public function __construct()
    {
        $this->_request_data['page'] =& $this->_page;
    }

    /**
     * Internal helper, loads the datamanager for the current wikipage. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        if ($GLOBALS['midcom_config']['enable_ajax_editing'])
        {
            $this->_controller = midcom_helper_datamanager2_controller::create('ajax');
            $this->_controller->schemadb =& $this->_request_data['schemadb'];
            $this->_controller->set_storage($this->_page);
            $this->_controller->process_ajax();
            $this->_datamanager =& $this->_controller->datamanager;
        }
        else
        {
            $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);
            if (! $this->_datamanager->autoset_storage($this->_page))
            {
                throw new midcom_error("Failed to create a DM2 instance for wiki page {$this->_page->guid}.");
            }
        }
    }

    private function _populate_toolbar()
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n_midcom']->get('view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'v',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "edit/{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n_midcom']->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                MIDCOM_TOOLBAR_ENABLED => $this->_page->can_do('midgard:update'),
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "delete/{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n_midcom']->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                MIDCOM_TOOLBAR_ENABLED => $this->_page->can_do('midgard:delete'),
            )
        );

        foreach (array_keys($this->_request_data['schemadb']) as $name)
        {
            if ($name == $this->_datamanager->schema->name)
            {
                // The page is already of this type, skip
                continue;
            }

            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "change/{$this->_page->name}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n->get('change to %s'),
                        $this->_l10n->get($this->_request_data['schemadb'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => Array
                    (
                        'change_to' => $name,
                    ),
                    MIDCOM_TOOLBAR_ENABLED => $this->_page->can_do('midgard:update'),
                )
            );
        }
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "whatlinks/{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n']->get('what links'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
            )
        );

        if (midcom::get('auth')->user)
        {
            $user = midcom::get('auth')->user->get_storage();
            if ($this->_page->parameter('net.nemein.wiki:watch', $user->guid))
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "subscribe/{$this->_page->name}/",
                        MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n']->get('unsubscribe'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail.png',
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => Array
                        (
                            'unsubscribe' => 1,
                        ),
                    )
                );
            }
            else
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "subscribe/{$this->_page->name}/",
                        MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n']->get('subscribe'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail.png',
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => Array
                        (
                            'subscribe' => 1,
                        ),
                    )
                );
            }
        }

        if ($this->_page->can_do('midgard:update'))
        {
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel' => 'alternate',
                    'type' => 'application/x-wiki',
                    'title' => $this->_request_data['l10n_midcom']->get('edit'),
                    'href' => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "edit/{$this->_page->name}/",
                )
            );
        }

        if (midcom::get('componentloader')->is_installed('org.openpsa.relatedto'))
        {
            org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_page->guid);
        }

        $_MIDCOM->bind_view_to_object($this->_page, $this->_datamanager->schema->name);
    }

    private function _load_page($wikiword)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('name', '=', $wikiword);
        $result = $qb->execute();

        if (count($result) > 0)
        {
            $this->_page = $result[0];
            return true;
        }

        if ($wikiword == 'index')
        {
            // Autoinitialize
            $this->_topic->require_do('midgard:create');
            $this->_page = net_nemein_wiki_viewer::initialize_index_article($this->_topic);
            if ($this->_page)
            {
                return true;
            }
        }

        $topic_qb = midcom_db_topic::new_query_builder();
        $topic_qb->add_constraint('up', '=', $this->_topic->id);
        $topic_qb->add_constraint('name', '=', $wikiword);
        $topics = $topic_qb->execute();
        if (count($topics) > 0)
        {
            // There is a topic by this URL name underneath, go there
            return false;
        }

        // We need to get the node from NAP for safe redirect
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($this->_topic->id);

        $urlized_wikiword = midcom_helper_misc::generate_urlname_from_string($wikiword);
        if ($urlized_wikiword != $wikiword)
        {
            // Lets see if the page for the wikiword exists
            $qb = net_nemein_wiki_wikipage::new_query_builder();
            $qb->add_constraint('topic', '=', $this->_topic->id);
            $qb->add_constraint('title', '=', $wikiword);
            $result = $qb->execute();
            if (count($result) > 0)
            {
                // This wiki page actually exists, so go there as "Permanent Redirect"
                $_MIDCOM->relocate("{$node[MIDCOM_NAV_ABSOLUTEURL]}{$result[0]->name}/", 301);
            }
        }
        $_MIDCOM->relocate("{$node[MIDCOM_NAV_ABSOLUTEURL]}notfound/" . rawurlencode($wikiword) . '/');
        // This will exit
    }

    /**
     * Can-Handle check against the current wikipage name. We have to do this explicitly
     * in can_handle already, otherwise we would hide all subtopics as the request switch
     * accepts all argument count matches unconditionally.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _can_handle_view($handler_id, array $args, array &$data)
    {
        if (count($args) == 0)
        {
            return $this->_load_page('index');
        }
        else
        {
            return $this->_load_page($args[0]);
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_view($handler_id, $args, &$data, $view_mode = true)
    {
        if (!$this->_page)
        {
            throw new midcom_error_notfound('The requested page could not be found.');
        }

        $this->_load_datamanager();

        if ($this->_datamanager->schema->name == 'redirect')
        {
            $qb = net_nemein_wiki_wikipage::new_query_builder();
            $qb->add_constraint('topic.component', '=', 'net.nemein.wiki');
            $qb->add_constraint('title', '=', $this->_page->url);
            $result = $qb->execute();
            if (count($result) == 0)
            {
                // No matching redirection page found, relocate to editing
                // TODO: Add UI message
                $_MIDCOM->relocate("edit/{$this->_page->name}/");
                // This will exit
            }

            if ($result[0]->topic == $this->_topic->id)
            {
                $_MIDCOM->relocate("{$result[0]->name}/");
            }
            else
            {
                $_MIDCOM->relocate(midcom::get('permalinks')->create_permalink($result[0]->guid));
            }
        }

        $this->_populate_toolbar();
        $this->_view_toolbar->hide_item("{$this->_page->name}/");

        if ($this->_page->name != 'index')
        {
            $this->add_breadcrumb("{$this->_page->name}/", $this->_page->title);
        }

        $_MIDCOM->set_pagetitle($this->_page->title);

        $_MIDCOM->set_26_request_metadata($this->_page->metadata->revised, $this->_page->guid);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        $_MIDCOM->load_library('net.nemein.tag');

        if ($this->_controller)
        {
            $data['wikipage_view'] = $this->_controller->get_content_html();
        }
        else
        {
            $data['wikipage_view'] = $this->_datamanager->get_content_html();
        }
        $data['wikipage'] =& $this->_page;
        if ($this->_config->get('autogenerate_toc'))
        {
            $data['wikipage_view']['content'] = $this->_autogenerate_toc($data['wikipage_view']['content']);
        }
        else
        {
            $data['wikipage_view']['content'] = $data['view']['content'];
        }

        $data['display_related_to'] = $this->_config->get('display_related_to');

        // Replace wikiwords
        // TODO: We should somehow make DM2 do this so it would also work in AJAX previews
        $data['wikipage_view']['content'] = preg_replace_callback($this->_config->get('wikilink_regexp'), array($this->_page, 'replace_wikiwords'), $data['wikipage_view']['content']);

        midcom_show_style('view-wikipage');
    }

    private function _toc_prefix($level)
    {
        $prefix = '';
        for ($i=0; $i < $level; $i++)
        {
            $prefix .= '    ';
        }
        return $prefix;
    }

    /**
     * This function parses HTML content and looks for header tags, making index of them.
     *
     * What exactly it does is looks for all H<num> tags and converts them to named
     * anchors, and prepends a list of links to them to the start of HTML.
     *
     * @todo Parse the heading structure to create OL subtrees based on their relative levels
     */
    private function _autogenerate_toc($content)
    {
        if (!preg_match_all("/(<(h([1-9][0-9]*))[^>]*?>)(.*?)(<\/\\2>)/i", $content, $headings))
        {
            return $content;
        }

        $toc = '';

        $current_tag_level = false;
        $current_list_level = 1;
        $toc .= "\n<ol class=\"midcom_helper_toc_formatter level_{$current_list_level}\">\n";
        foreach ($headings[4] as $key => $heading)
        {
            $anchor = md5($heading);
            $tag_level =& $headings[3][$key];
            $heading_code =& $headings[0][$key];
            $heading_tag =& $headings[2][$key];
            $heading_new_code = "<a name='{$anchor}'></a>{$heading_code}";
            $content = str_replace($heading_code, $heading_new_code, $content);
            $prefix = $this->_toc_prefix($current_list_level);
            if ($current_tag_level === false)
            {
                $current_tag_level = $tag_level;
            }
            else if ($current_tag_level == $tag_level)
            {
                $toc .= "</li>\n";
            }
            else if ($tag_level > $current_tag_level)
            {
                for ($i = $current_tag_level; $i < $tag_level; $i++)
                {
                    $current_tag_level = $tag_level;
                    $current_list_level++;
                    $toc .= $prefix . "<ol class=\"level_{$current_list_level}\">\n";
                    $prefix .= '    ';
                    if ($tag_level > $i + 1)
                    {
                        $toc .= $prefix . "<li>\n";
                    }
                }
            }
            else
            {
                for ($i = $current_tag_level; $i > $tag_level; $i--)
                {
                    $toc .= $prefix . "</li>\n";
                    $current_tag_level = $tag_level;
                    if ($current_list_level > 1)
                    {
                        $current_list_level--;
                        $prefix = $this->_toc_prefix($current_list_level);
                        $toc .= "{$prefix}</ol>\n";
                    }
                }
            }
            $toc .= $prefix . "<li class='{$heading_tag}'><a href='#{$anchor}'>" . strip_tags($heading) .  "</a>";
        }
        for ($i = $current_list_level; $i > 0; $i--)
        {
            $toc .= $prefix . "</li>\n";
            $prefix = $this->_toc_prefix($i - 1);
            $toc .= $prefix . "</ol>\n";
        }

        $content = $toc . $content;

        return $content;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_raw($handler_id, $args, &$data, $view_mode = true)
    {
        $this->_load_page($args[0]);
        if (!$this->_page)
        {
            throw new midcom_error_notfound('The page ' . $args[0] . ' could not be found.');
        }
        $_MIDCOM->skip_page_style = true;
        $this->_load_datamanager();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_raw($handler_id, array &$data)
    {
        if ($this->_controller)
        {
            $data['wikipage_view'] = $this->_controller->get_content_html();
        }
        else
        {
            $data['wikipage_view'] = $this->_datamanager->get_content_html();
        }
        $data['autogenerate_toc'] = $this->_config->get('autogenerate_toc');
        $data['display_related_to'] = $this->_config->get('display_related_to');

        // Replace wikiwords
        $data['wikipage_view']['content'] = preg_replace_callback($this->_config->get('wikilink_regexp'), array($this->_page, 'replace_wikiwords'), $data['wikipage_view']['content']);

        midcom_show_style('view-wikipage-raw');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_source($handler_id, $args, &$data, $view_mode = true)
    {
        $this->_load_page($args[0]);
        if (!$this->_page)
        {
            throw new midcom_error_notfound('The page ' . $args[0] . ' could not be found.');
        }
        $_MIDCOM->skip_page_style = true;
        $this->_load_datamanager();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_source($handler_id, array &$data)
    {
        if ($this->_controller)
        {
            $data['wikipage_view'] = $this->_controller->get_content_html();
        }
        else
        {
            $data['wikipage_view'] = $this->_datamanager->get_content_html();
        }
        $data['autogenerate_toc'] = $this->_config->get('autogenerate_toc');
        $data['display_related_to'] = $this->_config->get('display_related_to');
        $data['wikipage_view']['content'] = $this->_page->content;
        midcom_show_style('view-wikipage-source');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_subscribe($handler_id, array $args, array &$data)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        midcom::get('auth')->require_valid_user();

        if (!$this->_load_page($args[0]))
        {
            throw new midcom_error_notfound('The page ' . $args[0] . ' could not be found.');
        }

        midcom::get('auth')->request_sudo('net.nemein.wiki');

        $user = midcom::get('auth')->user->get_storage();

        if (   array_key_exists('target', $_POST)
            && $_POST['target'] == 'folder')
        {
            // We're subscribing to the whole wiki
            $object = $this->_topic;
            $target = sprintf($this->_request_data['l10n']->get('whole wiki %s'), $this->_topic->extra);
        }
        else
        {
            $object = $this->_page;
            $target = sprintf($this->_request_data['l10n']->get('page %s'), $this->_page->title);
        }

        if (array_key_exists('subscribe', $_POST))
        {
            // Subscribe to page
            $object->parameter('net.nemein.wiki:watch', $user->guid, time());
            midcom::get('uimessages')->add($this->_request_data['l10n']->get('net.nemein.wiki'), sprintf($this->_request_data['l10n']->get('subscribed to changes in %s'), $target), 'ok');
        }
        else
        {
            // Remove subscription
            $object->parameter('net.nemein.wiki:watch', $user->guid, '');
            midcom::get('uimessages')->add($this->_request_data['l10n']->get('net.nemein.wiki'), sprintf($this->_request_data['l10n']->get('unsubscribed from changes in %s'), $target), 'ok');
        }

        midcom::get('auth')->drop_sudo();

        // Redirect to editing
        if ($this->_page->name == 'index')
        {
            $_MIDCOM->relocate("");
        }
        $_MIDCOM->relocate("{$this->_page->name}/");
        // This will exit
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_whatlinks($handler_id, $args, &$data, $view_mode = true)
    {
        $this->_load_page($args[0]);
        if (!$this->_page)
        {
            throw new midcom_error_notfound('The page ' . $args[0] . ' could not be found.');
        }

        $this->_load_datamanager();

        $this->_populate_toolbar();
        $this->_view_toolbar->hide_item("whatlinks/{$this->_page->name}/");

        $qb = net_nemein_wiki_link_dba::new_query_builder();
        $qb->add_constraint('topage', '=', $this->_page->title);
        $data['wikilinks'] = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_whatlinks($handler_id, array &$data)
    {
        if ($this->_controller)
        {
            $data['wikipage_view'] = $this->_controller->get_content_html();
        }
        else
        {
            $data['wikipage_view'] = $this->_datamanager->get_content_html();
        }

        // Replace wikiwords
        $data['wikipage_view']['content'] = preg_replace_callback($this->_config->get('wikilink_regexp'), array($this->_page, 'replace_wikiwords'), $data['wikipage_view']['content']);

        midcom_show_style('view-wikipage-whatlinks');
    }

    /**
     * Callback for sorting wikipages by title
     *
     * @param net_nemein_wiki_wikipage $a
     * @param net_nemein_wiki_wikipage $b
     */
    public static function sort_by_title(net_nemein_wiki_wikipage $a, net_nemein_wiki_wikipage $b)
    {
        return strnatcmp($a->title, $b->title);
    }
}
?>