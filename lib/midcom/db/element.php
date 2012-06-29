<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Style Element record with framework support.
 *
 * @package midcom.db
 */
class midcom_db_element extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_element';

    /**
     * Returns the Parent of the Element. This is the style the element is assigned to.
     *
     * @return MidgardObject Parent object or null if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->style == 0)
        {
            debug_print_r('Current element is:', $this);
            debug_add("The Style Element {$this->id} has its style member set to 0, this is a critical database inconsistency.",
                MIDCOM_LOG_INFO);
            return null;
        }

        try
        {
            $parent = new midcom_db_style($this->style);
        }
        catch (midcom_error $e)
        {
            debug_add("Could not load Style ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            return null;
        }

        return $parent->guid;
    }
}
?>