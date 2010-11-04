<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: topic.php 24475 2009-12-16 12:05:15Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Legacy Database Abstraction Layer
 *
 * This class encapsulates a classic MidgardTopic with its original features.
 *
 * <i>Preliminary Implementation:</i>
 *
 * Be aware that this implementation is incomplete, and grows on a is-needed basis.
 *
 * @package midcom.db
 */
class midcom_db_topic extends midcom_baseclasses_database_topic
{

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

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
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
        else if ($_MIDCOM->dbfactory->is_a($topic, 'midcom_baseclasses_database_topic'))
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