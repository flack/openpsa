<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: composite.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 composite widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports the composite type or any subtype thereof. Each child object will be
 * displayed using its own AJAX form inside a MidCOM style element listing: _dm2_composite_xx_header,
 * _dm2_composite_xx_item and _dm2_composite_xx_footer where xx is the value of the
 * <i>style_element_name</i>.
 *
 * <b>Available configuration options:</b>
 *
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_composite extends midcom_helper_datamanager2_widget
{
    /**
     * The initialization event handler, check type.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (! is_a($this->_type, 'midcom_helper_datamanager2_type_composite'))
        {
            debug_add("Warning, the field {$this->name} is not a composite type or subclass thereof, you cannot use the composite widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        return true;
    }

    /**
     * Constructs the child object listing.
     */
    function add_elements_to_form()
    {
        foreach ($this->_type->objects as $identifier => $object)
        {
            $this->_type->add_object_item($identifier);
        }
    }

    function sync_type_with_widget($results){}

    function freeze(){}

    function unfreeze(){}

    /**
     * Returns always false
     */
    function is_frozen()
    {
        return false;
    }
}
?>