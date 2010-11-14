<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: topic.php 24475 2009-12-16 12:05:15Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Topic record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the GetBy*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable as midcom_application::dbfactory.
 *
 * @package midcom.db
 * @see midcom_services_dbclassloader
 */
class midcom_db_topic extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_topic';

    /**
     * The default constructor will create an empty object. Optionally, you can pass
     * an object ID or GUID to the object which will then initialize the object with
     * the corresponding DB instance.
     *
     * @param mixed $id A valid object ID or GUID, omit for an empty object.
     */
    function __construct($id = null)
    {
        parent::__construct($id);
    }

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
     * Returns the Parent of the Topic, which is always another topic.
     *
     * @return MidgardObject Parent topic (null if we have a root topic).
     */
    function get_parent_guid_uncached()
    {
        return midcom_db_topic::_get_parent_guid_uncached_static_topic($this->up);
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
        $mc_topic = midcom_db_topic::new_collector('guid', $guid);
        $mc_topic->add_value_property('up');
        if (!$mc_topic->execute())
        {
            // Error
            return null;
        }
        $mc_topic_keys = $mc_topic->list_keys();
        list ($key, $copy) = each ($mc_topic_keys);
        $parent_id = (int) $mc_topic->get_subkey($key, 'up');
        if ($parent_id == 0)
        {
            // Root-level topic
            return null;
        }
        $mc_parent = midcom_db_topic::new_collector('id', $parent_id);
        $mc_parent->add_value_property('guid');
        if (!$mc_parent->execute())
        {
            // ErrorA
            return null;
        }
        $mc_parent_keys = $mc_parent->list_keys();
        $parent_guids = array_keys($mc_parent_keys);
        if (count($parent_guids) == 0)
        {
            return null;
        }

        $parent_guid = $parent_guids[0];
        if ($parent_guid === false)
        {
            return null;
        }
        return $parent_guid;
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
        if (!$parent_id)
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
        $parent_guids = array_keys($mc_parent_keys);
        if (count($parent_guids) == 0)
        {
            return null;
        }

        $parent_guid = $parent_guids[0];
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

    /**
     * Lists all articles in the corresponding topic. All reply articles are filtered
     * by default to match original Midgard behavior.
     *
     * @param string $sort A legacy sorting order string.
     * @return Array A list of matching objects.
     * @see midcom_core_querybuilder
     * @see get_list_articles_qb()
     */
    function list_articles($sort = null)
    {
        if ($this->id == 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Cannot query the articles of a non-persistent topic (id==0).', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $qb = $this->get_list_articles_qb();
        $qb->add_constraint('up', '=', 0);
        if ($sort !== null)
        {
            $qb->add_order($sort);
        }
        return $qb->execute();
    }

    /**
     * Returns a query builder suitable to list articles within a topic.
     *
     * @return A query builder prepared to list articles of a topic.
     * @see midcom_core_querybuilder
     */
    function get_list_articles_qb()
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $this->id);
        return $qb;
    }

    /**
     * Checks whether the topic is part of the subtree of a given topic. A topic
     * is always in his own tree, so $this->is_in_tree($this) will be true.
     *
     * This wraps http://www.midgard-project.org/documentation/mgdschema-method-is_in_tree/
     *
     * @param mixed $topic Either the ID, GUID or object instance of the base topic.
     * @return boolean True if Member.
     */
    function is_in_tree($topic)
    {
        if (mgd_is_guid($topic))
        {
            if ($topic->guid == $this->guid)
            {
                return true;
            }
            $object = new midcom_db_topic($topic);
            if (! $object)
            {
                return false;
            }
            $root = $object->id;
        }
        else if (is_numeric($topic))
        {
            if ($topic == $this->id)
            {
                return true;
            }
            $root = $topic;
        }
        else if ($_MIDCOM->dbfactory->is_a($topic, 'midcom_db_topic'))
        {
            if ($topic->id == $this->id)
            {
                return true;
            }
            $root = $topic->id;
        }
        else
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Invalid argument, expecting either ID, GUID or DBA topic instance', MIDCOM_LOG_INFO);
            debug_pop();
            return false;
        }

        return parent::is_in_tree($root, $this->id);

    }

}


?>