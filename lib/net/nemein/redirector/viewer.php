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
class net_nemein_redirector_viewer extends midcom_baseclasses_components_viewer
{
    /**
     * Initialization script, which sets the request switches
     */
    public function _on_initialize()
    {
        // Match /
        if (   $this->_config->get('redirection_type') === null
            || (   $this->_topic->can_do('net.nemein.redirector:noredirect')
                && !$this->_config->get('admin_redirection'))) {
            $this->_request_switch['redirect'] = [
                'handler' => [net_nemein_redirector_handler_tinyurl::class, 'list'],
            ];
        } else {
            $this->_request_switch['redirect'] = [
                'handler' => [net_nemein_redirector_handler_redirect::class, 'index']
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
     */
    public static function topic_links_to(midcom_helper_configuration $config) : string
    {
        switch ($config->get('redirection_type')) {
            case 'node':
                if ($id = $config->get('redirection_node')) {
                    try {
                        $topic = new midcom_db_topic($id);
                        $id = $topic->id;
                    } catch (midcom_error $e) {
                        $e->log();
                        break;
                    }
                    $nap = new midcom_helper_nav();
                    if ($node = $nap->get_node($id)) {
                        return $node[MIDCOM_NAV_FULLURL];
                    }
                }
                // Node not found, fall through to configuration
                break;

            case 'subnode':
                $nap = new midcom_helper_nav();
                if ($nodes = $nap->get_nodes($nap->get_current_node())) {
                    // Redirect to first node
                    return $nodes[0][MIDCOM_NAV_FULLURL];
                }
                // Subnodes not found, fall through to configuration
                break;

            case 'permalink':
                if ($url = midcom::get()->permalinks->resolve_permalink($config->get('redirection_guid'))) {
                    return $url;
                }
                break;

            case 'url':
                if ($url = $config->get('redirection_url')) {
                    // Support varying host prefixes
                    if (str_contains($url, '__PREFIX__')) {
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
