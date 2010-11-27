<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: privilege.php 25327 2010-03-18 17:48:42Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple privilege
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports the privilege type only. It shows a triplet of radioboxes having
 * allowed, denied and inherited as option according to the type's value.
 *
 * The widget will not display itself if the user does not have privileges permission on
 * the storage object.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_privilege extends midcom_helper_datamanager2_widget
{
    /**
     * The QF Element list added to the form. Saved for freezing/unfreezing.
     *
     * @var Array
     * @access protected
     */
    var $_elements = null;

    /**
     * The initialization event handler validates the base type.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (! is_a($this->_type, 'midcom_helper_datamanager2_type_privilege'))
        {
            debug_add("Warning, the field {$this->name} is not of type privilege.", MIDCOM_LOG_WARN);
            return false;
        }
        return true;
    }

    /**
     * Adds the radibox triplet to the form if and only if we have the permissions.
     */
    function add_elements_to_form()
    {
        if (   $this->_type->storage->object
            && ! $this->_type->storage->object->can_do('midgard:privileges'))
        {
            return;
        }

        $elements = Array();

        $elements[] = HTML_QuickForm::createElement
        (
            'radio',
            null,
            MIDCOM_PRIVILEGE_ALLOW,
            $this->_l10n->get('widget privilege: allow'),
            MIDCOM_PRIVILEGE_ALLOW,
            Array('class' => 'radiobutton')
        );
        $elements[] = HTML_QuickForm::createElement
        (
            'radio',
            null,
            MIDCOM_PRIVILEGE_DENY,
            $this->_l10n->get('widget privilege: deny'),
            MIDCOM_PRIVILEGE_DENY,
            Array('class' => 'radiobutton')
        );
        $elements[] = HTML_QuickForm::createElement
        (
            'radio',
            null,
            MIDCOM_PRIVILEGE_INHERIT,
            $this->_l10n->get('widget privilege: inherit'),
            MIDCOM_PRIVILEGE_INHERIT,
            Array('class' => 'radiobutton')
        );

        $this->_elements = $elements;

        $group = $this->_form->addGroup
        (
            $this->_elements,
            $this->name,
            $this->_translate($this->_field['title']),
            "&nbsp;"
        );
        $group->setAttributes(Array('class' => 'radiobox'));
    }

    function get_default()
    {
        return $this->_type->get_value();
    }

    /**
     * Synchronizes  if and only if we have the permissions.
     */
    function sync_type_with_widget($results)
    {
        if (   $this->_type->storage->object
            && ! $this->_type->storage->object->can_do('midgard:privileges'))
        {
            return;
        }

        $this->_type->set_value($results[$this->name]);
    }

    function is_frozen()
    {
        if (    (   $this->_type->storage->object
                 && ! $this->_type->storage->object->can_do('midgard:privileges'))
             || ! $this->_elements)
        {
            return false;
        }
        else
        {
            return $this->_elements[0]->isFrozen();
        }
    }

    function freeze()
    {
        if (    (   $this->_type->storage->object
                 && ! $this->_type->storage->object->can_do('midgard:privileges'))
             || ! $this->_elements)
        {
            return;
        }


        foreach (array_keys($this->_elements) as $index)
        {
            $this->_elements[$index]->freeze();
        }
    }

    function unfreeze()
    {
        if (    (   $this->_type->storage->object
                 && ! $this->_type->storage->object->can_do('midgard:privileges'))
             || ! $this->_elements)
        {
            return;
        }


        foreach (array_keys($this->_elements) as $index)
        {
            $this->_elements[$index]->unfreeze();
        }
    }
}

?>