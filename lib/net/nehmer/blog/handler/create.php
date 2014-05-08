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
     * The content topic to use
     *
     * @var midcom_db_topic
     */
    private $_content_topic = null;

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
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['schema'] =& $this->_schema;
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
    }

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
            && !midcom::get('auth')->can_user_do('midcom:urlname'))
        {
            foreach (array_keys($this->_schemadb) as $name)
            {
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
    public function & dm2_create_callback (&$controller)
    {
        $this->_article = new midcom_db_article();
        $this->_article->topic = $this->_content_topic->id;

        if (! $this->_article->create())
        {
            debug_print_r('We operated on this object:', $this->_article);
            throw new midcom_error('Failed to create a new article. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        // Callback possibility
        if ($this->_config->get('callback_function'))
        {
            if ($this->_config->get('callback_snippet'))
            {
                midcom_helper_misc::include_snippet_php($this->_config->get('callback_snippet'));
            }

            $callback = $this->_config->get('callback_function');
            $callback($this->_article, $this->_content_topic);
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
        $this->_content_topic->require_do('midgard:create');

        $this->_schema = $args[0];

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                // Index the article
                $indexer = midcom::get('indexer');
                net_nehmer_blog_viewer::index($data['controller']->datamanager, $indexer, $this->_content_topic);
                // *** FALL THROUGH ***

            case 'cancel':
                return new midcom_response_relocate('');
        }

        $this->_prepare_request_data();

        $title = sprintf($this->_l10n_midcom->get('create %s'), $this->_schemadb[$this->_schema]->description);
        midcom::get('head')->set_pagetitle("{$this->_topic->extra}: {$title}");
        $this->add_breadcrumb("create/{$this->_schema}/", sprintf($this->_l10n_midcom->get('create %s'), $this->_schemadb[$this->_schema]->description));
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create ($handler_id, array &$data)
    {
        midcom_show_style('admin-create');
    }
}
?>