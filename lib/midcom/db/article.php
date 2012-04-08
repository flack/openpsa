<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Article record with framework support.
 *
 * All QueryBuilder operations need to be done by the factory class
 * obtainable as midcom_application::dbfactory.
 *
 * @see midcom_services_dbclassloader
 * @package midcom.db
 */
class midcom_db_article extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_article';

    /**
     * Returns the Parent of the Article. This can either be another article if we have
     * a reply article, or a topic otherwise.
     *
     * @return MidgardObject Parent Article or topic.
     */
    function get_parent_guid_uncached()
    {
        if (   isset($this->up)
            && $this->up != 0)
        {
            return self::_get_parent_guid_uncached_static_article($this->up);
        }
        return self::_get_parent_guid_uncached_static_topic($this->topic);
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
        $mc_article = self::new_collector('guid', $guid);
        $mc_article->add_value_property('up');
        $mc_article->add_value_property('topic');
        if (!$mc_article->execute())
        {
            // Error
            return null;
        }
        $mc_article_keys = $mc_article->list_keys();
        list ($key, $copy) = each ($mc_article_keys);
        $up = $mc_article->get_subkey($key, 'up');
        if ($up === false)
        {
            // error
            return null;
        }
        if (!empty($up))
        {
            return self::_get_parent_guid_uncached_static_article($up);
        }
        $topic = $mc_article->get_subkey($key, 'topic');
        if ($topic === false)
        {
            // error
            return null;
        }
        return self::_get_parent_guid_uncached_static_topic($topic);
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
        if (empty($parent_id))
        {
            return null;
        }
        $mc_parent = midcom_db_topic::new_collector('id', $parent_id);
        if (!$mc_parent->execute())
        {
            // Error
            return null;
        }
        $mc_parent_keys = $mc_parent->list_keys();
        $parent_guid = key($mc_parent_keys);
        if ($parent_guid === false)
        {
            // Error
            return null;
        }
        return $parent_guid;
    }

    /**
     * Get article guid statically
     *
     * used by get_parent_guid_uncached_static
     *
     * @param int $parent_id id of topic to get the guid for
     */
    private static function _get_parent_guid_uncached_static_article($parent_id)
    {
        if (empty($parent_id))
        {
            return null;
        }
        $mc_parent = midcom_db_article::new_collector('id', $parent_id);
        if (!$mc_parent->execute())
        {
            // Error
            return null;
        }
        $mc_parent_keys = $mc_parent->list_keys();
        $parent_guid = key($mc_parent_keys);
        if ($parent_guid === false)
        {
            // Error
            return null;
        }
        return $parent_guid;
    }

    function get_dba_parent_class()
    {
        if (   isset($this->up)
            && $this->up != 0)
        {
            return 'midcom_db_article';
        }
        return 'midcom_db_topic';
    }
}
?>