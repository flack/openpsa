<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexey Borzov <borz_off@cs.msu.su>                          |
// |          Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: simple.php 24442 2009-12-12 18:20:38Z jbergius $
/** @ignore */
require_once('HTML/QuickForm/Renderer.php');

/**
 * A concrete renderer for HTML_QuickForm,
 * based on QuickForm 2.x built-in one
 * @package midcom.helper.datamanager2
 * @access public
 */
class midcom_helper_datamanager2_renderer_simple extends HTML_QuickForm_Renderer
{
   /**
    * The HTML of the form
    * @var      string
    * @access   private
    */
    var $_html;

   /**
    * Header Template string
    * @var      string
    * @access   private
    */
    var $_headerTemplate =
        "\n\t<div class='ais_header'>\n\t\t{header}\n\t</div>"; // hvorfor tr?

   /**
    * Element template string
    * @var      string
    * @access   private
    */
    var $_elementTemplate = "<label for='{namespace}{element_name}' id='{element_name}_label'<!-- BEGIN required --> class='required'<!-- END required -->>\n\t\t
        <span class=\"field_text\">
                {label}<!-- BEGIN required --> <span class=\"field_required_start\">*</span><!-- END required --></span>\n\t\t

        <!-- BEGIN error --><span class='field_error' style=\"color: #ff0000\">{error}</span><br /><!-- END error -->\t{element}\n\t</label>";

   /**
    * Element template string
    * @var      string
    * @access   private
    */
    /*
    var $_orig_group_template = "<label for='{element_name}' id='{element_name}_label'<!-- BEGIN required --> class='required'<!-- END required -->>\n\t\t
        <span class=\"field_text\">
                {label}<!-- BEGIN required --> <span class=\"field_required_start\">*</span><!-- END required --></span>\n\t\t

        <!-- BEGIN error --><span class='field_error' style=\"color: #ff0000\">{error}</span><br /><!-- END error -->\n\t\t
            <fieldset id='{element_name}_fieldset' {attributes}>
                {element}\n\t\t
            </fieldset>
        </label>";
    */
    var $_orig_group_template = "<div id='{element_name}_label'<!-- BEGIN required --> class='required'<!-- END required -->>\n\t\t
        <label><span class=\"field_text\">
                {label}<!-- BEGIN required --> <span class=\"field_required_start\">*</span><!-- END required --></span></label>\n\t\t

        <!-- BEGIN error --><span class='field_error' style=\"color: #ff0000\">{error}</span><br /><!-- END error -->\n\t\t
            <fieldset id='{element_name}_fieldset' {attributes}>
                {element}\n\t\t
            </fieldset>
        </div>";
    var $_groupTemplate;

   /**
    * Form template string
    * @var      string
    * @access   private
    */
    var $_formTemplate =
        "\n<form{attributes} class=\"datamanager2\" >\n<div>\n{hidden} \n{content}\n\n</div>\n</form>";

   /**
    * Required Note template string
    * @var      string
    * @access   private
    */
    var $_requiredNoteTemplate =
        "\n\t<div class=\"required_note\">\n\t\t\n\t{requiredNote}</div>\n\t";

   /**
    * Array containing the templates for customised elements
    * @var      array
    * @access   private
    */
    var $_templates = array
    (
        'form_toolbar' => "<div class='form_toolbar'>{element}</div>",
        'datamanager_table' => "\n<table cellspacing='0' class='datamanager_table' {attributes} >\n {element}\n</table>\n",
        'datamanager_table_rows'   => "\n<tr {attributes} >\n{element}\n</tr>\n",
        'datamanager_table_rows_header'   => "\n<tr {attributes} >\n{element}\n</tr>\n",

    );

   /**
    * Array containing the templates for group wraps.
    *
    * These templates are wrapped around group elements and groups' own
    * templates wrap around them. This is set by setGroupTemplate().
    *
    * @var      array
    * @access   private
    */
    var $_groupWraps = array();

   /**
    * Array containing the templates for elements within groups
    * @var      array
    * @access   private
    */
    var $_groupTemplates = array
    (
        'datamanager_table' => "<label id='{element_name}_label'<!-- BEGIN required --> class='required'<!-- END required -->> </label>\n\t\t
        <span class=\"field_text\">
                {label}<!-- BEGIN required --> <span class=\"field_required_start\">*</span><!-- END required --></span>\n
                        </label>\n\t\t
        <!-- BEGIN error --><span class='field_error' style=\"color: #ff0000\">{error}</span><br /><!-- END error -->\n\t\t
            <fieldset id='{element_name}_fieldset' {attributes}>
                {element}\n\t\t
            </fieldset>
        ",
        'datamanager_table_rows' =>
        "<td >
        <!-- BEGIN required --> class='required'<!-- END required -->\n\t\t'><!-- BEGIN required -->*<!-- END required -->
        \n\t\t{element}</td>\n",
        'datamanager_table_rows_header' => "<td class=\"datamanager_table_rows_header\" >{element}</td>",
    );

   /**
    * True if we are inside a group
    * @var      boolean
    * @access   private
    */
    var $_inGroup = false;

   /**
    * Array with HTML generated for group elements
    * @var      array
    * @access   private
    */
    var $_groupElements = array();

   /**
    * Template for an element inside a group
    * @var      string
    * @access   private
    */
    var $_groupElementTemplate = '';

   /**
    * HTML that wraps around the group elements
    * @var      string
    * @access   private
    */
    var $_groupWrap = '';

    /**
     * The current group running
     * @var string
     * @access private
     */
    var $_currentGroupName = "";

    /**
     * The stack of groups
     * @var array
     * @access private
     */
    var $_currentGroups = array();

    /**
     * All group templates for running groups (i.e. prepared and ready).
     * @var array
     * @access private
     */
    var $_currentGroupTemplates = array();

   /**
    * Collected HTML of the hidden fields
    * @var      string
    * @access   private
    */
    var $_hiddenHtml = '';

   /**
    * Constructor
    *
    * @access public
    */
    /* Shouldn't this be midcom_helper_datamanager2_renderer_simple?
    function HTML_QuickForm_Renderer_Default()
    {
        $this->HTML_QuickForm_Renderer();
    } // end constructor
    */
    function __construct($namespace = '')
    {
        $this->namespace = $namespace;
        $this->_groupTemplate = $this->_orig_group_template;
        $this->HTML_QuickForm_Renderer();
    }

   /**
    * returns the HTML generated for the form
    *
    * @access public
    * @return string
    */
    function toHtml()
    {
        return $this->_html;
    }

   /**
    * Called when visiting a form, before processing any form elements
    *
    * @param    object      An HTML_QuickForm object being visited
    * @access   public
    * @return   void
    */
    function startForm(&$form)
    {
        $this->_html = '';
        $this->_hiddenHtml = '';
    }

   /**
    * Called when visiting a form, after processing all form elements
    * Adds required note, form attributes, validation javascript and form content.
    *
    * @param    object      An HTML_QuickForm object being visited
    * @access   public
    * @return   void
    */
    function finishForm(&$form)
    {
        // add a required note, if one is needed
        if (!empty($form->_required) 
            && !$form->_freezeAll)
        {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_requiredNoteTemplate);
        }
        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributes(true), $this->_formTemplate);
        if (strpos($this->_formTemplate, '{hidden}'))
        {
            $html = str_replace('{hidden}', $this->_hiddenHtml, $html);
        }
        else
        {
            $this->_html .= $this->_hiddenHtml;
        }
        $this->_html = str_replace('{content}', $this->_html, $html);
        
        // add a validation script
        if ('' != ($script = $form->getValidationScript()))
        {
            $this->_html = $script . "\n" . $this->_html;
        }
    }

   /**
    * Called when visiting a header element
    *
    * @param    object     An HTML_QuickForm_header element being visited
    * @access   public
    * @return   void
    */
    function renderHeader(&$header)
    {
        $name = $header->getName();
        if (!empty($name) 
            && isset($this->_templates[$name]))
        {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        }
        else
        {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_headerTemplate);
        }
    }

   /**
    * Helper method for renderElement
    *
    * @param    string      Element name
    * @param    mixed       Element label (if using an array of labels, you should set the appropriate template)
    * @param    boolean        Whether an element is required
    * @param    string      Error message associated with the element
    * @param    string      Element type (optional)
    * @access   private
    * @see      renderElement()
    * @return   string      Html for element
    */
    private function _prepareTemplate($name, $label, $required, $error, $type = false)
    {
        if (is_array($label))
        {
            $nameLabel = array_shift($label);
        }
        else
        {
            $nameLabel = $label;
        }

        if (isset($this->_templates[$name]))
        {
            $html = str_replace('{label}', $nameLabel, $this->_templates[$name]);
        }
        else if ($this->_inGroup)
        {
            /* rambo: I'm pretty sure this is never reached... */
            $html = str_replace('{label}', $nameLabel, $this->_defaultGroupTemplate);
        }
        else
        {
            switch ($type)
            {
                case 'dummy:group':
                    $template = $this->_orig_group_template;
                    break;
                default:
                    $template = $this->_elementTemplate;
                    break;
            }
            $html = str_replace('{label}', $nameLabel, $template);
            $html = str_replace('{namespace}', $this->namespace, $html);
        }

        if ($required)
        {
            $html = str_replace('<!-- BEGIN required -->', '', $html);
            $html = str_replace('<!-- END required -->', '', $html);
        }
        else
        {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->.*?<!-- END required -->([ \t\n\r]*)?/is", '', $html);
        }

        if (isset($error))
        {
            $html = str_replace('{error}', $error, $html);
            $html = str_replace('<!-- BEGIN error -->', '', $html);
            $html = str_replace('<!-- END error -->', '', $html);
        }
        else
        {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN error -->.*?<!-- END error -->([ \t\n\r]*)?/is", '', $html);
        }

        if (is_array($label))
        {
            foreach($label as $key => $text)
            {
                $key  = is_int($key)? $key + 2: $key;
                $html = str_replace("{label_{$key}}", $text, $html);
                $html = str_replace("<!-- BEGIN label_{$key} -->", '', $html);
                $html = str_replace("<!-- END label_{$key} -->", '', $html);
            }
        }

        $html = str_replace('{element_name}', $name , $html);
        if (strpos($html, '{label_'))
        {
            $html = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $html);
        }

        return $html;
    }

   /**
    * Renders an element Html
    * Called when visiting an element
    *
    * @param object     An HTML_QuickForm_element object being visited
    * @param boolean       Whether an element is required
    * @param string     An error message associated with an element
    * @access public
    * @return void
    */
    function renderElement(&$element, $required, $error)
    {
        if (!$this->_inGroup)
        {
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error, $element->getType());
            $this->_html .= str_replace('{element}', $element->toHtml(), $html);
        }
        else if (!empty($this->_groupElementTemplate))
        {
            $html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
            if ($required)
            {
                $html = str_replace('<!-- BEGIN required -->', '', $html);
                $html = str_replace('<!-- END required -->', '', $html);
            }
            else
            {
                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/i", '', $html);
            }
            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);
        }
        else
        {
            $this->_groupElements[] = $element->toHtml();
        }
    }

   /**
    * Renders a hidden element
    * Called when visiting a hidden element
    *
    * @param object     An HTML_QuickForm_hidden object being visited
    * @access public
    * @return void
    */
    function renderHidden(&$element)
    {
        $this->_hiddenHtml .= $element->toHtml() . "\n";
    }

   /**
    * Called when visiting a raw HTML/text pseudo-element
    *
    * @param  object     An HTML_QuickForm_html element being visited
    * @access public
    * @return void
    */
    function renderHtml(&$data)
    {
        $this->_html .= $data->toHtml();
    }

   /**
    * Called when visiting a group, before processing any group elements
    *
    * @param object     An HTML_QuickForm_group object being visited
    * @param boolean       Whether a group is required
    * @param string     An error message associated with a group
    * @access public
    * @return void
    */
    function startGroup(&$group, $required, $error)
    {
        $name = $group->getName();

        if ($this->_inGroup)
        {
            // the rest of the variables are set in the other groups run of the startGroup function.
            //$this->_currentGroupTemplates[$this->_currentGroupName]['_groupElements'] = $this->_groupElements;
        }

        $this->_currentGroupTemplates[$name] = array
        (
            // '_groupTemplate'        => $this->_prepareTemplate($name, $group->getLabel(), $required, $error),
            '_groupTemplate'        => $this->_prepareTemplate($name, $group->getLabel(), $required, $error, 'dummy:group'),
            '_groupElementTemplate' => empty($this->_groupTemplates[$name])? '': $this->_groupTemplates[$name],
            '_groupWrap'            => empty($this->_groupWraps[$name])? '': $this->_groupWraps[$name],
            '_groupElements'        => array(),
        );

        $this->_inGroup              = true;
        $this->_setGroupTemplates($name);
        $this->_currentGroupName = $name;
        $this->_currentGroups[] = $name;
    }


    /**
     * Called by start/finishGroup() to set the current template elements
     * @param group to set the templates to.
     */
    function _setGroupTemplates($name)
    {
        $this->_groupTemplate        = $this->_currentGroupTemplates[$name]['_groupTemplate'];
        $this->_groupElementTemplate = $this->_currentGroupTemplates[$name]['_groupElementTemplate'];
        $this->_groupWrap            = $this->_currentGroupTemplates[$name]['_groupWrap'];
        $this->_groupElements        = $this->_currentGroupTemplates[$name]['_groupElements'];
    }
   /**
    * Called when visiting a group, after processing all group elements
    *
    * @param    object      An HTML_QuickForm_group object being visited
    * @access   public
    * @return   void
    */
    function finishGroup(&$group)
    {
        $separator = $group->_separator;
        if (is_array($separator))
        {
            $count = count($separator);
            $html  = '';
            for ($i = 0; $i < count($this->_groupElements); $i++)
            {
                $html .= (0 == $i? '': $separator[($i - 1) % $count]) . $this->_groupElements[$i];

            }
        }
        else
        {
            if (is_null($separator))
            {
                $separator = '&nbsp;';
            }
            $html = implode((string)$separator, $this->_groupElements);
        }

        if (!empty($this->_groupWrap))
        {
            $html = str_replace('{content}', $html, $this->_groupWrap);
        }

        $template = str_replace('{attributes}', $group->getAttributes(true), $this->_groupTemplate);

        $html   = str_replace('{element}', $html, $template);

        array_pop($this->_currentGroups);
        $currentGroupId = count($this->_currentGroups) - 1;
        if ($currentGroupId > -1 )
        {
            $this->_currentGroupName = $this->_currentGroups[$currentGroupId];
            $this->_currentGroupTemplates[$this->_currentGroupName]['_groupElements'][] = $html;
            $this->_setGroupTemplates($this->_currentGroupName);
            $this->_inGroup = true;
        }
        else
        {
            $this->_currentGroupName = "";
            $this->_html .= $html;
            $this->_inGroup = false;
        }

    }

    /**
     * Sets element template
     *
     * @param       string      The HTML surrounding an element
     * @param       string      (optional) Name of the element to apply template for
     * @access      public
     * @return      void
     */
    function setElementTemplate($html, $element = null)
    {
        if (is_null($element))
        {
            $this->_elementTemplate = $html;
        }
        else
        {
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
     * @param       string      The HTML surrounding group elements
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    function setGroupTemplate($html, $group)
    {
        $this->_groupWraps[$group] = $html;
    }

    /**
     * Sets element template for elements within a group
     *
     * @param       string      The HTML surrounding an element
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    function setGroupElementTemplate($html, $group)
    {
        $this->_groupTemplates[$group] = $html;
    }

    /**
     * Sets header template
     *
     * @param       string      The HTML surrounding the header
     * @access      public
     * @return      void
     */
    function setHeaderTemplate($html)
    {
        $this->_headerTemplate = $html;
    }

    /**
     * Sets form template
     *
     * @param     string    The HTML surrounding the form tags
     * @access    public
     * @return    void
     */
    function setFormTemplate($html)
    {
        $this->_formTemplate = $html;
    }

    /**
     * Sets the note indicating required fields template
     *
     * @param       string      The HTML surrounding the required note
     * @access      public
     * @return      void
     */
    function setRequiredNoteTemplate($html)
    {
        $this->_requiredNoteTemplate = $html;
    }

    /**
     * Clears all the HTML out of the templates that surround notes, elements, etc.
     * Useful when you want to use addData() to create a completely custom form look
     *
     * @access  public
     * @return  void
     */
    function clearAllTemplates()
    {
        $this->setElementTemplate('{element}');
        $this->setFormTemplate("\n\t<form{attributes}>{content}\n\t</form>\n");
        $this->setRequiredNoteTemplate('');
        $this->_templates = array();
    }
}
?>