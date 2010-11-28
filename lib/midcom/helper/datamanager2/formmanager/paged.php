<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: paged.php 25931 2010-05-03 09:33:35Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Paged Form Manager class.
 *
 * This class uses special operations to allow for paged forms.
 *
 * The form rendering is done using the widgets and is based on HTML_QuickForm.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_formmanager_paged extends midcom_helper_datamanager2_formmanager
{
    /**
     * Initializes the Form manager with a list of types for a given schema.
     *
     * @param midcom_helper_datamanager2_schema &$schema The schema to use for processing. This
     *     variable is taken by reference.
     * @param Array &$types A list of types matching the passed schema, used as a basis for the
     *     form types. This variable is taken by reference.
     */
    function __construct(&$schema, &$types)
    {
        parent::__construct($schema, $types);
    }

    /**
     * ...
     *
     * @return boolean Indicating success
     * @access private
     */
    function _load_widgets()
    {
        $this->widgets = Array();

        foreach ($this->_schema->fields as $name => $config)
        {
            if (!parent::_load_widget($name))
            {
                return false;
            }
        }
        return true;
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
         * First, we construct the regular form, to allow for a call to process_form.
         * In process_form, we then process the page switch. There we will have to
         * reconstruct the form with the new page elements, along with all hidden
         * values. The trick here is to rebuild the form with all unseen fields added
         * as hidden elements in a way so that the reconstructed form can create
         * its widgets directly from it.
         */

        return parent::initialize($name);
    }

    /**
     * ...
     *
     * @return string One of 'editing', 'save', 'next', 'previous' and 'cancel'
     */
    function process_form()
    {
        $exitcode = parent::process_form();

        // Process next/previous

        return $exitcode;
    }
}
