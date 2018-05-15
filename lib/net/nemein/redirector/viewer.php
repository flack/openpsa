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
            $this->_request_switch['redirect'] = [
                'handler' => [net_nemein_redirector_handler_tinyurl::class, 'list'],
            ];
        } else {
            $this->_request_switch['redirect'] = [
                'handler' => [net_nemein_redirector_handler_redirect::class, 'redirect']
            ];
        }
    }

    /**
     * Add creation link
     */
    public function _on_handle($handler_id, array $args)
    {
        if ($this->_topic->can_do('midgard:create')) {
            // Add the creation link to toolbar
            $this->_node_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => "create/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('tinyurl')),
                MIDCOM_TOOLBAR_GLYPHICON => 'external-link',
                MIDCOM_TOOLBAR_ACCESSKEY => 'n',
            ]);
        }
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
                return $nap->get_node($nodes[0])[MIDCOM_NAV_FULLURL];

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
