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
     * @access private
     */
    private $_content_topic = null;

    /**
     * The article which has been created
     *
     * @var midcom_db_article
     * @access private
     */
    private $_article = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new article.
     *
     * @var string
     * @access private
     */
    private $_schema = null;

    private $_indexmode = false;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['indexmode'] =& $this->_indexmode;
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
            //TODO: Is this a typo or does auth->create really exist?
            && ! $_MIDCOM->auth->create)
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

    public function get_schema_defaults()
    {
        $defaults = array();
        if ($this->_request_data['handler_id'] == 'createindex')
        {
            $defaults['name'] = 'index';
        }
        return $defaults;
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
                // mgd_include_snippet($this->_config->get('callback_snippet'));
                $eval = midcom_helper_misc::get_snippet_content($this->_config->get('callback_snippet'));

                if ($eval)
                {
                    eval($eval);
                }
            }

            $callback = $this->_config->get('callback_function');
            $callback($this->_article, $this->_content_topic);
        }

        return $this->_article;
    }

    /**
     * Displays an article edit view.
     *
     * Note, that the article for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation article
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_create($handler_id, $args, &$data)
    {
        $this->_content_topic->require_do('midgard:create');

        $this->_schema = $args[0];
        if ($handler_id == 'createindex')
        {
            $this->_indexmode = true;
        }

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                // #809 should have taken care of this, but see same place in n.n.static
                if (strlen($this->_article->name) == 0)
                {
                    // Generate something to avoid empty "/" links in case of failures
                    $this->_article->name = time();
                    $this->_article->update();
                }
                // Index the article
                $indexer = $_MIDCOM->get_service('indexer');
                net_nehmer_blog_viewer::index($data['controller']->datamanager, $indexer, $this->_content_topic);
                // *** FALL THROUGH ***

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }

        $this->_prepare_request_data();
        if ( $this->_article != null )
        {
            $_MIDCOM->set_26_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        }

        $title = sprintf($this->_l10n_midcom->get('create %s'), $this->_schemadb[$this->_schema]->description);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: {$title}");
        $this->add_breadcrumb("create/{$this->_schema}/", sprintf($this->_l10n_midcom->get('create %s'), $this->_schemadb[$this->_schema]->description));

        return true;
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_create ($handler_id, &$data)
    {
        midcom_show_style('admin-create');
    }
}
?>