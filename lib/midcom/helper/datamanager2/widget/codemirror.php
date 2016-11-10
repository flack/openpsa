<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 CodeMirror widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string language:</i> Programming language being edited by CodeMirror, for example php or javascript
 * - <i>int width:</i> The number of columns of the textarea, this defaults to 50.
 *   Note that this value might be overridden by CSS.
 * - <i>int height:</i> The number of rows of the textearea, this defaults to 6.
 *   Note that this value might be overridden by CSS.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_codemirror extends midcom_helper_datamanager2_widget
{
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
     * Language of code in editor
     *
     * @var string
     */
    public $language = 'php';

    /**
     * Whether to actually enable the widget
     */
    var $enabled = true;

    /**
     * The initialization event handler post-processes the maxlength setting.
     */
    public function _on_initialize()
    {
        $this->_require_type_value();

        if ($this->enabled) {
            $prefix = MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/codemirror-' . $this->_type->version;
            midcom::get()->head->add_stylesheet($prefix . '/lib/codemirror.css');
            midcom::get()->head->add_jsfile($prefix . '/lib/codemirror.js');
            foreach ($this->_type->modes as $mode) {
                midcom::get()->head->add_jsfile($prefix . '/mode/' . $mode . '/' . $mode . '.js');
            }
            midcom::get()->head->add_jsfile($prefix . '/addon/edit/matchbrackets.js');
            midcom::get()->head->add_jsfile($prefix . '/addon/dialog/dialog.js');
            midcom::get()->head->add_stylesheet($prefix . '/addon/dialog/dialog.css');
            midcom::get()->head->add_jsfile($prefix . '/addon/search/searchcursor.js');
            midcom::get()->head->add_jsfile($prefix . '/addon/search/match-highlighter.js');
            midcom::get()->head->add_jsfile($prefix . '/addon/search/search.js');
            midcom::get()->head->add_stylesheet($prefix . '/addon/display/fullscreen.css');
            midcom::get()->head->add_jsfile($prefix . '/addon/display/fullscreen.js');
        }
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    public function add_elements_to_form($attributes)
    {
        $attributes['rows'] = $this->height;
        $attributes['cols'] = $this->width;
        $attributes['class'] = "codemirror {$this->language}";

        if (!$this->enabled) {
            $attributes['class'] = 'longtext';
        }

        $this->_form->addElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        $config = midcom_helper_misc::get_snippet_content_graceful($this->_config->get('codemirror_config_snippet'));

        $config = str_replace('{$id}', $attributes['id'], $config);
        $config = str_replace('{$read_only}', 'false', $config);

        midcom::get()->head->add_jquery_state_script($config);
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
