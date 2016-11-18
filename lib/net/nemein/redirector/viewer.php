<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Redirector interface class.
 *
 * @package net.nemein.redirector
 */
class net_nemein_redirector_viewer extends midcom_baseclasses_components_request
{
    /**
     * Initialization script, which sets the request switches
     */
    public function _on_initialize()
    {
        // Match /
        if (   is_null($this->_config->get('redirection_type'))
            || (   $this->_topic->can_do('net.nemein.redirector:noredirect')
                && !$this->_config->get('admin_redirection'))) {
            $this->_request_switch['redirect'] = array(
                'handler' => array('net_nemein_redirector_handler_tinyurl', 'list'),
            );
        } else {
            $this->_request_switch['redirect'] = array(
                'handler' => 'redirect'
            );
        }
    }

    /**
     * Add creation link
     */
    public function _on_handle($handler_id, array $args)
    {
        if ($this->_topic->can_do('midgard:create')) {
            // Add the creation link to toolbar
            $this->_node_toolbar->add_item(
                array(
                    MIDCOM_TOOLBAR_URL => "create/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('tinyurl')),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_event.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                )
            );
        }
    }

    /**
     * Check for hijacked URL space
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _can_handle_redirect($handler_id, array $args, array &$data)
    {
        // Process the request immediately
        if (isset($args[0])) {
            $mc = net_nemein_redirector_tinyurl_dba::new_collector('node', $this->_topic->guid);
            $mc->add_constraint('name', '=', $args[0]);
            $mc->add_value_property('code');
            $mc->add_value_property('url');
            $mc->execute();

            $results = $mc->list_keys();

            // No results found
            if (count($results) === 0) {
                return false;
            }

            // Catch first the configuration option for showing editing interface instead
            // of redirecting administrators
            if (   $this->_topic->can_do('net.nemein.redirector:noredirect')
                && !$this->_config->get('admin_redirection')) {
                midcom::get()->relocate("{$this->_topic->name}/edit/{$args[0]}/");
            }
            $guid = key($results);
            $url = $mc->get_subkey($guid, 'url');
            $code = $mc->get_subkey($guid, 'code');

            // Redirection HTTP code
            if (!$code) {
                $code = $this->_config->get('redirection_code');
            }

            midcom::get()->relocate($url, $code);
            // This will exit
        }

        return true;
    }

    /**
     * Process the redirect request
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_redirect($handler_id, array $args, array &$data)
    {
        // Get the topic link and relocate accordingly
        $data['url'] = net_nemein_redirector_viewer::topic_links_to($data);

        if (!$this->_config->get('redirection_metatag')) {
            return new midcom_response_relocate($data['url'], $this->_config->get('redirection_code'));
        }
        // Metatag redirection
        $data['redirection_url'] = $data['url'];
        $data['redirection_speed'] = $this->_config->get('redirection_metatag_speed');

        midcom::get()->head->add_meta_head(
            array(
                'http-equiv' => 'refresh',
                'content' => "{$data['redirection_speed']};url={$data['url']}",
            )
        );
    }

    /**
     * Show redirection page.
     *
     * @param string $handler_id    Handler ID
     * @param array &$data          Pass-by-reference of request data
     */
    public function _show_redirect($handler_id, array &$data)
    {
        midcom_show_style('redirection-page');
    }

    /**
     * Get the URL where the topic links to
     *
     * @param array $data   Request data
     * @return String containing redirection URL
     */
    public static function topic_links_to(array $data)
    {
        switch ($data['config']->get('redirection_type')) {
            case 'node':
                $nap = new midcom_helper_nav();
                $id = $data['config']->get('redirection_node');

                if (is_string($id)) {
                    try {
                        $topic = new midcom_db_topic($id);
                        $id = $topic->id;
                    } catch (midcom_error $e) {
                        $e->log();
                        break;
                    }
                }

                $node = $nap->get_node($id);

                // Node not found, fall through to configuration
                if (!$node) {
                    break;
                }

                return $node[MIDCOM_NAV_FULLURL];

            case 'subnode':
                $nap = new midcom_helper_nav();
                $nodes = $nap->list_nodes($nap->get_current_node());

                // Subnodes not found, fall through to configuration
                if (count($nodes) == 0) {
                    break;
                }

                // Redirect to first node
                $node = $nap->get_node($nodes[0]);
                return $node[MIDCOM_NAV_FULLURL];

            case 'permalink':
                if ($url = midcom::get()->permalinks->resolve_permalink($data['config']->get('redirection_guid'))) {
                    return $url;
                }

            case 'url':
                if ($data['config']->get('redirection_url') != '') {
                    $url = $data['config']->get('redirection_url');

                    // Support varying host prefixes
                    if (strpos($url, '__PREFIX__') !== false) {
                        $url = str_replace('__PREFIX__', midcom_connection::get_url('self'), $url);
                    }

                    return $url;
                }
                // Otherwise fall-through to config
        }

        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        return "{$prefix}config/";
    }
}
