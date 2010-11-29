<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: create.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static create page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_create extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    var $_content_topic = null;

    /**
     * The article which has been created
     *
     * @var midcom_db_article
     * @access private
     */
    var $_article = null;

    /**
     * The Controller of the article used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    var $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    var $_schemadb = null;

    /**
     * The schema to use for the new article.
     *
     * @var string
     * @access private
     */
    var $_schema = null;

    /**
     * This flag indicates whether we have been called from the index-article check
     * or not.
     *
     * @var boolean
     * @access private
     */
    var $_indexmode = false;

    /**
     * The defaults to use for the new article.
     *
     * @var Array
     * @access private
     */
    var $_defaults = Array();

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['indexmode'] =& $this->_indexmode;
        $this->_request_data['schema'] =& $this->_schema;
        $this->_request_data['schemadb'] =& $this->_schemadb;
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
     * Special treatment is done for the name field, which is set hidden
     * if the simple_name_handling config option (auto-generated urlname
     * based on the title) is set.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb'];
        if ($this->_config->get('simple_name_handling'))
        {
            foreach (array_keys($this->_schemadb) as $name)
            {
                $this->_schemadb[$name]->fields['name']['hidden'] = true;
            }
        }
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->defaults = $this->_defaults;
        $this->_controller->callback_object =& $this;

        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function &dm2_create_callback (&$controller)
    {
        $this->_article = new midcom_db_article();
        $this->_article->topic = $this->_content_topic->id;

        if (   array_key_exists('name', $this->_defaults)
            && $this->_defaults['name'] == 'index')
        {
            // Store this to article directly in case name field is not editable in schema
            $this->_article->name = 'index';
        }

        if (! $this->_article->create())
        {
            debug_print_r('We operated on this object:', $this->_article);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to create a new article, cannot continue. Last Midgard error was: '. midcom_connection::get_error_string());
            // This will exit.
        }

        // Callback possibility
        if ($this->_config->get('callback_function'))
        {
            if ($this->_config->get('callback_snippet'))
            {
                // mgd_include_snippet($this->_config->get('callback_snippet'));
                $eval = midcom_get_snippet_content($this->_config->get('callback_snippet'));

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
            $this->_defaults['name'] = 'index';
            $this->_indexmode = true;
        }

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                /**
                 * http://trac.midgard-project.org/ticket/809 should have taken care of this.
                 * BUT: to be extra careful, let's do this sanity-check anyway.
                 */
                if (strlen($this->_article->name) == 0)
                {
                    // Generate something to avoid empty "/" links in case of failures
                    $this->_article->name = time();
                    $this->_article->update();
                }

                // Index the article
                $indexer = $_MIDCOM->get_service('indexer');
                net_nehmer_static_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);
                if ($this->_article->name === 'index')
                {
                    $_MIDCOM->relocate('');
                    // This will exit.
                }
                $_MIDCOM->relocate("{$this->_article->name}/");
                // This will exit.

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }

        $this->_prepare_request_data();
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