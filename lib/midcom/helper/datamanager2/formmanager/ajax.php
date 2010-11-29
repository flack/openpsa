<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: ajax.php 25931 2010-05-03 09:33:35Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 ajax Form Manager class.
 *
 * This class uses special operations to allow for ajax forms.
 *
 * The form rendering is done using the widgets and is based on HTML_QuickForm.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_formmanager_ajax extends midcom_helper_datamanager2_formmanager
{
    /**
     * Latest exit code
     */
    var $_exitcode = null;

    /**
     * Initializes the Form manager with a list of types for a given schema.
     *
     * @param midcom_helper_datamanager2_schema &$schema The schema to use for processing. This
     *     variable is taken by reference.
     * @param Array &$types A list of types matching the passed schema, used as a basis for the
     *     form types. This variable is taken by reference.
     */
    public function __construct(&$schema, &$types, $state = 'edit')
    {
        parent::__construct($schema, $types, $state);
    }

    /**
     * This function will create all widget objects for the current schema. It will load class
     * files where necessary (using require_once), and then create a set of instances
     * based on the schema.
     *
     * @param string $name The name of the field for which we should load the widget.
     * @return boolean Indicating success
     * @access protected
     */
    function _load_widget($name)
    {
        return parent::_load_widget($name, true);
    }

    /**
     * ...
     *
     * @param mixed $name The name of the form. This defaults to the name of the currently active component, which should
     *     suffice in most cases.
     * @return boolean Indicating success.
     */
    function initialize($name = null)
    {
        /* The idea:
         *
         * First, we construct the regular foorm, to allow for a call to process_form.
         * In process_form, we then process the page switch. There we will have to
         * reconstruct the formn with the new page elements, along with all hidden
         * values. The trick here is to rebuild the form with all unseen fields added
         * as hidden elements in a way so that the reconstructed form can create
         * its widgets directly from it.
         */

        return parent::initialize($name);
    }

    /**
     * This call will render the form in AJAX-readable fashion
     */
    function display_form($form_identifier = 'ajax')
    {
        $_MIDCOM->cache->content->content_type('text/xml');
        $_MIDCOM->header('Content-type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";

        $exitcode = '';
        if (!is_null($this->_exitcode))
        {
            $exitcode = " exitcode=\"{$this->_exitcode}\"";
        }

        echo "<form id=\"{$form_identifier}\"{$exitcode} editable=\"true\">\n";

        if (count($this->form->_errors) > 0)
        {
            foreach ($this->form->_errors as $field => $error)
            {
                echo "<error field=\"{$field}\">{$error}</error>\n";
            }
        }

        foreach ($this->widgets as $name => $copy)
        {
            // TODO: Add support for other datatypes as we go
            switch (get_class($this->_types[$name]))
            {
                case 'midcom_helper_datamanager2_type_text':
                case 'midcom_helper_datamanager2_type_select':
                case 'midcom_helper_datamanager2_type_date':
                case 'midcom_helper_datamanager2_type_number':
                case 'midcom_helper_datamanager2_type_boolean':
                case 'midcom_helper_datamanager2_type_tags':
                    $element = $this->form->getElement($name);
                    if (method_exists($element, 'toHtml'))
                    {
                        echo "<field name=\"{$name}\"><![CDATA[\n";
                        echo $element->toHtml();
                        echo "]]></field>\n";
                    }
                    break;
            }
        }
        echo "</form>\n";
    }

    /**
     * This call will render the contents in AJAX-readable fashion
     */
    function display_view($form_identifier = 'ajax', $new_form_identifier = null)
    {
        $_MIDCOM->cache->content->content_type('text/xml');
        $_MIDCOM->header('Content-type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";

        $exitcode = '';
        if (!is_null($this->_exitcode))
        {
            $exitcode = " exitcode=\"{$this->_exitcode}\"";
        }

        $new_identifier = '';
        if (!is_null($new_form_identifier))
        {
            $new_identifier = " new_identifier=\"{$new_form_identifier}\"";
        }

        echo "<form id=\"{$form_identifier}\"{$exitcode}{$new_identifier}>\n";

        if (count($this->form->_errors) > 0)
        {
            foreach ($this->form->_errors as $field => $error)
            {
                echo "<error field=\"{$field}\">{$error}</error>\n";
            }
        }

        foreach ($this->widgets as $name => $widget)
        {
            // TODO: Add support for other datatypes as we go
            switch (get_class($this->_types[$name]))
            {
                case 'midcom_helper_datamanager2_type_text':
                case 'midcom_helper_datamanager2_type_select':
                case 'midcom_helper_datamanager2_type_date':
                case 'midcom_helper_datamanager2_type_number':
                case 'midcom_helper_datamanager2_type_boolean':
                case 'midcom_helper_datamanager2_type_tags':
                    echo "<field name=\"{$name}\"><![CDATA[\n";
                    echo $widget->render_content();
                    echo "]]></field>\n";
                    break;
            }
        }
        echo "</form>\n";
    }

    /**
     * ...
     *
     * @return string One of 'editing', 'save', 'next', 'previous' and 'cancel'
     */
    function process_form()
    {
        $this->_exitcode = parent::process_form(true);

        // Process next/previous

        return $this->_exitcode;
    }
}
