<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * n.n.static site interface class
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_viewer extends midcom_baseclasses_components_viewer
{
    /**
     * Initialize the request switch and the content topic.
     */
    public function _on_initialize()
    {
        // View mode handler, set index viewer according to autoindex setting.
        // These, especially the general view handler, must come last, otherwise we'll hide other
        // handlers
        if ($this->_config->get('autoindex')) {
            $this->_request_switch['autoindex'] = [
                'handler' => [net_nehmer_static_handler_autoindex::class, 'autoindex'],
            ];
        } else {
            $this->_request_switch['index'] = [
                'handler' => [net_nehmer_static_handler_view::class, 'view'],
            ];
        }
    }

    /**
     * Indexes an article.
     *
     * @param datamanager $dm The Datamanager encapsulating the event.
     * @param midcom_services_indexer $indexer The indexer instance to use.
     * @param midcom_db_topic|midcom_core_dbaproxy $topic The topic which we are bound to. If this is not an object, the code
     *     tries to load a new topic instance from the database identified by this parameter.
     */
    public static function index(datamanager $dm, $indexer, $topic)
    {
        $nav = new midcom_helper_nav();
        $node = $nav->get_node($topic->id);

        $document = $indexer->new_document($dm);
        $document->topic_guid = $topic->guid;
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->get_storage()->get_value());
        $document->component = $topic->component;
        $indexer->index($document);
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     */
    private function _populate_node_toolbar()
    {
        $buttons = [];
        $workflow = $this->get_workflow('datamanager');
        if ($this->_topic->can_do('midgard:create')) {
            foreach ($this->_request_data['schemadb']->all() as $name => $schema) {
                $buttons[] = $workflow->get_button("create/{$name}/", [
                    MIDCOM_TOOLBAR_LABEL => sprintf(
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($schema->get('description'))
                    ),
                    MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                ]);
            }
        }

        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $buttons[] = $workflow->get_button('config/', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
            ]);
        }
        $this->_node_toolbar->add_items($buttons);
    }

    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, array $args)
    {
        $this->_request_data['schemadb'] = schemadb::from_path($this->_config->get('schemadb'));

        $this->_populate_node_toolbar();
    }

    /**
     *
     * @param midcom_helper_configuration $config
     * @param integer $id The topic ID
     * @return midcom_core_querybuilder The querybuilder instance
     */
    public static function get_topic_qb(midcom_helper_configuration $config, $id, $order = true)
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $id);

        if ($order) {
            $sort_order = 'ASC';
            $sort_property = $config->get('sort_order');
            if (strpos($sort_property, 'reverse ') === 0) {
                $sort_order = 'DESC';
                $sort_property = substr($sort_property, strlen('reverse '));
            }
            if (strpos($sort_property, 'metadata.') === false) {
                $ref = midcom_helper_reflector::get('midgard_article');
                if (!$ref->property_exists($sort_property)) {
                    $sort_property = 'metadata.' . $sort_property;
                }
            }
            $qb->add_order($sort_property, $sort_order);
        }
        return $qb;
    }
}
