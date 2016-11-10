<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple privilege
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
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
     */
    protected $_elements = null;

    /**
     * The initialization event handler validates the base type.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('midcom_helper_datamanager2_type_privilege');
    }

    /**
     * Adds the radiobox triplet to the form if and only if we have the permissions.
     */
    public function add_elements_to_form($attributes)
    {
        if (   $this->_type->storage->object
            && !$this->_type->storage->object->can_do('midgard:privileges'))
        {
            return;
        }
        $effective_value = $this->_type->get_effective_value();
        if (   $this->_type->get_value() === MIDCOM_PRIVILEGE_INHERIT
            || ($this->_type->get_value() !== MIDCOM_PRIVILEGE_DENY) !== $effective_value)
        {
            $effective_value = $effective_value ? 'allow' : 'deny';
            $inherit_label = sprintf($this->_l10n->get('widget privilege: inherit %s'), $this->_l10n->get('widget privilege: ' . $effective_value));
        }
        else
        {
            $inherit_label = $this->_l10n->get('widget privilege: inherit');
        }

        $elements = array();

        $elements[] = $this->_form->createElement
        (
            'radio',
            null,
            MIDCOM_PRIVILEGE_ALLOW,
            $this->_l10n->get('widget privilege: allow'),
            MIDCOM_PRIVILEGE_ALLOW,
            array('class' => 'radiobutton')
        );
        $elements[] = $this->_form->createElement
        (
            'radio',
            null,
            MIDCOM_PRIVILEGE_DENY,
            $this->_l10n->get('widget privilege: deny'),
            MIDCOM_PRIVILEGE_DENY,
            array('class' => 'radiobutton')
        );
        $elements[] = $this->_form->createElement
        (
            'radio',
            null,
            MIDCOM_PRIVILEGE_INHERIT,
            $inherit_label,
            MIDCOM_PRIVILEGE_INHERIT,
            array('class' => 'radiobutton')
        );

        $this->_elements = $elements;

        $group = $this->_form->addGroup
        (
            $this->_elements,
            $this->name,
            $this->_translate($this->_field['title']),
            "&nbsp;"
        );
        $group->setAttributes(array('class' => 'radiobox'));
    }

    public function get_default()
    {
        return $this->_type->get_value();
    }

    /**
     * Synchronizes if and only if we have the permissions.
     */
    public function sync_type_with_widget($results)
    {
        if (   $this->_type->storage->object
            && !$this->_type->storage->object->can_do('midgard:privileges'))
        {
            return;
        }

        $this->_type->set_value($results[$this->name]);
    }

    public function is_frozen()
    {
        if (    (   $this->_type->storage->object
                 && !$this->_type->storage->object->can_do('midgard:privileges'))
             || !$this->_elements)
        {
            return false;
        }
        return $this->_elements[0]->isFrozen();
    }

    public function freeze()
    {
        if (    (   $this->_type->storage->object
                 && !$this->_type->storage->object->can_do('midgard:privileges'))
             || !$this->_elements)
        {
            return;
        }

        foreach (array_keys($this->_elements) as $index)
        {
            $this->_elements[$index]->freeze();
        }
    }

    public function unfreeze()
    {
        if (    (   $this->_type->storage->object
                 && !$this->_type->storage->object->can_do('midgard:privileges'))
             || !$this->_elements)
        {
            return;
        }

        foreach (array_keys($this->_elements) as $index)
        {
            $this->_elements[$index]->unfreeze();
        }
    }
}
