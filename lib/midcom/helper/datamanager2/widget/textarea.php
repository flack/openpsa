<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: textarea.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple textarea widget
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
 * - <i>int width:</i> The number of columns of the textarea, this defaults to 50.
 *   Note that this value might be overridden by CSS.
 * - <i>int height:</i> The number of rows of the textearea, this defaults to 6.
 *   Note that this value might be overridden by CSS.
 * - <i>string wrap:</i> Controls the textbox wrapping, defaults to 'virtual' text is
 *   wrapped by the browser, but the automatic wraps are not sent to the server. You
 *   can set this to 'off' or 'physical'. If you set this to an empty string, the
 *   attribute is omitted.
 * - <i>boolean expand</i> If set, then the form will include a link so the user can
 *   expand the textarea.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_textarea extends midcom_helper_datamanager2_widget
{
    /**
     * Maximum length of the string encapsulated by this type. 0 means no limit.
     * -1 tries to bind to the types maxlength member, if available.
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
     * Add expand link to textbox?
     *
     * @var boolean
     */
    public $expand = false;
    
    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (   ! array_key_exists('value', $this->_type)
            || is_array($this->_type->value)
            || is_object($this->_type->value))
        {
            debug_add("Warning, the field {$this->name} does not have a value member or it is an array or object, you cannot use the text widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if ($this->maxlength == -1)
        {
            if (array_key_exists('maxlength', $this->_type))
            {
                $this->maxlength = $this->_type->maxlength;
            }
        }
        if ($this->maxlength < 0)
        {
            $this->maxlength = 0;
        }
        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $attributes = Array
        (
            'rows' => $this->height,
            'cols' => $this->width,
            'class' => 'longtext',
            'id'    => "{$this->_namespace}{$this->name}",
        );
        if ($this->wrap != '')
        {
            $attributes['wrap'] = $this->wrap;
        }
        if ($this->expand) 
        {
            $this->_form->addElement('link', 
                            $this->_l10n->get('expand'),
                            '', 
                            "#",$this->_l10n->get('expand area'),
                            array ('onclick'=> "expandArea(this,'{$attributes['id']}');")
                            );
            $this->_add_expand_js();
        }
        $this->_form->addElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        if ($this->maxlength > 0)
        {
            $errormsg = sprintf($this->_l10n->get('type text: value is longer then %d characters'), $this->maxlength);
            $this->_form->addRule($this->name, $errormsg, 'maxlength', $this->maxlength);
        }
    }

    function get_default()
    {
        return $this->_type->value;
    }

    function sync_type_with_widget($results)
    {
        $this->_type->value = $results[$this->name];
    }
    
    function _add_expand_js() {
        $expand_area = $this->_l10n->get("Expand area");
        $contract_area = $this->_l10n->get("Contract area");    
    
        $script = <<<EOT
    /* http://www.webreference.com/programming/javascript/gr/column9/index.html */
var undefined;
var midcom_helper_datamanager2_textAreas = new Object();
function expandArea(evt, textAreaId) {
    evt = (evt) ? evt: ( (window.event) ? event : null);
    var obj;
    var dbg;
    // initialize a subobject if none exists.
    if ( midcom_helper_datamanager2_textAreas[textAreaId] == undefined) {
        midcom_helper_datamanager2_textAreas[textAreaId] = new Object();
        midcom_helper_datamanager2_textAreas[textAreaId].expanded = false;
    }
    
    if (evt) 
    {   
        obj = document.getElementById(textAreaId);
        if (!midcom_helper_datamanager2_textAreas[textAreaId].expanded) 
        {
            midcom_helper_datamanager2_textAreas[textAreaId].height = obj.style.height;
            midcom_helper_datamanager2_textAreas[textAreaId].width  = obj.style.width;      
        
            pos = getElementPosition(obj.id);
            obj.style.height = window.innerHeight -pos.top -50 + "px";
            obj.style.width = window.innerWidth -pos.left -50 + "px";
            obj.style.border = "solid 1px black";
            midcom_helper_datamanager2_textAreas[textAreaId].expanded = true;
            evt.innerHTML = "$contract_area";
        } 
        else 
        {
            // object is to contract.
            obj.style.height = midcom_helper_datamanager2_textAreas[textAreaId].height;
            obj.style.width = midcom_helper_datamanager2_textAreas[textAreaId].width;   
            evt.innerHTML = "$expand_area";
            midcom_helper_datamanager2_textAreas[textAreaId].expanded = false;
        }
    }
}
EOT;
        $_MIDCOM->add_jscript($script);
    }
}

?>