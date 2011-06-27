<?php
/**
 * @package midcom.helper.activitystream
 * @author The Midgard Project, http://www.midgard-project.org
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
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midcom_helper_activitystream_activity';

    // Don't activity log or version activity stream entries
    var $_use_activitystream = false;
    var $_use_rcs = false;

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
        if (   !$target
            && $activity->target)
        {
            try
            {
                $target = $_MIDCOM->dbfactory->get_object_by_guid($activity->target);
                $reflector = new midcom_helper_reflector($target);
                $class_label = $reflector->get_class_label();
                $target_label = "{$class_label} " . $reflector->get_object_label($target);
            }
            catch (midcom_error $e)
            {
                $target_label = $activity->target;
            }
        }

        switch ($activity->verb)
        {
            case 'http://activitystrea.ms/schema/1.0/post':
                $verb = 'saved';
                break;
            case 'http://community-equity.org/schema/1.0/delete':
                $verb = 'deleted';
                break;
            case 'http://community-equity.org/schema/1.0/clone':
                $verb = 'cloned';
                break;
            default:
                // TODO: Check if the originating component can provide this
                return '';
        }
        try
        {
            $actor = new midcom_core_user($activity->actor);
            return sprintf($_MIDCOM->i18n->get_string('%s ' . $verb . ' %s', 'midcom.helper.activitystream'), $actor->name, $target_label);
        }
        catch (midcom_error $e)
        {
            return sprintf($_MIDCOM->i18n->get_string('%s was ' . $verb, 'midcom.helper.activitystream'), $target_label);
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

    public function _on_creating()
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

        return true;
    }
}
?>