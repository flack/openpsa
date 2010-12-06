<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 privilege checkbox widget.
 *
 * Based on the regular checkbox widget but with advanced checks that hide the form elelments
 * in case the user does not have sufficient privileges.
 *
 * This type requires a privilege base type.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_privilegecheckbox extends midcom_helper_datamanager2_widget_checkbox
{
    /**
     * The initialization event handler validates the base type
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (! is_a($this->_type, 'midcom_helper_datamanager2_type_privilegeset'))
        {
            debug_add("Warning, the field {$this->name} is not of type privilegeset.", MIDCOM_LOG_WARN);
            return false;
        }

        return parent::_on_initialize();
    }

    /**
     * Adds the checkbox if and only if either the base object is yet null (new object being created)
     * or the user has privileges permissions on the base object.
     */
    function add_elements_to_form()
    {
        if (   ! $this->_type->storage->object
            || $this->_type->storage->object->can_do('midgard:privileges'))
        {
            parent::add_elements_to_form();
        }
    }

    /**
     * Synchronizes if and only if either the base object is yet null (new object being created)
     * or the user has privileges permissions on the base object.
     */
    function sync_type_with_widget($results)
    {
        if (   ! $this->_type->storage->object
            || $this->_type->storage->object->can_do('midgard:privileges'))
        {
            parent::sync_type_with_widget($results);
        }
    }

    /**
     * Checks if and only if either the base object is yet null (new object being created)
     * or the user has privileges permissions on the base object.
     *
     * Otherwise, the fucntion will return false always.
     */
    function is_frozen()
    {
        if (   ! $this->_type->storage->object
            || $this->_type->storage->object->can_do('midgard:privileges'))
        {
            return parent::is_frozen();
        }
        else
        {
            return false;
        }
    }
}
?>