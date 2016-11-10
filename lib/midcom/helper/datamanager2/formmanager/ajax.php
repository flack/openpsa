<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
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
     * This function will create all widget objects for the current schema. It will load class
     * files where necessary (using require_once), and then create a set of instances
     * based on the schema.
     *
     * @param string $name The name of the field for which we should load the widget.
     */
    protected function _load_widget($name, $initialize_dependencies = true)
    {
        parent::_load_widget($name, true);
    }

    /**
     * This call will render the form in AJAX-readable fashion
     */
    function display_form($form_identifier = 'ajax')
    {
        midcom::get()->cache->content->content_type('text/xml');
        midcom::get()->header('Content-type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";

        $exitcode = '';
        if (!is_null($this->_exitcode)) {
            $exitcode = " exitcode=\"{$this->_exitcode}\"";
        }

        echo "<form id=\"{$form_identifier}\"{$exitcode} editable=\"true\">\n";

        if (count($this->form->_errors) > 0) {
            foreach ($this->form->_errors as $field => $error) {
                echo "<error field=\"{$field}\">{$error}</error>\n";
            }
        }

        foreach (array_keys($this->widgets) as $name) {
            // TODO: Add support for other datatypes as we go
            switch (get_class($this->_types[$name])) {
                case 'midcom_helper_datamanager2_type_text':
                case 'midcom_helper_datamanager2_type_select':
                case 'midcom_helper_datamanager2_type_date':
                case 'midcom_helper_datamanager2_type_number':
                case 'midcom_helper_datamanager2_type_boolean':
                case 'midcom_helper_datamanager2_type_tags':
                    $element = $this->form->getElement($name);
                    if ($element->isFrozen()) {
                        continue;
                    }
                    if (method_exists($element, 'toHtml')) {
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
        midcom::get()->cache->content->content_type('text/xml');
        midcom::get()->header('Content-type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";

        $exitcode = '';
        if (!is_null($this->_exitcode)) {
            $exitcode = " exitcode=\"{$this->_exitcode}\"";
        }

        $new_identifier = '';
        if (!is_null($new_form_identifier)) {
            $new_identifier = " new_identifier=\"{$new_form_identifier}\"";
        }

        echo "<form id=\"{$form_identifier}\"{$exitcode}{$new_identifier}>\n";

        if (count($this->form->_errors) > 0) {
            foreach ($this->form->_errors as $field => $error) {
                echo "<error field=\"{$field}\">{$error}</error>\n";
            }
        }

        foreach ($this->widgets as $name => $widget) {
            // TODO: Add support for other datatypes as we go
            switch (get_class($this->_types[$name])) {
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
    function process_form($ajax_mode = true)
    {
        $this->_exitcode = parent::process_form(true);

        // Process next/previous

        return $this->_exitcode;
    }
}
