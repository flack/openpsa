<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Move handler.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_move extends midcom_baseclasses_components_handler
{
    /**
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * @var midcom_db_topic
     */
    private $current_folder;

    /**
     * Handler for folder move. Checks for updating permissions, initializes
     * the move and the content topic itself. Handles also the sent form.
     */
    public function _handler_move(Request $request, string $guid, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);

        if (   !is_a($this->_object, midcom_db_topic::class)
            && !is_a($this->_object, midcom_db_article::class)) {
            throw new midcom_error_notfound("Moving only topics and articles is supported.");
        }

        $this->_object->require_do('midgard:update');

        if ($request->request->has('move_to')) {
            try {
                $target = new midcom_db_topic($request->request->getInt('move_to'));
                $this->_move_object($target);
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('moved %s to %s'), $this->_topic->get_label(), $target->get_label()));
            } catch (midcom_error $e) {
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $e->getMessage(), 'error');
            }
        }

        $object_label = midcom_helper_reflector::get($this->_object)->get_object_label($this->_object);

        if (is_a($this->_object, midcom_db_topic::class)) {
            // This is a topic
            $this->_object->require_do('midcom.admin.folder:topic_management');
            $this->current_folder = new midcom_db_topic($this->_object->up);
        } else {
            // This is a regular object
            $this->current_folder = new midcom_db_topic($this->_object->topic);
        }
        $data['handler'] = $this;

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('move %s'), $object_label));

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');
        return $this->get_workflow('viewer')->run($request);
    }

    public function show_tree(midcom_db_topic $folder = null, bool $tree_disabled = false)
    {
        if (null === $folder) {
            $folder = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        }

        if (   is_a($this->_object, midcom_db_topic::class)
            && $folder->up == $this->_object->id) {
            $tree_disabled = true;
        }

        $class = '';
        $selected = '';
        $disabled = '';
        if ($folder->guid == $this->current_folder->guid) {
            $class = 'current';
            $selected = ' checked="checked"';
        }

        if (   !is_a($this->_object, midcom_db_topic::class)
            && $folder->component !== $this->current_folder->component) {
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

        foreach ($qb->execute() as $child) {
            $this->show_tree($child, $tree_disabled);
        }
        echo "</li>\n";
        echo "</ul>\n";
    }

    private function _move_object(midcom_db_topic $target)
    {
        $target->require_do('midgard:create');

        if (is_a($this->_object, midcom_db_topic::class)) {
            $name = $this->_object->name;
            $this->_object->name = ''; // Prevents problematic location to break the site, we set this back below...
            $this->_object->up = $target->id;
            if (!$this->_object->update()) {
                throw new midcom_error('Failed to move the topic, reason ' . midcom_connection::get_error_string());
            }
            // It was ok, so set name back now
            $this->_object->name = $name;
            $this->_object->update();
        } else {
            $this->_object->topic = $target->id;
            if (!$this->_object->update()) {
                throw new midcom_error('Failed to move the article, reason ' . midcom_connection::get_error_string());
            }
        }
    }

    /**
     * Output the style element for move editing
     */
    public function _show_move(string $handler_id, array &$data)
    {
        midcom_show_style('midcom-admin-show-folder-move');
    }
}
