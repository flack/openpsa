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
     * The article which has been created
     *
     * @var midcom_db_article
     */
    private $_article = null;

    /**
     * The article link which has been created
     *
     * @var net_nehmer_blog_link_dba
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
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $arg = $this->_article->name ? $this->_article->name : $this->_article->guid;

        if ($this->_config->get('view_in_url'))
        {
            $view_url = "view/{$arg}/";
        }
        else
        {
            $view_url = "{$arg}/";
        }

        $this->add_breadcrumb($view_url, $this->_article->title);

        switch ($handler_id)
        {
            case 'delete_link':
                $this->add_breadcrumb("delete/link/{$this->_article->guid}/", $this->_l10n->get('delete link'));
                break;
            case 'create_link':
                $this->add_breadcrumb("create/link/", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')));
                break;
        }
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
        $this->_link = new net_nehmer_blog_link_dba();
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
     */
    public function _handler_create($handler_id, array $args, array &$data)
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
                return new midcom_response_relocate("{$this->_article->name}/");

            case 'cancel':
                if (isset($_GET['article']))
                {
                    if ($this->_config->get('view_in_url'))
                    {
                        $prefix = 'view/';
                    }
                    else
                    {
                        $prefix = '';
                    }
                    try
                    {
                        $article = new midcom_db_article($_GET['article']);
                        return new midcom_response_relocate("{$prefix}{$article->name}/");
                    }
                    catch (midcom_error $e)
                    {
                        $e->log();
                    }
                }

                return new midcom_response_relocate('');
        }

        $this->_prepare_request_data();
        $title = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link'));
        midcom::get('head')->set_pagetitle("{$this->_topic->extra}: {$title}");
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Shows the link creation form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create ($handler_id, array &$data)
    {
        midcom_show_style('admin-create-link');
    }

    /**
     * Displays article link delete confirmation
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_article = new midcom_db_article($args[0]);

        $qb = net_nehmer_blog_link_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_content_topic->id);
        $qb->add_constraint('article', '=', $this->_article->id);

        if ($qb->count() === 0)
        {
            throw new midcom_error_notfound('No links were found');
        }

        // Get the link
        $results = $qb->execute_unchecked();
        $this->_link =& $results[0];
        $this->_link->require_do('midgard:delete');

        $this->_process_delete();

        midcom::get('metadata')->set_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        $this->_view_toolbar->bind_to($this->_article);
        midcom::get('head')->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Internal helper method, which will check if the delete request has been
     * confirmed
     */
    private function _process_delete()
    {
        if (isset($_POST['f_cancel']))
        {
            midcom::get('uimessages')->add($this->_l10n->get('net.nehmer.blog'), $this->_l10n->get('delete cancelled'));

            // Redirect to view page.
            if ($this->_config->get('view_in_url'))
            {
                midcom::get()->relocate("view/{$this->_article->name}/");
            }
            else
            {
                midcom::get()->relocate("{$this->_article->name}/");
            }
            // This will exit
        }

        if (!isset($_POST['f_delete']))
        {
            return;
        }

        // Delete the link
        if ($this->_link->delete())
        {
            midcom::get('uimessages')->add($this->_l10n->get('net.nehmer.blog'), $this->_l10n->get('blog link deleted'));
            midcom::get()->relocate('');
            // This will exit
        }
        else
        {
            throw new midcom_error($this->_l10n->get('failed to delete the blog link, contact the site administrator'));
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        $data['article'] =& $this->_article;
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($this->_article->topic);

        $data['topic_url'] = $node[MIDCOM_NAV_FULLURL];
        $data['topic_name'] = $node[MIDCOM_NAV_NAME];
        $data['delete_url'] = "{$node[MIDCOM_NAV_FULLURL]}delete/{$this->_article->guid}/";

        midcom_show_style('admin-delete-link');
    }

}
?>
