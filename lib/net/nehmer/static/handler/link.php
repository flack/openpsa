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
class net_nehmer_static_handler_link extends midcom_baseclasses_components_handler
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
     * The article link which has been created
     *
     * @var net_nehmer_static_link_dba
     * @access private
     */
    private $_link = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['indexmode'] =& $this->_indexmode;
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
    }

    public function load_schemadb()
    {
    	return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_link'));
    }

    public function get_schema_defaults()
    {
        $defaults = array();
        if (isset($_GET['article']))
        {
            $defaults['article'] = $_GET['article'];
        }
        else
        {
            $defaults['topic'] = $this->_topic->id;
        }
        return $defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function &dm2_create_callback (&$controller)
    {
        $this->_link = new net_nehmer_static_link_dba();
        $this->_link->topic = $this->_topic->id;

        if (!$this->_link->create())
        {
            debug_print_r('We operated on this object:', $this->_link);
            throw new midcom_error('Failed to create a new article. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_link;
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

        if (!$this->_config->get('enable_article_links'))
        {
            throw new midcom_error_notfound('Article linking disabled');
        }

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                $this->_article = new midcom_db_article($this->_link->article);
                $_MIDCOM->relocate("{$this->_article->name}/");
                // This will exit

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit
        }

        $this->_prepare_request_data();
        $title = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link'));
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: {$title}");
        $this->add_breadcrumb("create/link/", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')));
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
        midcom_show_style('admin-create-link');
    }
}
?>
