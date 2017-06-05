<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static create page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_create extends midcom_baseclasses_components_handler
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
     * The defaults to use for the new article.
     *
     * @var Array
     */
    private $_defaults = [];

    /**
     * Loads and prepares the schema database.
     *
     * Special treatment is done for the name field, which is set hidden
     * if the simple_name_handling config option (auto-generated urlname
     * based on the title) is set.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $this->_schemadb = $this->_request_data['schemadb'];
        if ($this->_config->get('simple_name_handling')) {
            foreach (array_keys($this->_schemadb) as $name) {
                $this->_schemadb[$name]->fields['name']['hidden'] = true;
            }
        }
        return $this->_schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema;
    }

    public function get_schema_defaults()
    {
        if ($this->_request_data['handler_id'] == 'createindex') {
            $this->_defaults['name'] = 'index';
        }
        return $this->_defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function &dm2_create_callback(&$controller)
    {
        $this->_article = new midcom_db_article();
        $this->_article->topic = $this->_topic->id;

        if (   array_key_exists('name', $this->_defaults)
            && $this->_defaults['name'] == 'index') {
            // Store this to article directly in case name field is not editable in schema
            $this->_article->name = 'index';
        }

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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');
        $this->_schema = $args[0];

        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $this->get_controller('create'),
            'save_callback' => [$this, 'save_callback']
        ]);
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_schemadb[$this->_schema]->description));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_static_viewer::index($controller->datamanager, $indexer, $this->_topic);
        if ($this->_article->name == 'index') {
            return '';
        }
        return $this->_article->name . '/';
    }
}
