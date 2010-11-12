<?php
/**
 * @package midcom.helper.activitystream
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for activity log objects
 *
 * @package midcom.helper.activitystream
 */
class midcom_helper_activitystream_activity_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midcom_helper_activitystream_activity';
    
    // Don't activity log or version activity stream entries
    var $_use_activitystream = false;
    var $_use_rcs = false;
    
    function __construct($id = null)
    {
        return parent::__construct($id);
    }

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
     * Map MidCOM I/O operations to Activity Streams verbs
     *
     * @see http://wiki.activitystrea.ms/Verb-Mapping
     */
    static function operation_to_verb($operation)
    {
        switch ($operation)
        {
            case MIDCOM_OPERATION_DBA_CREATE:
            case MIDCOM_OPERATION_DBA_UPDATE:
                return 'http://activitystrea.ms/schema/1.0/post';
            case MIDCOM_OPERATION_DBA_DELETE:
                return 'http://community-equity.org/schema/1.0/delete';
            case MIDCOM_OPERATION_DBA_IMPORT:
                return 'http://community-equity.org/schema/1.0/clone';
        }
    }
    
    static function generate_summary($activity, $target = null)
    {
        $actor = null;
        if ($activity->actor)
        {
            $actor = new midcom_core_user($activity->actor);
        }

        if (   !$target
            && $activity->target)
        {
            $target = $_MIDCOM->dbfactory->get_object_by_guid($activity->target);
        }
        
        $target_label = $activity->target;
        if ($target)
        {
            $reflector = new midcom_helper_reflector($target);
            $class_label = $reflector->get_class_label();
            $target_label = "{$class_label} " . $reflector->get_object_label($target);
        }

        $verb = '';
        switch ($activity->verb)
        {
            case 'http://activitystrea.ms/schema/1.0/post':
                if ($actor)
                {
                    return sprintf($_MIDCOM->i18n->get_string('%s saved %s', 'midcom.helper.activitystream'), $actor->name, $target_label);
                }
                return sprintf($_MIDCOM->i18n->get_string('%s was saved', 'midcom.helper.activitystream'), $target_label);
            case 'http://community-equity.org/schema/1.0/delete':
                if ($actor)
                {
                    return sprintf($_MIDCOM->i18n->get_string('%s deleted %s', 'midcom.helper.activitystream'), $actor->name, $target_label);
                }
                return sprintf($_MIDCOM->i18n->get_string('%s was deleted', 'midcom.helper.activitystream'), $target_label);
            case 'http://community-equity.org/schema/1.0/clone':
                if ($actor)
                {
                    return sprintf($_MIDCOM->i18n->get_string('%s cloned %s', 'midcom.helper.activitystream'), $actor->name, $target_label);
                }
                return sprintf($_MIDCOM->i18n->get_string('%s was cloned', 'midcom.helper.activitystream'), $target_label);
            default:
                // TODO: Check if the originating component can provide this
                return '';
        }
    }
    
    static function get($limit = 20, $offset = 0, $unique = false)
    {
        $qb = midcom_helper_activitystream_activity_dba::new_query_builder();
        $qb->add_order('metadata.created', 'DESC');
        $qb->set_limit($limit);
        $qb->set_offset($offset);
        
        $objects = $qb->execute();
        if (   !$unique
            || count($objects) < $limit)
        {
            return $objects;
        }
        
        $unique_objects = array();
        $duplicates = 0;
        $uniques = 0;
        foreach ($objects as $object)
        {
            if (isset($unique_objects[$object->target]))
            {
                $duplicates++;
                continue;
            }
            $unique_objects[$object->target] = $object;
            $uniques++;
        }
        
        if ($uniques == $limit)
        {
            return $unique_objects;
        }
        
        return array_merge($unique_objects, midcom_helper_activitystream_activity_dba::get($limit - $uniques, $offset + $limit, true));
    }
    
    static function get_by_user(midcom_core_user $user, $limit = 20, $offset = 0)
    {
        $actor = $user->get_storage();
        $qb = midcom_helper_activitystream_activity_dba::new_query_builder();
        $qb->add_constraint('actor', '=', $actor->id);
        $qb->add_order('metadata.created', 'DESC');
        $qb->set_limit($limit);
        $qb->set_offset($offset);
        return $qb->execute();
    }
    
    function _on_creating()
    {
        if (!$this->summary)
        {
            $this->summary = midcom_helper_activitystream_activity_dba::generate_summary($this);
        }
        
        if (   !$this->verb
            || !$this->summary
            || !$this->target)
        {
            return false;
        }
        
        return parent::_on_creating();
    }
}
?>