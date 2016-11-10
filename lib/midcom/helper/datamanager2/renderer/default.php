<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * A renderer for HTML_QuickForm
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_renderer_default extends HTML_QuickForm_Renderer
{
    /**
     * Form template string
     *
     * @var string
     */
    private $_form_template = "\n<form{attributes} class=\"datamanager2\" >\n<div class=\"form\">\n{hidden} \n{content}\n\n</div>\n</form>";

    /**
     * Header Template string
     *
     * @var string
     */
    private $_header_template = "\n\t<div class='header'>\n\t\t{header}\n\t</div>";

    /**
     * Element template string
     *
     * @var string
     */
    private $_element_template = "<div class=\"element {type}<!-- BEGIN error --> error<!-- END error --><!-- BEGIN required --> required<!-- END required -->\" id=\"{element_name}_container\"><label for='{namespace}{element_name}' id='{element_name}_label'>\n\t\t
        <span class=\"field_text\">
                {label}<!-- BEGIN required --> <span class=\"field_required_start\">*</span><!-- END required --></span></label>\n\t\t
        <div class=\"input\">
        <!-- BEGIN error --><span class='field_error'>{error}</span><br /><!-- END error -->\t{element}</div>\n\t</div>\n\t";

    /**
     * Element template string
     *
     * @var string
     */
    private $_hidden_template = "<!-- BEGIN error --><span class='field_error'>{error}</span><br /><!-- END error -->\t{element}\n\t";

    /**
     * Group template string
     *
     * @var string
     */
    private $_default_group_template = "<div class=\"element<!-- BEGIN required --> required<!-- END required -->\" id='{element_name}_container'>\n\t\t
        <label><span class=\"field_text\">
                {label}<!-- BEGIN required --> <span class=\"field_required_start\">*</span><!-- END required --></span></label>\n\t\t
        <div class=\"input\">
        <!-- BEGIN error --><span class='field_error'>{error}</span><br /><!-- END error -->\n\t\t
            <fieldset id='{element_name}_fieldset' {attributes}>
                {element}\n\t\t
            </fieldset>
        </div>
        </div>";

    private $_group_template;

    /**
     * Required Note template string
     *
     * @var string
     */
    private $_required_note_template = "\n\t<div class=\"required_note\">\n\t\t\n\t{requiredNote}</div>\n\t";

    /**
     * Array containing the templates for customised elements
     *
     * @var array
     */
    private $_templates = array
    (
        'form_toolbar' => "<div class='form_toolbar'>{element}</div>",
    );

    /**
     * Array containing the templates for elements in groups.
     *
     * @var array
     */
    private $_group_element_templates = array();

    /**
     * Array containing the templates for group wraps.
     *
     * These templates are wrapped around group elements and the group's own
     * templates wrap around them. This is set by setGroupTemplate().
     *
     * @var array
     */
    private $_group_wraps = array();

    /**
     * True if we are inside a group
     *
     * @var boolean
     */
    private $_in_group = false;

    /**
     * Array with HTML generated for group elements
     *
     * @var array
     */
    private $_group_elements = array();

    /**
     * Template for an element inside a group
     *
     * @var string
     */
    private $_group_element_template = '';

    /**
     * HTML that wraps around the group elements
     *
     * @var string
     */
    private $_group_wrap = '';

    /**
     * The current group running
     *
     * @var string
     */
    private $_current_group_name = "";

    /**
     * The stack of groups
     *
     * @var array
     */
    private $_current_groups = array();

    /**
     * All group templates for running groups (i.e. prepared and ready).
     *
     * @var array
     */
    private $_current_group_templates = array();

    /**
     * Collected HTML of the hidden fields
     *
     * @var string
     */
    private $_hidden_html = '';

    /**
     * The HTML of the form
     *
     * @var string
     */
    private $_html;

    /**
     * Constructor
     */
    public function __construct($namespace = '')
    {
        $this->namespace = $namespace;
        $this->_group_template = $this->_default_group_template;
    }

    /**
     * returns the HTML generated for the form
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->_html;
    }

    /**
     * Called when visiting a form, before processing any form elements
     *
     * @param HTML_QuickForm &$form The object being visited
     */
    public function startForm(&$form)
    {
        $this->_html = '';
        $this->_hidden_html = '';
    }

    /**
     * Called when visiting a form, after processing all form elements
     * Adds required note, form attributes, validation javascript and form content.
     *
     * @param HTML_QuickForm &$form The object being visited
     */
    public function finishForm(&$form)
    {
        // add a required note if needed
        if (   !empty($form->_required)
            && !$form->_freezeAll) {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_required_note_template);
        }
        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributes(true), $this->_form_template);
        if (strpos($this->_form_template, '{hidden}')) {
            $html = str_replace('{hidden}', $this->_hidden_html, $html);
        } else {
            $this->_html .= $this->_hidden_html;
        }
        $this->_html = str_replace('{content}', $this->_html, $html);

        // add a validation script
        if ('' != ($script = $form->getValidationScript())) {
            $this->_html = $script . "\n" . $this->_html;
        }
    }

    /**
     * Helper method for renderElement
     *
     * @param string $name Element name
     * @param HTML_Quickform_element $element The element we're working on
     * @param boolean $required Whether an element is required
     * @param string $error Error message associated with the element
     * @param string $type Element type
     * @see renderElement()
     * @return string HTML for the element
     */
    private function _prepare_template($name, $element, $required, $error, $type)
    {
        if (isset($this->_templates[$name])) {
            $template = $this->_templates[$name];
        } else {
            switch ($type) {
                case 'group':
                    $template = $this->_default_group_template;
                    break;
                case 'hidden':
                    $template = $this->_hidden_template;
                    break;
                default:
                    $template = $this->_element_template;
                    break;
            }
        }

        $helptext = $this->_extract_helptext($element);
        $label = $element->getLabel();

        if (is_array($label)) {
            $nameLabel = array_shift($label);
        } else {
            $nameLabel = $label;
        }

        $html = str_replace('{label}', $nameLabel, $template);
        $html = str_replace('{type}', 'element_' . $type, $html);
        $html = str_replace('{namespace}', $this->namespace, $html);

        $this->_process_placeholder($html, 'required', $required);
        $this->_process_placeholder($html, 'error', $error);
        $this->_process_placeholder($html, 'helptext', $helptext);

        if (is_array($label)) {
            foreach ($label as $key => $text) {
                $key  = is_int($key) ? $key + 2 : $key;
                $html = str_replace("{label_{$key}}", $text, $html);
                $html = str_replace("<!-- BEGIN label_{$key} -->", '', $html);
                $html = str_replace("<!-- END label_{$key} -->", '', $html);
            }
        }

        $html = str_replace('{element_name}', $name, $html);
        if (strpos($html, '{label_')) {
            $html = preg_replace('/<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->/i', '', $html);
        }

        return $html;
    }

    private function _process_placeholder(&$html, $identifier, $value)
    {
        if ($value) {
            if (is_string($value)) {
                $html = str_replace('{' . $identifier . '}', $value, $html);
            }
            $html = str_replace('<!-- BEGIN ' . $identifier . ' -->', '', $html);
            $html = str_replace('<!-- END ' . $identifier . ' -->', '', $html);
        } else {
            $html = preg_replace("/<!-- BEGIN " . $identifier . " -->.*?<!-- END " . $identifier . " -->/is", '', $html);
        }
    }

    private function _extract_helptext(&$element)
    {
        $helptext = '';
        $attributes = $element->getAttributes();
        if (!empty($attributes['helptext'])) {
            $helptext = $element->getLabel() . "|" . $attributes['helptext'];
        }

        unset($attributes['helptext']);
        $element->setAttributes($attributes);
        return $helptext;
    }

    /**
     * @inheritDoc
     */
    public function renderElement(&$element, $required, $error)
    {
        if (!$this->_in_group) {
            $html = $this->_prepare_template($element->getName(), $element, $required, $error, $element->getType());
            $this->_html .= str_replace('{element}', $element->toHtml(), $html);
        } elseif (!empty($this->_group_element_template)) {
            $html = str_replace('{label}', $element->getLabel(), $this->_group_element_template);
            $helptext = $this->_extract_helptext($element);
            $this->_process_placeholder($html, 'required', $required);
            $this->_process_placeholder($html, 'error', $error);
            $this->_process_placeholder($html, 'helptext', $helptext);

            $this->_group_elements[] = str_replace('{element}', $element->toHtml(), $html);
        } else {
            $this->_extract_helptext($element);
            $this->_group_elements[] = $element->toHtml();
        }
    }

    /**
     * @inheritDoc
     */
    public function renderHidden(&$element, $required, $error)
    {
        $html = $this->_prepare_template($element->getName(), $element, $required, $error, 'hidden');
        $this->_extract_helptext($element);
        $this->_hidden_html .= str_replace('{element}', $element->toHtml(), $html);
    }

    /**
     * Called when visiting a header element
     *
     * @param HTML_QuickForm_header &$header The element being visited
     */
    public function renderHeader(&$header)
    {
        $name = $header->getName();
        if (   !empty($name)
            && isset($this->_templates[$name])) {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        } else {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_header_template);
        }
    }

    /**
     * Called when visiting a raw HTML/text pseudo-element
     *
     * @param HTML_QuickForm_html &$data The element being visited
     */
    public function renderHtml(&$data)
    {
        $this->_html .= $data->toHtml();
    }

    /**
     * Called when visiting a group, before processing any group elements
     *
     * @param HTML_QuickForm_group &$group The object being visited
     * @param boolean $required Whether a group is required
     * @param string $error An error message associated with a group
     */
    public function startGroup(&$group, $required, $error)
    {
        $name = $group->getName();

        $this->_current_group_templates[$name] = array
        (
            'group_template' => $this->_prepare_template($name, $group, $required, $error, 'group'),
            'group_element_template' => empty($this->_group_element_templates[$name]) ? '' : $this->_group_element_templates[$name],
            'group_wrap' => empty($this->_group_wraps[$name]) ? '' : $this->_group_wraps[$name],
            'group_elements' => array(),
        );

        $this->_in_group = true;
        $this->_set_group_templates($name);
        $this->_current_group_name = $name;
        $this->_current_groups[] = $name;
    }

    /**
     * Called when visiting a group, after processing all group elements
     *
     * @param HTML_QuickForm_group &$group The object being visited
     */
    public function finishGroup(&$group)
    {
        $separator = $group->_separator;
        if (is_array($separator)) {
            $count = count($separator);
            $html  = '';
            for ($i = 0; $i < count($this->_group_elements); $i++) {
                $html .= (0 == $i ? '' : $separator[($i - 1) % $count]) . $this->_group_elements[$i];
            }
        } else {
            if (is_null($separator)) {
                $separator = '&nbsp;';
            }
            $html = implode((string) $separator, $this->_group_elements);
        }

        if (!empty($this->_group_wrap)) {
            $html = str_replace('{content}', $html, $this->_group_wrap);
        }

        $template = str_replace('{attributes}', $group->getAttributes(true), $this->_group_template);

        $html = str_replace('{element}', $html, $template);

        array_pop($this->_current_groups);
        $current_group_id = count($this->_current_groups) - 1;
        if ($current_group_id > -1) {
            $this->_current_group_name = $this->_current_groups[$current_group_id];
            $this->_current_group_templates[$this->_current_group_name]['group_elements'][] = $html;
            $this->_set_group_templates($this->_current_group_name);
            $this->_in_group = true;
        } else {
            $this->_current_group_name = "";
            $this->_html .= $html;
            $this->_in_group = false;
        }
    }

    /**
     * Called by start/finishGroup() to set the current template elements
     *
     * @param string $name The group to set the templates to.
     */
    private function _set_group_templates($name)
    {
        $this->_group_template = $this->_current_group_templates[$name]['group_template'];
        $this->_group_element_template = $this->_current_group_templates[$name]['group_element_template'];
        $this->_group_wrap = $this->_current_group_templates[$name]['group_wrap'];
        $this->_group_elements = $this->_current_group_templates[$name]['group_elements'];
    }

    /**
     * Sets element template
     *
     * @param string $html The HTML surrounding an element
     * @param string $element The (optional) name of the element to apply template for
     */
    public function setElementTemplate($html, $element = null)
    {
        if (is_null($element)) {
            $this->_element_template = $html;
        } else {
            $this->_templates[$element] = $html;
        }
    }

    /**
     * Sets template for a group wrapper
     *
     * This template is contained within a group-as-element template
     * set via setTemplate() and contains group's element templates, set
     * via setGroupElementTemplate()
     *
     * @param string $html The HTML surrounding group elements
     * @param string $group The name of the group to apply template for
     */
    public function setGroupTemplate($html, $group)
    {
        $this->_group_wraps[$group] = $html;
    }

    /**
     * Sets element template for elements within a group
     *
     * @param string $html The HTML surrounding an element
     * @param string $group The name of the group to apply template for
     */
    public function setGroupElementTemplate($html, $group)
    {
        $this->_group_element_templates[$group] = $html;
    }

    /**
     * Sets header template
     *
     * @param string $html The HTML surrounding the header
     */
    public function setHeaderTemplate($html)
    {
        $this->_header_template = $html;
    }

    /**
     * Sets form template
     *
     * @param string $html The HTML surrounding the form tags
     */
    public function setFormTemplate($html)
    {
        $this->_form_template = $html;
    }

    /**
     * Sets the note indicating required fields template
     *
     * @param string $html The HTML surrounding the required note
     */
    public function setRequiredNoteTemplate($html)
    {
        $this->_required_note_template = $html;
    }

    /**
     * Clears all the HTML out of the templates that surround notes, elements, etc.
     * Useful when you want to use addData() to create a completely custom form look
     */
    public function clearAllTemplates()
    {
        $this->setElementTemplate('{element}');
        $this->setFormTemplate("\n\t<form{attributes}>{content}\n\t</form>\n");
        $this->setRequiredNoteTemplate('');
        $this->_templates = array();
    }
}
