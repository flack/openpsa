<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 password widget
 *
 * This is a simple widget to request a password. It will render two password input fields,
 * whose values need to be identical for the password to be accepted. HTML_QuickForm compare
 * rules are used to enforce this. The value is taken from any valid text type which has a
 * value member, but initialization is always with an empty string, as this widget cannot
 * (and should not) display an existing password. The rule is, that as long as the value
 * is empty, no password change will be given to the caller during the synchronization
 * (effectively skipping the operation).
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string confirm_text:</i> The confirm-note appended to the second fields label. This
 *   is put through the regular schema translation pipe and defaults to '(confirm)'.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_password extends midcom_helper_datamanager2_widget
{
    /**
     * The confirmation note appended to the label of the second, password confirmation
     * field.
     *
     * @var string
     */
    var $confirm_text = '(confirm)';

    /**
     * Explicitly allow empty password
     *
     * @var boolean
     */
    var $require_password = true;

    /**
     * The initialization event handler verifies the used type.
     */
    public function _on_initialize()
    {
        $this->_require_type_value();
    }

    /**
     * Adds a pair of password input fields as a group to the form.
     */
    public function add_elements_to_form($attributes)
    {
        $attributes = array_merge($attributes, array('class' => 'shorttext'));
        $title = $this->_translate($this->_field['title']);
        $confirm_name = "{$this->name}_confirm";
        $confirm_title = "{$title} " . $this->_translate($this->confirm_text);
        $this->_form->addElement('password', $this->name, $title, $attributes);
        $this->_form->addElement('password', $confirm_name, $confirm_title, array('class' => 'shorttext'));
        $this->_form->addRule(array($this->name, $confirm_name), $this->_translate('passwords do not match'), 'compare', null);

        if ($this->require_password)
        {
            $this->_form->addRule($this->name, 'required', 'required', '');
            $this->_form->addRule($confirm_name, 'required', 'required', '');
        }
    }

    /**
     * Unfreeze the confirmation widget as well as the main widget.
     */
    public function freeze()
    {
        parent::freeze();
        $confirm = $this->_form->getElement("{$this->name}_confirm");
        $confirm->unfreeze();
    }

    public function sync_type_with_widget($results)
    {
        if ($results[$this->name] != '')
        {
            $this->_type->value = $results[$this->name];
        }
    }
}
