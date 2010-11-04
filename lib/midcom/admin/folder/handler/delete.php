<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: delete.php 25746 2010-04-23 07:25:06Z jval $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Handle the folder deleting requests
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_delete extends midcom_baseclasses_components_handler
{
    /**
     * Constructor method
     *
     * @access public
     */
    function __construct ()
    {
        parent::__construct();
        $_MIDCOM->componentloader->load('midcom.helper.reflector');
    }

    /**
     * Handler for folder deletion.
     *
     * @access private
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    function _handler_delete($handler_id, $args, &$data)
    {
        // Symlink support requires that we use actual URL topic object here
        if ($urltopic = end($_MIDCOM->get_context_data(MIDCOM_CONTEXT_URLTOPICS)))
        {
            $this->_topic = $urltopic;
        }

        $this->_topic->require_do('midgard:delete');
        $this->_topic->require_do('midcom.admin.folder:topic_management');

        if (array_key_exists('f_cancel', $_REQUEST))
        {
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('f_submit', $_REQUEST))
        {
            $nav = new midcom_helper_nav();
            $upper_node = $nav->get_node($nav->get_current_upper_node());

            if ($this->_process_delete_form())
            {
                $_MIDCOM->relocate($upper_node[MIDCOM_NAV_FULLURL]);
                // This will exit.
            }
        }

        $this->_request_data['topic'] = $this->_topic;

        // Add the view to breadcrumb trail
        $tmp = array();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__ais/folder/delete/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('delete folder', 'midcom.admin.folder'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        // Hide the button in toolbar
        $this->_node_toolbar->hide_item('__ais/folder/delete/');

        // Set page title
        $data['title'] = sprintf($_MIDCOM->i18n->get_string('delete folder %s', 'midcom.admin.folder'), $data['topic']->extra);
        $_MIDCOM->set_pagetitle($data['title']);

        // Set the help object in the toolbar
        $help_toolbar = $_MIDCOM->toolbars->get_help_toolbar();
        $help_toolbar->add_help_item('delete_folder', 'midcom.admin.folder', null, null, 1);


        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midcom.admin.folder');

        // Add style sheet
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css',
            )
        );

        return true;
    }

    /**
     * Removes the folder from indexer if applicable.
     *
     * @access private
     */
    function _delete_topic_update_index()
    {
        if ($GLOBALS['midcom_config']['indexer_backend'] === false)
        {
            // Indexer is not configured.
            return;
        }

        debug_push_class(__CLASS__, __FUNCTION__);

        debug_add("Dropping all NAP registered objects from the index.");

        // First we collect everything we have to delete, this might take a while
        // so we keep an eye on the script timeout.
        $guids = array ();
        $nap = new midcom_helper_nav();

        $node_list = array($nap->get_current_node());

        while (count($node_list) > 0)
        {
            set_time_limit(30);

            // Add the node being processed.
            $nodeid = array_shift($node_list);
            debug_add("Processing node {$nodeid}");

            $node = $nap->get_node($nodeid);
            $guids[] = $node[MIDCOM_NAV_GUID];

            debug_add("Processing leaves of node {$nodeid}");
            $leaves = $nap->list_leaves($nodeid, true);
            debug_add('Got ' . count($leaves) . ' leaves.');
            foreach ($leaves as $leafid)
            {
                $leaf = $nap->get_leaf($leafid);
                $guids[] = $leaf[MIDCOM_NAV_GUID];
            }

            debug_add('Loading subnodes');
            $node_list = array_merge($node_list, $nap->list_nodes($nodeid, true));
            debug_print_r('Remaining node queue', $node_list);
        }

        debug_add('We have to delete ' . count($guids) . ' objects from the index.');

        // Now we go over the entire index and delete the corresponding objects.
        // We load all attachments of the corresponding objects as well, to have
        // them deleted too.
        //
        // Again we keep an eye on the script timeout.
        $indexer = $_MIDCOM->get_service('indexer');
        foreach ($guids as $guid)
        {
            set_time_limit(60);

            $object = $_MIDCOM->dbfactory->get_object_by_guid($guid);
            if ($object)
            {
                $atts = $object->list_attachments();
                if ($atts)
                {
                    foreach ($atts as $attachment)
                    {
                        debug_add("Deleting attachment {$atts->id} from the index.");
                        $indexer->delete($atts->guid);
                    }
                }
            }

            debug_add("Deleting guid {$guid} from the index.");
            $indexer->delete($guid);
        }

        debug_pop();
    }

    /**
     * Deletes the folder and _midcom_db_article_ objects stored in it.
     *
     * @access private
     */
    function _process_delete_form()
    {
        $_MIDCOM->auth->request_sudo('midcom.admin.folder');
        $qb_topic = midcom_db_topic::new_query_builder();
        $qb_topic->add_constraint('symlink', '=', $this->_topic->id);
        $symlinks = $qb_topic->execute();
        if (!empty($symlinks))
        {
            $msg = 'Refusing to delete Folder because it has symlinks:';
            $nap = new midcom_helper_nav();
            foreach ($symlinks as $symlink)
            {
                $node = $nap->get_node($symlink->id);
                $msg .= ' ' . $node[MIDCOM_NAV_FULLURL];
            }

            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, $msg);
            // This will exit
        }
        $_MIDCOM->auth->drop_sudo();

        $this->_delete_topic_update_index();

        if (!midcom_admin_folder_handler_delete::_delete_children($this->_topic))
        {
            $this->_contentadm->msg = 'Error: Could not delete Folder contents: ' . midcom_application::get_error_string();
            return false;
        }

        if (!$this->_topic->delete())
        {
            debug_add("Could not delete Folder {$this->_topic->id}: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            $this->_contentadm->msg = 'Error: Could not delete Folder contents: ' . midcom_application::get_error_string();
            return false;
        }

        // Invalidate everything since we operate recursive here.
        $_MIDCOM->cache->invalidate_all();

        debug_pop();
        return true;
    }

    function _delete_children($object)
    {
        $children = midcom_admin_folder_handler_delete::_get_child_objects($object);
        if ($children === false)
        {
            return false;
        }

        foreach ($children as $objects)
        {
            foreach ($objects as $object)
            {
                if (!midcom_admin_folder_handler_delete::_delete_children($object))
                {
                    return false;
                }
                if (!$object->delete())
                {
                    debug_add("Could not delete child object {$object->guid}:" . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Shows the _Delete folder_ form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     * @access private
     */
    function _show_delete($handler_id, &$data)
    {
        if (!empty($this->_topic->symlink))
        {
            $topic = new midcom_db_topic($this->_topic->symlink);
            if ($topic && $topic->guid)
            {
                $data['symlink'] = '';
                $nap = new midcom_helper_nav();
                if ($node = $nap->get_node($topic))
                {
                    $data['symlink'] = $node[MIDCOM_NAV_FULLURL];
                }
            }
            else
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Could not get target for symlinked topic #{$this->_topic->id}: " .
                    midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
            }
        }

        $data['title'] = $this->_topic->extra;
        if (!$data['title'])
        {
            $data['title'] = $this->_topic->name;
        }

        midcom_show_style('midcom-admin-show-delete-folder');
    }

    /**
     * List topic contents
     *
     * @access public
     * @static
     * @param int $id Topic ID
     */
    function list_children($id)
    {
        $children = array();
        if ($topic = new midcom_db_topic($id))
        {
            $children = midcom_admin_folder_handler_delete::_get_child_objects($topic);
            if ($children === false)
            {
                $children = array();
            }
        }

        $qb_topic = midcom_db_topic::new_query_builder();
        $qb_topic->add_constraint('up', '=', $id);

        $qb_article = midcom_db_article::new_query_builder();
        $qb_article->add_constraint('topic', '=', $id);

        if (   $qb_topic->count() === 0
            && $qb_article->count() === 0
            && !$children)
        {
            return false;
        }

        echo "<ul class=\"folder_list\">\n";

        foreach ($qb_topic->execute_unchecked() as $topic)
        {
            if (!midcom_baseclasses_core_dbobject::delete_pre_multilang_checks($topic))
            {
                continue;
            }

            $topic_title = $topic->extra;
            if (!$topic_title)
            {
                $topic_title = $topic->name;
            }

            echo "    <li class=\"node\">\n";
            echo "        <img src=\"".MIDCOM_STATIC_URL."/stock-icons/16x16/stock_folder.png\" alt=\"\" /> {$topic_title}\n";

            midcom_admin_folder_handler_delete::list_children($topic->id);

            echo "    </li>\n";
        }

        foreach ($qb_article->execute_unchecked() as $article)
        {
            if (!midcom_baseclasses_core_dbobject::delete_pre_multilang_checks($article))
            {
                continue;
            }

            echo "    <li class=\"leaf article\">\n";
            echo "        <img src=\"".MIDCOM_STATIC_URL."/stock-icons/16x16/new-text.png\" alt=\"\" /> {$article->title}\n";

            // Check for the reply articles
            $qb = midcom_db_article::new_query_builder();
            $qb->add_constraint('up', '=', $article->id);

            if ($qb->count() > 0)
            {
                $reply_ul = false;
                foreach ($qb->execute_unchecked() as $reply)
                {
                    if (!midcom_baseclasses_core_dbobject::delete_pre_multilang_checks($reply))
                    {
                        continue;
                    }

                    if (!$reply_ul)
                    {
                        echo "        <ul>\n";
                        $reply_ul = true;
                    }

                    echo "            <li class=\"leaf_child reply_article\">{$reply->title}\n";
                    midcom_admin_folder_handler_delete::_list_leaf_children($reply);
                    echo "            </li>\n";
                }
                if ($reply_ul)
                {
                    echo "        </ul>\n";
                }
            }

            midcom_admin_folder_handler_delete::_list_leaf_children($article, array('midgard_article'));

            echo "    </li>\n";
        }

        foreach ($children as $class => $objects)
        {
            if ($class == 'midgard_topic' || $class == 'midgard_article')
            {
                continue;
            }
            $style = "";
            if ($class == 'net_nemein_tag_link')
            {
                $style = "style=\"display: none;\"";
            }
            foreach ($objects as $object)
            {
                $title = midcom_admin_folder_handler_delete::_get_object_title($class, $object);
                echo "    <li class=\"leaf $class\"$style>\n";
                echo "        <img src=\"".MIDCOM_STATIC_URL."/stock-icons/16x16/new-text.png\" alt=\"\" /> {$title}\n";
                midcom_admin_folder_handler_delete::_list_leaf_children($object);
                echo "    </li>\n";
            }
        }

        echo "</ul>\n";
    }

    function _list_leaf_children($object, $skip = array())
    {
        if ($children = midcom_admin_folder_handler_delete::_get_child_objects($object))
        {
            if ($skip)
            {
                foreach ($children as $class => $objects)
                {
                    if (in_array($class, $skip))
                    {
                        unset($children[$class]);
                    }
                }
            }
        }
        if ($children)
        {
            echo "        <ul>\n";
            foreach ($children as $class => $objects)
            {
                foreach ($objects as $object)
                {
                    $title = midcom_admin_folder_handler_delete::_get_object_title($class, $object);
                    echo "            <li class=\"leaf_child $class\" style=\"display: none;\">{$title}\n";
                    midcom_admin_folder_handler_delete::_list_leaf_children($object);
                    echo "            </li>\n";
                }
            }
            echo "        </ul>\n";
        }
    }

    function _get_object_title($class, $object)
    {
        $title = trim(midcom_helper_reflector::get_object_title($object));
        if (empty($title))
        {
            $title = trim(midcom_helper_reflector::get_object_name($object));
        }
        if (empty($title))
        {
            $title = $class . " " . $object->guid;
        }
        return $title;
    }

    function _get_child_objects($object)
    {
        $children = midcom_helper_reflector_tree::get_child_objects($object);
        if ($children === false)
        {
            debug_add('Failed to query the children of object {$object->guid}: ' . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
        }
        if (!$children)
        {
            return $children;
        }

        foreach ($children as $class => $objects)
        {
            foreach ($objects as $key => $object)
            {
                if (!midcom_baseclasses_core_dbobject::delete_pre_multilang_checks($object))
                {
                    unset($children[$class][$key]);
                }
            }
            if (empty($children[$class]))
            {
                unset($children[$class]);
            }
        }

        return $children;
    }
}
?>
