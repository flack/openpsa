<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
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
    private static $_shown_guids = array();

    /**
     * Handler for folder deletion.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        // Symlink support requires that we use actual URL topic object here
        $urltopics = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URLTOPICS);
        if ($urltopic = end($urltopics))
        {
            $this->_topic = $urltopic;
        }

        $this->_topic->require_do('midgard:delete');
        $this->_topic->require_do('midcom.admin.folder:topic_management');

        if (array_key_exists('f_cancel', $_REQUEST))
        {
            return new midcom_response_relocate('');
        }

        if (   array_key_exists('f_submit', $_REQUEST)
            && $this->_process_delete_form())
        {
            $nav = new midcom_helper_nav();
            $upper_node = $nav->get_node($nav->get_current_upper_node());
            return new midcom_response_relocate($upper_node[MIDCOM_NAV_ABSOLUTEURL]);
        }

        $this->_request_data['topic'] = $this->_topic;

        // Add the view to breadcrumb trail
        $this->add_breadcrumb('__ais/folder/delete/', $this->_l10n->get('delete folder'));

        // Hide the button in toolbar
        $this->_node_toolbar->hide_item('__ais/folder/delete/');

        // Set page title
        $data['title'] = sprintf($this->_l10n->get('delete folder %s'), $this->_topic->get_label());
        midcom::get()->head->set_pagetitle($data['title']);

        // Set the help object in the toolbar
        $help_toolbar = midcom::get()->toolbars->get_help_toolbar();
        $help_toolbar->add_help_item('delete_folder', 'midcom.admin.folder', null, null, 1);

        // Add style sheet
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');
    }

    /**
     * Removes the folder from indexer if applicable.
     */
    private function _delete_topic_update_index()
    {
        if (midcom::get()->config->get('indexer_backend') === false)
        {
            // Indexer is not configured.
            return;
        }
        midcom::get()->disable_limits();

        debug_add("Dropping all NAP registered objects from the index.");

        // First we collect everything we have to delete, this might take a while
        // so we keep an eye on the script timeout.
        $guids = array ();
        $nap = new midcom_helper_nav();

        $node_list = array($nap->get_current_node());

        while (count($node_list) > 0)
        {
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
        $indexer = midcom::get()->indexer;
        foreach ($guids as $guid)
        {
            try
            {
                $object = midcom::get()->dbfactory->get_object_by_guid($guid);
                $atts = $object->list_attachments();
                foreach ($atts as $attachment)
                {
                    debug_add("Deleting attachment {$attachment->id} from the index.");
                    $indexer->delete($attachment->guid);
                }
            }
            catch (midcom_error $e)
            {
                $e->log();
            }

            debug_add("Deleting guid {$guid} from the index.");
            $indexer->delete($guid);
        }
    }

    /**
     * Deletes the folder and _midcom_db_article_ objects stored in it.
     */
    private function _process_delete_form()
    {
        if (midcom::get()->config->get('symlinks'))
        {
            midcom::get()->auth->request_sudo('midcom.admin.folder');
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

                throw new midcom_error($msg);
            }
            midcom::get()->auth->drop_sudo();
        }
        $this->_delete_topic_update_index();

        if (!$this->_delete_children($this->_topic))
        {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('could not delete folder contents: %s'), midcom_connection::get_error_string()), 'error');
            return false;
        }

        if (!$this->_topic->delete())
        {
            debug_add("Could not delete Folder {$this->_topic->id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('could not delete folder: %s'), midcom_connection::get_error_string()), 'error');
            return false;
        }

        // Invalidate everything since we operate recursive here.
        midcom::get()->cache->invalidate_all();

        return true;
    }

    private function _delete_children($object)
    {
        $children = midcom_helper_reflector_tree::get_child_objects($object);

        if (empty($children))
        {
            return true;
        }

        foreach ($children as $objects)
        {
            foreach ($objects as $object)
            {
                if (!$this->_delete_children($object))
                {
                    return false;
                }
                if (!$object->delete())
                {
                    debug_add("Could not delete child object {$object->guid}:" . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
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
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        if (!empty($this->_topic->symlink))
        {
            try
            {
                $topic = new midcom_db_topic($this->_topic->symlink);
                $data['symlink'] = '';
                $nap = new midcom_helper_nav();
                if ($node = $nap->get_node($topic))
                {
                    $data['symlink'] = $node[MIDCOM_NAV_FULLURL];
                }
            }
            catch (midcom_error $e)
            {
                debug_add("Could not get target for symlinked topic #{$this->_topic->id}: " .
                    $e->getMessage(), MIDCOM_LOG_ERROR);
            }
        }

        $data['title'] = $this->_topic->get_label();

        midcom_show_style('midcom-admin-show-delete-folder');
    }

    /**
     * List topic contents
     *
     * @param midcom_core_dbaobject $parent
     */
    public static function list_children(midcom_core_dbaobject $parent)
    {
        try
        {
            $children = midcom_helper_reflector_tree::get_child_objects($parent);
        }
        catch (midcom_error $e)
        {
            $children = array();
        }

        if (empty($children))
        {
            return;
        }

        echo "<ul class=\"folder_list\">\n";

        foreach ($children as $class => $objects)
        {
            $reflector = midcom_helper_reflector::get($class);
            $style = "";
            if ($class == 'net_nemein_tag_link')
            {
                $style = "style=\"display: none;\"";
            }
            foreach ($objects as $object)
            {
                if (array_key_exists($object->guid, self::$_shown_guids))
                {
                    //we might see objects twice if they have both up and parent
                    continue;
                }
                self::$_shown_guids[$object->guid] = true;

                $title = $reflector->get_object_label($object);
                $icon = $reflector->get_object_icon($object);
                echo "    <li class=\"leaf $class\"$style>\n";
                echo "        " . $icon . " {$title}\n";
                self::list_children($object);
                echo "    </li>\n";
            }
        }

        echo "</ul>\n";
    }
}
