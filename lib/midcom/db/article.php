<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: article.php 24475 2009-12-16 12:05:15Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Article record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the GetBy*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable as midcom_application::dbfactory.
 *
 * <i>Automatic updates:</i>
 *
 * - The system automatically resets invalid $author members, as they would break
 *   mgd_list_*article* style queries. The member is set to the ID of the current
 *   user or, if that one is not accessible, to 1, which is the Midgard Administrator
 *   user ID.
 *
 * @see midcom_services_dbclassloader
 * @package midcom.db
 */
class midcom_db_article extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_article';

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     * 
     * @static
     */
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    
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
            return midcom_db_article::_get_parent_guid_uncached_static_article($this->up);
        }
        return midcom_db_article::_get_parent_guid_uncached_static_topic($this->topic);
    }

    /**
     * Statically callable method to get parent guid when object guid is given
     * 
     * Uses midgard_collector to avoid unnecessary full object loads
     *
     * @param guid $guid guid of topic to get the parent for
     */
    function get_parent_guid_uncached_static($guid)
    {
        if (empty($guid))
        {
            return null;
        }
        $mc_article = midcom_db_article::new_collector('guid', $guid);
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
            return midcom_db_article::_get_parent_guid_uncached_static_article($up);
        }
        $topic = $mc_article->get_subkey($key, 'topic');
        if ($topic === false)
        {
            // error
            return null;
        }
        return midcom_db_article::_get_parent_guid_uncached_static_topic($topic);
    }

    /**
     * Get topic guid statically
     *
     * used by get_parent_guid_uncached_static
     *
     * @param id $parent_id id of topic to get the guid for
     */
    function _get_parent_guid_uncached_static_topic($parent_id)
    {
        if (empty($parent_id))
        {
            return null;
        }
        $mc_parent = midcom_db_topic::new_collector('id', $parent_id);
        $mc_parent->add_value_property('guid');
        if (!$mc_parent->execute())
        {
            // Error
            return null;
        }
        $mc_parent_keys = $mc_parent->list_keys();
        list ($key, $copy) = each ($mc_parent_keys);
        $parent_guid = $mc_parent->get_subkey($key, 'guid');
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
     * @param id $parent_id id of topic to get the guid for
     */
    function _get_parent_guid_uncached_static_article($parent_id)
    {
        if (empty($parent_id))
        {
            return null;
        }
        $mc_parent = midcom_db_article::new_collector('id', $parent_id);
        $mc_parent->add_value_property('guid');
        if (!$mc_parent->execute())
        {
            // Error
            return null;
        }
        $mc_parent_keys = $mc_parent->list_keys();
        list ($key, $copy) = each ($mc_parent_keys);
        $parent_guid = $mc_parent->get_subkey($key, 'guid');
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

    /**
     * Pre-Creation hook, which validates the $author field for correctness.
     *
     * @return boolean Indicating success.
     */
    function _on_creating()
    {
        return true;
    }

    /**
     * Pre-Update hook, which validates the $author field for correctness.
     *
     * @return boolean Indicating success.
     */
    function _on_updating()
    {
        return true;
    }

}
?>