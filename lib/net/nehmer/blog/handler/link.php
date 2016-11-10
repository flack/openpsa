<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.blog link handler
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_link extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     */
    private $_content_topic = null;

    /**
     * The article link which has been created
     *
     * @var net_nehmer_blog_link_dba
     */
    private $_link = null;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic = $this->_request_data['content_topic'];
    }

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_link'));
    }

    public function get_schema_name()
    {
        return 'link';
    }

    public function get_schema_defaults()
    {
        $defaults = array();
        if (isset($_GET['article'])) {
            $defaults['article'] = $_GET['article'];
        } else {
            $defaults['topic'] = $this->_topic->id;
        }
        return $defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function &dm2_create_callback(&$controller)
    {
        $this->_link = new net_nehmer_blog_link_dba();
        $this->_link->topic = $this->_topic->id;

        if (!$this->_link->create()) {
            debug_print_r('We operated on this object:', $this->_link);
            throw new midcom_error('Failed to create a new article. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_link;
    }

    /**
     * Displays an article edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_content_topic->require_do('midgard:create');

        if (!$this->_config->get('enable_article_links')) {
            throw new midcom_error_notfound('Article linking disabled');
        }

        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $this->get_controller('create'),
            'save_callback' => array($this, 'save_callback')
        ));

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')));

        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_blog_viewer::index($controller->datamanager, $indexer, $this->_content_topic);
        $article = new midcom_db_article($this->_link->article);
        return $article->name . '/';
    }

    /**
     * Displays article link delete confirmation
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $article = new midcom_db_article($args[0]);

        $qb = net_nehmer_blog_link_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_content_topic->id);
        $qb->add_constraint('article', '=', $article->id);

        if ($qb->count() === 0) {
            throw new midcom_error_notfound('No links were found');
        }

        // Get the link
        $results = $qb->execute_unchecked();
        $this->_link = $results[0];
        $this->_link->require_do('midgard:delete');

        $workflow = $this->get_workflow('delete', array
        (
            'object' => $this->_link,
            'label' => $this->_l10n->get('article link')
        ));
        return $workflow->run();
    }
}
