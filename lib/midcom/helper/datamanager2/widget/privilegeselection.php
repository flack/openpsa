<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 privilege selection
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports the privilege type only. It shows a menu rendered as multi state checkbox having
 * allowed, denied and inherited as options and default selected according to the type's value.
 *
 * The widget will not display itself if the user does not have privileges permission on
 * the storage object.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_privilegeselection extends midcom_helper_datamanager2_widget
{
    /**
     * The QF Element added to the form. Saved for freezing/unfreezing.
     *
     * @var Array
     */
    protected $_element = null;

    /**
     * Holds the privileges to be included in the selection widget
     *
     * @var Array
     */
    protected $_privilege_options = null;

    /**
     * Holds the javascript to render the privilege selection widget
     *
     * @var String
     */
    protected $_jscript = '';

    /**
     * The initialization event handler validates the base type.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('midcom_helper_datamanager2_type_privilege');

        $this->_privilege_options = array
        (
            MIDCOM_PRIVILEGE_INHERIT => $this->_l10n->get('widget privilege: inherit'),
            MIDCOM_PRIVILEGE_ALLOW => $this->_l10n->get('widget privilege: allow'),
            MIDCOM_PRIVILEGE_DENY => $this->_l10n->get('widget privilege: deny'),
        );

        midcom::get()->head->enable_jquery();

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/privilege/jquery.privilege.css');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/privilege/jquery.privilege.js');
    }

    /**
     * Adds the elements to the form if we have the permissions.
     */
    function add_elements_to_form($attributes)
    {
        if (   $this->_type->storage->object
            && ! $this->_type->storage->object->can_do('midgard:privileges'))
        {
            return;
        }

        $elements = array();

        $select_attributes = Array
        (
            'class' => 'dropdown privilegeselection',
            'id'    => "{$this->_namespace}{$this->name}",
        );
        $this->_element = $this->_form->createElement('select', $this->name, '',
            $this->_privilege_options, $select_attributes);

        $this->_jscript = '<script type="text/javascript">';
        $this->_jscript .= 'jQuery("#' . $this->_namespace . $this->name . '_holder").render_privilege();';
        $this->_jscript .= '</script>';

        $elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_namespace}{$this->name}_holder_start",
            '',
            "<div id=\"{$this->_namespace}{$this->name}_holder\">"
        );
        $elements[] = $this->_element;
        $elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_namespace}{$this->name}_holder_end",
            '',
            "</div>"
        );
        $elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_namespace}{$this->name}_initscripts",
            '',
            $this->_jscript
        );

        $this->_form->addGroup
        (
            $elements,
            $this->name,
            '',
            '',
            Array('class' => 'privilegeselection')
        );
    }

    function get_default()
    {
        $key = $this->_type->get_value();
        if (! $key)
        {
            reset($this->_privilege_options);
            $key = key($this->_privilege_options);
        }

        return array($this->name => $key);
    }

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
             || ! $this->_element)
        {
            return false;
        }
        return $this->_element->isFrozen();
    }

    function freeze()
    {
        if (    (   $this->_type->storage->object
                 && ! $this->_type->storage->object->can_do('midgard:privileges'))
             || ! $this->_element)
        {
            return;
        }

        $this->_element->freeze();
    }

    function unfreeze()
    {
        if (    (   $this->_type->storage->object
                 && ! $this->_type->storage->object->can_do('midgard:privileges'))
             || ! $this->_element)
        {
            return;
        }

        $this->_element->unfreeze();
    }
}
?>