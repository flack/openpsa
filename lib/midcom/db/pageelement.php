<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard PageElement record with framework support.
 *
 * The uplink is the owning page.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the get_by*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable through the statically callable new_query_builder() DBA methods.
 *
 * @see midcom_services_dbclassloader
 * @package midcom.db
 */
class midcom_db_pageelement extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_pageelement';

    /**
     * Returns the Parent of the Page.
     *
     * @return MidgardObject Parent object or null if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->page == 0)
        {
            return null;
        }

        try
        {
            $parent = new midcom_db_page($this->page);
        }
        catch (midcom_error $e)
        {
            debug_add("Could not load Page ID {$this->page} from the database, aborting.",
                MIDCOM_LOG_INFO);
            return null;
        }

        return $parent->guid;
    }
}
?>