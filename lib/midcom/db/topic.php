<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Topic record with framework support.
 *
 * @package midcom.db
 */
class midcom_db_topic extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_topic';

    public function get_label()
    {
        if ($this->extra)
        {
            return $this->extra;
        }
        else if ($this->name)
        {
            return $this->name;
        }
        else
        {
            return '#' . $this->id;
        }
    }

    /**
     * Returns the Parent of the Topic, which is always another topic.
     *
     * @return MidgardObject Parent topic (null if we have a root topic).
     */
    public function get_parent_guid_uncached()
    {
        return self::_get_parent_guid_uncached_static_topic($this->up);
    }

    /**
     * Statically callable method to get parent guid when object guid is given
     *
     * Uses midgard_collector to avoid unnecessary full object loads
     *
     * @param string $guid GUID of topic to get the parent for
     */
    public static function get_parent_guid_uncached_static($guid, $classname = __CLASS_)
    {
        if (empty($guid))
        {
            return null;
        }
        $mc_topic = self::new_collector('guid', $guid);
        $mc_topic_keys = $mc_topic->get_values('up');
        if (empty($mc_topic_keys))
        {
            // Error
            return null;
        }

        $parent_id = array_shift($mc_topic_keys);
        if ($parent_id == 0)
        {
            // Root-level topic
            return null;
        }
        return self::_get_parent_guid_uncached_static_topic($parent_id);
    }

    /**
     * Get topic guid statically
     *
     * used by get_parent_guid_uncached_static
     *
     * @param int $parent_id id of topic to get the guid for
     */
    private static function _get_parent_guid_uncached_static_topic($parent_id)
    {
        if (!$parent_id)
        {
            return null;
        }
        $mc_parent = midcom_db_topic::new_collector('id', $parent_id);
        $mc_parent->execute();
        $mc_parent_keys = $mc_parent->list_keys();
        if (empty($mc_parent_keys))
        {
            // Error
            return null;
        }

        $parent_guid = key($mc_parent_keys);
        if ($parent_guid === false)
        {
            return null;
        }
        return $parent_guid;
    }

    function get_dba_parent_class()
    {
        return 'midcom_db_topic';
    }
}
?>