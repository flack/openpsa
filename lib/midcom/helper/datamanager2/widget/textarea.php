<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple textarea widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int maxlength:</i> The maximum length of the string allowed for this field.
 *   This includes any newlines, which account as at most two characters, depending
 *   on the OS. If you specify a 0, no maximum length is set. If you specify a -1,
 *   maximum length is inherited from the type, if applicable or unlimited otherwise.
 *   If a maximum length is set, an appropriate validation rule is created implicitly.
 *   A -1 setting is processed during startup and has no effect at a later time.
 * - <i>int width:</i> The number of columns of the textarea, this defaults to 50.
 *   Note that this value might be overridden by CSS.
 * - <i>int height:</i> The number of rows of the textearea, this defaults to 6.
 *   Note that this value might be overridden by CSS.
 * - <i>string wrap:</i> Controls the textbox wrapping, defaults to 'virtual' text is
 *   wrapped by the browser, but the automatic wraps are not sent to the server. You
 *   can set this to 'off' or 'physical'. If you set this to an empty string, the
 *   attribute is omitted.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_textarea extends midcom_helper_datamanager2_widget
{
    /**
     * Maximum length of the string encapsulated by this type. 0 means no limit.
     * -1 tries to bind to the type's maxlength member, if available.
     *
     * @var int
     */
    public $maxlength = -1;

    /**
     * Width of the textbox.
     *
     * @var int
     */
    public $width = 50;

    /**
     * Height of the textbox.
     *
     * @var int
     */
    public $height = 6;

    /**
     * Wrapping mode of the textbox.
     *
     * @var string
     */
    public $wrap = 'virtual';

    /**
     * The initialization event handler post-processes the maxlength setting.
     */
    public function _on_initialize()
    {
        $this->_require_type_value();

        if ($this->maxlength == -1) {
            if (property_exists($this->_type, 'maxlength')) {
                $this->maxlength = $this->_type->maxlength;
            }
        }
        if ($this->maxlength < 0) {
            $this->maxlength = 0;
        }
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    public function add_elements_to_form($attributes)
    {
        $attributes['rows'] = $this->height;
        $attributes['cols'] = $this->width;
        $attributes['class'] = 'longtext';
        if ($this->wrap != '') {
            $attributes['wrap'] = $this->wrap;
        }

        $this->_form->addElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        if ($this->maxlength > 0) {
            $errormsg = sprintf($this->_l10n->get('type text: value is longer than %d characters'), $this->maxlength);
            $this->_form->addRule($this->name, $errormsg, 'maxlength', $this->maxlength);
        }
        if (!empty($this->_type->forbidden_patterns)) {
            $this->_form->addFormRule(array(&$this->_type, 'validate_forbidden_patterns'));
        }
        if (!empty($this->_type->allowed_patterns)) {
            $this->_form->addFormRule(array(&$this->_type, 'validate_allowed_patterns'));
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
