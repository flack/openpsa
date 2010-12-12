<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Metadata editor.
 *
 * This handler uses midcom.helper.datamanager2 to edit object move properties
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_move extends midcom_baseclasses_components_handler
{
    /**
     * Object requested for move editing
     *
     * @var mixed Object for move editing
     */
    private $_object = null;

    /**
     * Handler for folder move. Checks for updating permissions, initializes
     * the move and the content topic itself. Handles also the sent form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    public function _handler_move($handler_id, $args, &$data)
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        if (! $this->_object)
        {
            debug_add("Object with GUID '{$args[0]}' was not found!", MIDCOM_LOG_ERROR);

            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$args[0]}' was not found.");
            // This will exit.
        }

        if (   !is_a($this->_object, 'midcom_db_topic')
            && !is_a($this->_object, 'midcom_db_article'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Moving only topics and articles is supported.");
        }

        $this->_object->require_do('midgard:update');

        if (isset($_POST['move_to']))
        {
            $this->_move_object((int) $_POST['move_to']);
            $_MIDCOM->relocate($_MIDCOM->permalinks->create_permalink($this->_object->guid));
            // This will exit
        }

        $object_label = midcom_helper_reflector::get($this->_object)->get_object_label($this->_object);

        if (is_a($this->_object, 'midcom_db_topic'))
        {
            // This is a topic
            $this->_object->require_do('midcom.admin.folder:topic_management');
            $this->_node_toolbar->hide_item("__ais/folder/move/{$this->_object->guid}/");
            $data['current_folder'] = new midcom_db_topic($this->_object->up);
        }
        else
        {
            // This is a regular object, bind to view
            $_MIDCOM->bind_view_to_object($this->_object);

            $this->add_breadcrumb($_MIDCOM->permalinks->create_permalink($this->_object->guid), $object_label);
            $this->_view_toolbar->hide_item("__ais/folder/move/{$this->_object->guid}/");

            $data['current_folder'] = new midcom_db_topic($this->_object->topic);
        }

        $this->add_breadcrumb("__ais/folder/move/{$this->_object->guid}/", $this->_l10n->get('move'));

        $data['title'] = sprintf($_MIDCOM->i18n->get_string('move %s', 'midcom.admin.folder'), $object_label);
        $_MIDCOM->set_pagetitle($data['title']);

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midcom.admin.folder');

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');

        return true;
    }

    private function _move_object($target)
    {
        $move_to_topic = new midcom_db_topic();
        $move_to_topic->get_by_id($target);

        if (!$move_to_topic->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to move the topic. Could not get the target topic');
            // This will exit
        }

        $move_to_topic->require_do('midgard:create');

        if (is_a($this->_object, 'midcom_db_topic'))
        {
            $name = $this->_object->name;
            $this->_object->name = ''; // Prevents problematic location to break the site, we set this back below...
            $up = $this->_object->up;
            $this->_object->up = $move_to_topic->id;
            if (!$this->_object->update())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to move the topic, reason ' . midcom_connection::get_error_string());
                // This will exit
            }
            if (!midcom_admin_folder_folder_management::is_child_listing_finite($this->_object))
            {
                $this->_object->up = $up;
                $this->_object->name = $name;
                $this->_object->update();
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Refusing to move this folder because the move would have created an " .
                    "infinite loop situation caused by the symlinks on this site. The " .
                    "whole site would have been completely and irrevocably broken if this " .
                    "move would have been allowed to take place. Infinite loops can not " .
                    "be allowed. Sorry, but this was for your own good."
                );
                // This will exit
            }
            // It was ok, so set name back now
            $this->_object->name = $name;
            $this->_object->update();
        }
        else
        {
            $this->_object->topic = $move_to_topic->id;
            if (!$this->_object->update())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to move the article, reason ' . midcom_connection::get_error_string());
                // This will exit
            }
        }
    }

    /**
     * Output the style element for move editing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_move($handler_id, &$data)
    {
        // Bind object details to the request data
        $data['object'] =& $this->_object;

        midcom_show_style('midcom-admin-show-folder-move');
    }
}
?>