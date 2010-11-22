<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: admin.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.blog admin page handler
 *
 * @package net.nehmer.blog
 */

class net_nehmer_blog_handler_admin extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    var $_content_topic = null;

    /**
     * The article to operate on
     *
     * @var midcom_db_article
     * @access private
     */
    var $_article = null;

    /**
     * The Datamanager of the article to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

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
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['article'] =& $this->_article;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        // Populate the toolbar
        if ($this->_article->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "edit/{$this->_article->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

        if ($this->_article->can_do('midgard:delete'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "delete/{$this->_article->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }
    }

    /**
     * Simple default constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
    }

    /**
     * Loads and prepares the schema database.
     *
     * Special treatment is done for the name field, which is set readonly for non-admins
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb'];
        if (   $this->_config->get('simple_name_handling')
            && ! $_MIDCOM->auth->admin)
        {
            foreach (array_keys($this->_schemadb) as $name)
            {
                $this->_schemadb[$name]->fields['name']['readonly'] = true;
            }
        }
    }

    /**
     * Internal helper, loads the datamanager for the current article. Any error triggers a 500.
     *
     * @access private
     */
    function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (   ! $this->_datamanager
            || ! $this->_datamanager->autoset_storage($this->_article))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for article {$this->_article->id}.");
            // This will exit.
        }
    }

    /**
     * Internal helper, loads the controller for the current article. Any error triggers a 500.
     *
     * @access private
     */
    function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_article);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
            // This will exit.
        }
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    function _update_breadcrumb_line($handler_id)
    {
        $tmp = Array();

        $arg = $this->_article->name ? $this->_article->name : $this->_article->guid;

        if ($this->_config->get('view_in_url'))
        {
            $view_url = "view/{$arg}/";
        }
        else
        {
            $view_url = "{$arg}/";
        }

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => $view_url,
            MIDCOM_NAV_NAME => $this->_article->title,
        );

        switch ($handler_id)
        {
            case 'delete_link':
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "delete/link/{$this->_article->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('delete link'),
                );
                break;

            default:
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "{$handler_id}/{$this->_article->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n_midcom->get($handler_id),
                );
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
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
    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_article = new midcom_db_article($args[0]);

        if (!$this->_article)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The article with GUID {$args[0]} was not found.");
            // This will exit.
        }

        // Relocate for the correct content topic, let the true content topic take care of the ACL
        if ($this->_article->topic !== $this->_content_topic->id)
        {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->_article->topic);

            if (   $node
                && isset($node[MIDCOM_NAV_FULLURL]))
            {
                $_MIDCOM->relocate($node[MIDCOM_NAV_FULLURL] . "edit/{$args[0]}/");
                // This will exit
            }
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The article with GUID {$args[0]} was not found.");
            // This will exit.
        }

        $this->_article->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the article
                $indexer = $_MIDCOM->get_service('indexer');
                net_nehmer_blog_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                if ($this->_config->get('view_in_url'))
                {
                    $_MIDCOM->relocate("view/{$this->_article->name}/");
                }
                else
                {
                    $_MIDCOM->relocate("{$this->_article->name}/");
                }

                // This will exit.
        }

        $this->_prepare_request_data();
        $this->_view_toolbar->bind_to($this->_article);
        $_MIDCOM->set_26_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_edit ($handler_id, &$data)
    {
        midcom_show_style('admin-edit');
    }

    /**
     * Displays article link delete confirmation
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_deletelink($handler_id, $args, &$data)
    {
        $this->_article = new midcom_db_article($args[0]);

        if (   !$this->_article
            || !isset($this->_article->guid)
            || !$this->_article->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The article {$args[0]} was not found.");
            // This will exit.
        }

        $qb = net_nehmer_blog_link_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_content_topic->id);
        $qb->add_constraint('article', '=', $this->_article->id);

        // No links were found
        if ($qb->count() === 0)
        {
            return false;
        }

        // Get the link
        $results = $qb->execute_unchecked();
        $this->_link =& $results[0];
        $this->_link->require_do('midgard:delete');

        $this->_process_link_delete();

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        $this->_view_toolbar->bind_to($this->_article);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }

    /**
     * Internal helper method, which will check if the delete request has been
     * confirmed
     *
     * @access private
     */
    function _process_link_delete()
    {
        if (isset($_POST['f_cancel']))
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.blog'), $this->_l10n->get('delete cancelled'));

            // Redirect to view page.
            if ($this->_config->get('view_in_url'))
            {
                $_MIDCOM->relocate("view/{$this->_article->name}/");
            }
            else
            {
                $_MIDCOM->relocate("{$this->_article->name}/");
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
            $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.blog'), $this->_l10n->get('blog link deleted'));
            $_MIDCOM->relocate('');
            // This will exit
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $this->_l10n->get('failed to delete the blog link, contact the site administrator'));
            // This will exit
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_deletelink($handler_id, &$data)
    {
        $data['article'] =& $this->_article;
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($this->_article->topic);

        $data['topic_url'] = $node[MIDCOM_NAV_FULLURL];
        $data['topic_name'] = $node[MIDCOM_NAV_NAME];
        $data['delete_url'] = "{$node[MIDCOM_NAV_FULLURL]}delete/{$this->_article->guid}/";

        midcom_show_style('admin-delete-link');
    }

    /**
     * Displays an article delete confirmation view.
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
    function _handler_delete($handler_id, $args, &$data)
    {
        $this->_article = new midcom_db_article($args[0]);
        if (   !$this->_article
            || !isset($this->_article->guid)
            || !$this->_article->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The article {$args[0]} was not found.");
            // This will exit.
        }

        // Relocate to delete the link instead of the article itself
        if ($this->_article->topic !== $this->_content_topic->id)
        {
            $_MIDCOM->relocate("delete/link/{$args[0]}/");
            // This will exit
        }
        $this->_article->require_do('midgard:delete');

        $this->_load_datamanager();

        if (array_key_exists('net_nehmer_blog_deleteok', $_REQUEST))
        {
            $title = $this->_article->title;

            // Deletion confirmed.
            if (! $this->_article->delete())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to delete article {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
                // This will exit.
            }

            // Delete all the links pointing to the article
            $qb = net_nehmer_blog_link_dba::new_query_builder();
            $qb->add_constraint('article', '=', $this->_article->id);
            $links = $qb->execute_unchecked();

            $_MIDCOM->auth->request_sudo('net.nehmer.blog');
            foreach ($links as $link)
            {
                $link->delete();
            }
            $_MIDCOM->auth->drop_sudo();

            // Update the index
            $indexer = $_MIDCOM->get_service('indexer');
            $indexer->delete($this->_article->guid);

            // Show user interface message
            $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.blog'), sprintf($this->_l10n->get('article %s deleted'), $title));

            // Delete ok, relocating to welcome.
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('net_nehmer_blog_deletecancel', $_REQUEST))
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.blog'), $this->_l10n->get('delete cancelled'));

            // Redirect to view page.
            if ($this->_config->get('view_in_url'))
            {
                $_MIDCOM->relocate("view/{$this->_article->name}/");
            }
            else
            {
                $_MIDCOM->relocate("{$this->_article->name}/");
            }
            // This will exit
        }

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        $this->_view_toolbar->bind_to($this->_article);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_delete ($handler_id, &$data)
    {
        midcom_show_style('admin-delete');
    }
}
?>