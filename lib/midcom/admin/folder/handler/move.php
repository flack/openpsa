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
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_move($handler_id, array $args, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);

        if (   !is_a($this->_object, 'midcom_db_topic')
            && !is_a($this->_object, 'midcom_db_article')) {
            throw new midcom_error_notfound("Moving only topics and articles is supported.");
        }

        $this->_object->require_do('midgard:update');

        if (isset($_POST['move_to'])) {
            $this->_move_object((int) $_POST['move_to']);
            return new midcom_response_relocate(midcom::get()->permalinks->create_permalink($this->_object->guid));
        }

        $object_label = midcom_helper_reflector::get($this->_object)->get_object_label($this->_object);

        if (is_a($this->_object, 'midcom_db_topic')) {
            // This is a topic
            $this->_object->require_do('midcom.admin.folder:topic_management');
            $this->_node_toolbar->hide_item("__ais/folder/move/{$this->_object->guid}/");
            $data['current_folder'] = new midcom_db_topic($this->_object->up);
        } else {
            // This is a regular object, bind to view
            $this->bind_view_to_object($this->_object);

            $this->add_breadcrumb(midcom::get()->permalinks->create_permalink($this->_object->guid), $object_label);
            $this->_view_toolbar->hide_item("__ais/folder/move/{$this->_object->guid}/");

            $data['current_folder'] = new midcom_db_topic($this->_object->topic);
        }
        $data['handler'] = $this;

        $this->add_breadcrumb("__ais/folder/move/{$this->_object->guid}/", $this->_l10n->get('move'));

        $data['title'] = sprintf($this->_l10n->get('move %s'), $object_label);
        midcom::get()->head->set_pagetitle($data['title']);

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');
    }

    public function show_tree(midcom_db_topic $folder = null, $tree_disabled = false)
    {
        if (null === $folder) {
            $folder = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        }

        if (   is_a($this->_object, 'midcom_db_topic')
            && $folder->up == $this->_object->id) {
            $tree_disabled = true;
        }

        $class = '';
        $selected = '';
        $disabled = '';
        if ($folder->guid == $this->_request_data['current_folder']->guid) {
            $class = 'current';
            $selected = ' checked="checked"';
        }

        if (   !is_a($this->_object, 'midcom_db_topic')
            && $folder->component !== $this->_request_data['current_folder']->component) {
            // Non-topic objects may only be moved under folders of same component
            $class = 'wrong_component';
            $disabled = ' disabled="disabled"';
        }

        if ($tree_disabled) {
            $class = 'child';
            $disabled = ' disabled="disabled"';
        }

        if ($folder->guid == $this->_object->guid) {
            $class = 'self';
            $disabled = ' disabled="disabled"';
        }
        echo "<ul>\n";
        echo "<li class=\"{$class}\"><label><input{$selected}{$disabled} type=\"radio\" name=\"move_to\" value=\"{$folder->id}\" /> " . $folder->get_label() . "</label>\n";

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $folder->id);
        $qb->add_constraint('component', '<>', '');
        $children = $qb->execute();

        foreach ($children as $child) {
            $this->show_tree($child, $tree_disabled);
        }
        echo "</li>\n";
        echo "</ul>\n";
    }

    private function _move_object($target)
    {
        $move_to_topic = new midcom_db_topic();

        if (!$move_to_topic->get_by_id($target)) {
            throw new midcom_error( 'Failed to move the topic. Could not get the target topic');
        }

        $move_to_topic->require_do('midgard:create');

        if (is_a($this->_object, 'midcom_db_topic')) {
            $name = $this->_object->name;
            $this->_object->name = ''; // Prevents problematic location to break the site, we set this back below...
            $this->_object->up = $move_to_topic->id;
            if (!$this->_object->update()) {
                throw new midcom_error('Failed to move the topic, reason ' . midcom_connection::get_error_string());
            }
            // It was ok, so set name back now
            $this->_object->name = $name;
            $this->_object->update();
        } else {
            $this->_object->topic = $move_to_topic->id;
            if (!$this->_object->update()) {
                throw new midcom_error('Failed to move the article, reason ' . midcom_connection::get_error_string());
            }
        }
    }

    /**
     * Output the style element for move editing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_move($handler_id, array &$data)
    {
        // Bind object details to the request data
        $data['object'] = $this->_object;

        midcom_show_style('midcom-admin-show-folder-move');
    }
}
