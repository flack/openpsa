<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Page record with framework support.
 *
 * The uplink is the up page. There are no host parents as the same page can be the
 * child of many hosts.
 *
 * @package midcom.db
 */
class midcom_db_page extends midcom_db_cachemember
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_page';

    /**
     * Returns the Parent of the Page.
     *
     * @return MidgardObject Parent object or null if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->up == 0)
        {
            return null;
        }

        try
        {
            $parent = new midcom_db_page($this->up);
        }
        catch (midcom_error $e)
        {
            debug_add("Could not load Page ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            return null;
        }

        return $parent->guid;
    }
}
?>