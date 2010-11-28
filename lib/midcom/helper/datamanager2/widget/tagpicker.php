<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: tagpicker.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 tag picker widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
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
 * - <i>int size:</i> The size of the input box. Defaulting to 40. Note that this value
 *   might be overridden by CSS.
 * - <i>boolean hideinput:</i> Set this to true if you want to hide the input in the widget,
 *   this usually means that a password HTML element will be used, instead of a regular
 *   text input widget. Defaults to false.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_tagpicker extends midcom_helper_datamanager2_widget
{
    /**
     * Maximum number of the tags accepted
     * 0 means no limit
     *
     * @var int
     */
    public $maxtags = 0;

    /**
     * The size of the input box.
     *
     * @var int
     */
    public $size = 40;

    /**
     * Whether the input should be shown in the widget, or not.
     *
     * @var boolean
     */
    public $hideinput = false;

    /**
     * Whether to allow users to add tags that are not yet in database
     *
     * @var boolean
     */
    public $allow_other = false;

    var $_taglist_html = '';

    /**
     * The initialization event handler post-processes the maxtags setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (is_a('midcom_helper_datamanager2_type_tags', $this->_type))
        {
            debug_add("Warning, the field {$this->name} is not a tags type or subclass thereof, you cannot use the tagpicker widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if (array_key_exists('maxtags', $this->_type))
        {
            $this->maxtags = $this->_type->maxtags;
        }

        if ($this->maxtags < 0)
        {
            $this->maxtags = 0;
        }

        $_MIDCOM->load_library('net.nemein.tag');

        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $attributes = Array
        (
            'size' => $this->size,
            'class' => 'shorttext tagpicker_input',
            'id'    => "{$this->_namespace}{$this->name}",
        );

        if ($this->hideinput)
        {
            $attributes['style'] = 'display: none;';
        }

        $this->_taglist_html = $this->_generate_tag_list();

        $this->_form->addElement('text', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        $this->_form->addElement('static', "{$this->name}_taglist", '', $this->_taglist_html);
    }

    function get_default()
    {
        return $this->_type->value;
    }

    function sync_type_with_widget($results)
    {
        if (   $this->allow_other
            || empty($results[$this->name]))
        {
            // The simple way, just take what is given
            $this->_type->value = $results[$this->name];
            return;
        }

        // Otherwise, validate the results
        $tags_in_db = net_nemein_tag_handler::get_tags();

        $tags_from_user = explode(' ', $results[$this->name]);

        $tags_to_save = array();

        foreach ($tags_from_user as $tag)
        {
            if (!isset($tags_in_db[$tag]))
            {
                // We don't have this tag, skip.
                continue;
            }

            $tags_to_save[] = $tag;
        }

        $this->_type->value = implode(' ', $tags_to_save);
    }

    function _generate_tag_list()
    {
        $html = '';
        $tags = net_nemein_tag_handler::get_tags();
        $object_tags = explode(' ', $this->_type->value);

        if (! empty($tags))
        {
            $html .= "<ul>\n";

            foreach ($tags as $tag => $data)
            {
                $classname = 'enabled';
                if (in_array($tag, $object_tags))
                {
                    $classname = 'selected';
                }

                $html .= "<li class=\"{$classname}\"><span>{$tag}</span></li>\n";
            }

            $html .= "</ul>\n";
        }

        return $html;
    }
}
?>