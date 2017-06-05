<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple text widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member.
 *
 * <b>Available configuration options:</b>
 * At the moment, none.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_hidden extends midcom_helper_datamanager2_widget
{
    /**
     * The initialization event handler post-processes the maxlength setting.
     */
    public function _on_initialize()
    {
        $this->_require_type_value();
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    public function add_elements_to_form($attributes)
    {
        $attributes['class'] = 'hiddentext';
        $this->_form->addElement('hidden', $this->name, null, $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        if (is_a($this->_type, 'midcom_helper_datamanager2_type_number')) {
            $this->_form->addRule($this->name, $this->_translate('validation failed: numeric'), 'regex', '/^-?[0-9]*([.,][0-9]*)?$/');
            $this->_form->addFormRule([&$this->_type, 'validate_number']);
        }
    }

    public function get_default()
    {
        return $this->_type->value;
    }

    public function sync_type_with_widget($results)
    {
        $this->_type->value = $results[$this->name];
    }
}
