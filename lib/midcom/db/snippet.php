<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Snippet record with framework support.
 *
 * The uplink is the owning snippetdir.
 *
 * @package midcom.db
 */
class midcom_db_snippet extends midcom_db_cachemember
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_snippet';

    public function __set($property, $value)
    {
        if ($property == 'up')
        {
            $property = self::get_parent_fieldname();
        }
        return parent::__set($property, $value);
    }

    public function __get($property)
    {
        if ($property == 'up')
        {
            $property = self::get_parent_fieldname();
        }
        return parent::__get($property);
    }

    /**
     * Compat workaround for schema change between mgd1 and mgd2
     *
     * @return string 'up' under Midgard1, otherwise 'snippetdir'
     */
    public static function get_parent_fieldname()
    {
        if (extension_loaded('midgard'))
        {
            return 'up';
        }
        return 'snippetdir';
    }

    /**
     * Returns the Parent of the Snippet.
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
            $parent = new midcom_db_snippetdir($this->up);
        }
        catch (midcom_error $e)
        {
            debug_add("Could not load Snippetdir ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            return null;
        }

        return $parent->guid;
    }

    public function get_icon()
    {
        return 'script.png';
    }
}
?>
