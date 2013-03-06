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
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
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
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (   ! array_key_exists('value', $this->_type)
            || is_array($this->_type->value)
            || is_object($this->_type->value))
        {
            debug_add("Warning, the field {$this->name} does not have a value member or it is an array or object, you cannot use the text widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if ($this->enabled)
        {
            $prefix = MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/codemirror-' . $this->_type->version;
            midcom::get('head')->add_stylesheet($prefix . '/lib/codemirror.css');
            midcom::get('head')->add_jsfile($prefix . '/lib/codemirror.js');
            foreach ($this->_type->modes as $mode)
            {
                midcom::get('head')->add_jsfile($prefix . '/mode/' . $mode . '/' . $mode . '.js');
            }
            midcom::get('head')->add_jsfile($prefix . '/addon/edit/matchbrackets.js');
            midcom::get('head')->add_jsfile($prefix . '/addon/dialog/dialog.js');
            midcom::get('head')->add_stylesheet($prefix . '/addon/dialog/dialog.css');
            midcom::get('head')->add_jsfile($prefix . '/addon/search/searchcursor.js');
            midcom::get('head')->add_jsfile($prefix . '/addon/search/match-highlighter.js');
            midcom::get('head')->add_jsfile($prefix . '/addon/search/search.js');
        }

        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form($attributes)
    {
        $attributes = array_merge($attributes, array
        (
            'rows' => $this->height,
            'cols' => $this->width,
            'class' => "codemirror {$this->language}",
            'id'    => "{$this->_namespace}{$this->name}",
        ));

        if (!$this->enabled)
        {
            $attributes['class'] = 'longtext';
        }

        $this->_form->addElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        $config = midcom_helper_misc::get_snippet_content_graceful($this->_config->get('codemirror_config_snippet'));

        $config = str_replace('{$id}', $attributes['id'], $config);
        $config = str_replace('{$read_only}', 'false', $config);

        midcom::get('head')->add_jquery_state_script($config);
    }

    function get_default()
    {
        return $this->_type->value;
    }

    function sync_type_with_widget($results)
    {
        $this->_type->value = $results[$this->name];
    }
}
?>