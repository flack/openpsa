<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.blog create page handler
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The article which has been created
     *
     * @var midcom_db_article
     */
    private $_article = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new article.
     *
     * @var string
     */
    private $_schema = null;

    /**
     * Loads and prepares the schema database.
     *
     * Special treatment is done for the name field, which is set readonly for non-creates
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb'];
        if (   $this->_config->get('simple_name_handling')
            && !midcom::get()->auth->can_user_do('midcom:urlname')) {
            foreach (array_keys($this->_schemadb) as $name) {
                $this->_schemadb[$name]->fields['name']['readonly'] = true;
            }
        }
        return $this->_schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_article = new midcom_db_article();
        $this->_article->topic = $this->_topic->id;

        if (!$this->_article->create()) {
            debug_print_r('We operated on this object:', $this->_article);
            throw new midcom_error('Failed to create a new article. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        // Callback possibility
        if ($this->_config->get('callback_function')) {
            if ($this->_config->get('callback_snippet')) {
                midcom_helper_misc::include_snippet_php($this->_config->get('callback_snippet'));
            }

            $callback = $this->_config->get('callback_function');
            $callback($this->_article, $this->_topic);
        }

        return $this->_article;
    }

    /**
     * Displays an article create view.
     *
     * If create privileges apply, we relocate to the created article
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');
        $this->_schema = $args[0];

        $data['controller'] = $this->get_controller('create');
        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_blog_viewer::index($controller->datamanager, $indexer, $this->_topic);
    }
}
