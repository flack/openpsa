<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\events\dbaevent;

/**
 * MidCOM DBA baseclass for MgdSchema object decorators.
 *
 * @property midcom_helper_metadata $metadata the object's metadata
 * @package midcom
 */
abstract class midcom_core_dbaobject
{
    /**
     * MgdSchema object
     *
     * @var midgard\portable\api\object MgdSchema object
     */
    public $__object = null;

    /**
     * Metadata object
     *
     * @var midcom_helper_metadata MidCOM metadata object
     */
    private $__metadata = null;

    /**
     * Should the revision control system be enabled for object updates
     *
     * @var boolean
     */
    public $_use_rcs = true;

    /**
     * Should the Activity Log be enabled for object actions
     *
     * @var boolean
     */
    public $_use_activitystream = true;

    /**
     * Change message used for RCS and the Activity Log summary
     *
     * @var string
     */
    private $_rcs_message = false;

    /**
     * Verb to use for Activity Log. Should be an URL conforming to activitystrea.ms specification.
     * If left blank then this will come from the DBA action performed (update, create)
     *
     * @access private
     * @var string
     */
    var $_activitystream_verb = null;

    /**
     * Should it be allowed to automatically generate unique name in case of clash
     *
     * @see http://trac.midgard-project.org/ticket/809
     * @var boolean
     */
    public $allow_name_catenate = false;

    /**
     * May contain a list of dbaclass => field entries. When deleting an object,
     * these dependent objects are automatically deleted beforehand
     *
     * @var array
     */
    public $autodelete_dependents = [];

    /**
     * Constructor. Creates an abstraction layer for an MgdSchema object.
     */
    public function __construct($id = null)
    {
        if (is_object($id)) {
            $this->__object = midcom::get()->dbfactory->convert_midcom_to_midgard($id);
        } else {
            if (   is_string($id)
                && strlen($id) == 1) {
                debug_add('Constructing ' . $this->__mgdschema_class_name__ . ' object ' . $id . ' with ID typecast to string. Changing typecast.', MIDCOM_LOG_INFO);
                $id = (int) $id;
            }
            if (   is_int($id)
                && $id < 1) {
                throw new midcom_error($id . ' is not a valid database ID');
            }

            try {
                $mgdschemaclass = $this->__mgdschema_class_name__;
                $this->__object = new $mgdschemaclass($id);
            } catch (midgard_error_exception $e) {
                debug_add('Constructing ' . $this->__mgdschema_class_name__ . ' object ' . $id . ' failed, reason: ' . $e->getMessage(), MIDCOM_LOG_WARN);
                throw new midcom_error_midgard($e, $id);
            }

            //Some useful information for performance tuning
            if (   midcom::get()->config->get('log_level') >= MIDCOM_LOG_DEBUG
                && $this->__object->guid) {
                static $guids = [];
                static $total = 0;

                $total++;

                //If the GUID was loaded already, write the appropriate log entry
                if (array_key_exists($this->__object->guid, $guids)) {
                    $guids[$this->__object->guid]++;
                    $message = $this->__mgdschema_class_name__ . ' ' . $this->__object->guid;
                    $message .= ' loaded from db ' . $guids[$this->__object->guid] . ' times.';
                    $stats = 'Objects loaded (Total/Unique): ' . $total . '/' . sizeof($guids);

                    debug_add($message);
                    debug_add($stats);
                } else {
                    $guids[$this->__object->guid] = 1;
                }
            }
        }

        if (   $this->__object->guid
            && mgd_is_guid($this->__object->guid)) {
            midcom_baseclasses_core_dbobject::post_db_load_checks($this);
        }
    }

    /**
     * Magic getter for object property mapping
     *
     * @param string $property Name of the property
     */
    public function __get($property)
    {
        if (null === $this->__object) {
            return null;
        }

        if ($property === 'metadata') {
            if (null === $this->__metadata) {
                $this->__metadata = $this->get_metadata();
            }
            return $this->__metadata;
        }

        return $this->__object->$property;
    }

    /**
     * Magic setter for object property mapping
     *
     * @param string $property  Name of the property
     * @param mixed $value      Property value
     */
    public function __set($property, $value)
    {
        return $this->__object->$property = $value;
    }

    /**
     * Shortcut for accessing MidCOM Query Builder
     *
     * @return midcom_core_querybuilder The initialized instance of the query builder.
     */
    public static function new_query_builder()
    {
        return midcom::get()->dbfactory->new_query_builder(get_called_class());
    }

    /**
     * Shortcut for accessing MidCOM Collector
     *
     * @param string $domain The domain property of the collector instance
     * @param mixed $value Value match for the collector instance
     * @return midcom_core_collector The initialized instance of the collector.
     */
    public static function new_collector($domain = null, $value = null)
    {
        return midcom::get()->dbfactory->new_collector(get_called_class(), $domain, $value);
    }

    /**
     * Shortcut for accessing MidCOM object cache.
     *
     * @param mixed $src GUID of object (ids work but are discouraged)
     * @return self Reference to the object or false
     */
    public static function &get_cached($src)
    {
        return midcom::get()->dbfactory->get_cached(get_called_class(), $src);
    }

    public function set_guid($guid)
    {
        return $this->__object->set_guid($guid);
    }

    /**
     * Magic isset test for object property mapping
     *
     * @param string $property  Name of the property
     */
    public function __isset($property)
    {
        return isset($this->__object->$property);
    }

    /**
     * API for creating a new object
     *
     * @return boolean Indicating success
     */
    public function create()
    {
        return midcom_baseclasses_core_dbobject::create($this);
    }

    /**
     * API for creating an attachment for the object
     *
     * @param string $name      Machine-readable name of the attachment
     * @param string $title     Human-readable title of the attachment
     * @param string $mimetype  MIME-type of the attachment
     * @return midcom_db_attachment The created attachment or false on failure.
     */
    public function create_attachment($name, $title, $mimetype)
    {
        return midcom_baseclasses_core_dbobject::create_attachment($this, $name, $title, $mimetype);
    }

    /**
     * Create new privilege for the object.
     *
     * @param string $privilege  Privilege name
     * @param mixed $assignee    ID or GUID of the assignee
     * @param int $value         Privilege level
     * @param string $classname  An optional class name to which a SELF privilege gets restricted to. Only valid for SELF privileges.
     * @return midcom_core_privilege The newly created privilege record or false on failure.
     */
    public function create_new_privilege_object($privilege, $assignee = null, $value = MIDCOM_PRIVILEGE_ALLOW, $classname = '')
    {
        return midcom_baseclasses_core_dbobject::create_new_privilege_object($this, $privilege, $assignee, $value, $classname);
    }

    /**
     * Delete the current object
     *
     * @return boolean Indicating success
     */
    public function delete()
    {
        return midcom_baseclasses_core_dbobject::delete($this);
    }

    /**
     * Undelete object defined by a GUID
     *
     * @return boolean Indicating success
     */
    public static function undelete($guid)
    {
        return midcom_baseclasses_core_dbobject::undelete([$guid], get_called_class());
    }

    /**
     * Purge the current object from database
     *
     * @return boolean Indicating success
     */
    public function purge()
    {
        if (!$this->__object) {
            return false;
        }
        return $this->__object->purge();
    }

    /**
     * Delete an attachment of the this object
     *
     * @param string $name     Name of the attachment
     * @return boolean Indicating success
     */
    public function delete_attachment($name)
    {
        return midcom_baseclasses_core_dbobject::delete_attachment($this, $name);
    }

    /**
     * Delete a parameter
     *
     * @param string $domain    Parameter domain
     * @param string $name      Parameter name
     * @return boolean Indicating success
     */
    public function delete_parameter($domain, $name)
    {
        return midcom_baseclasses_core_dbobject::delete_parameter($this, $domain, $name);
    }

    /**
     * Delete the current object tree, starting from this object
     *
     * @return boolean Indicating success
     */
    public function delete_tree()
    {
        return midcom_baseclasses_core_dbobject::delete_tree($this);
    }

    /**
     * Get the requested attachment object
     *
     * @param string $name    Attachment URL name
     * @return midcom_db_attachment The attachment found, or false on failure.
     */
    public function get_attachment($name)
    {
        return midcom_baseclasses_core_dbobject::get_attachment($this, $name);
    }

    /**
     * @return midcom_core_querybuilder The initialized instance of the query builder or false on failure.
     */
    public function get_attachment_qb()
    {
        return midcom_baseclasses_core_dbobject::get_attachment_qb($this);
    }

    /**
     *
     * @param string $guid
     * @return boolean Indicating success
     */
    public function get_by_guid($guid)
    {
        return midcom_baseclasses_core_dbobject::get_by_guid($this, $guid);
    }

    /**
     *
     * @param integer $id
     * @return boolean Indicating success
     */
    public function get_by_id($id)
    {
        return midcom_baseclasses_core_dbobject::get_by_id($this, $id);
    }

    /**
     *
     * @param string $path
     * @return boolean Indicating success
     */
    public function get_by_path($path)
    {
        return midcom_baseclasses_core_dbobject::get_by_path($this, $path);
    }

    /**
     *
     * @return midcom_helper_metadata
     */
    public function get_metadata()
    {
        return midcom_helper_metadata::retrieve($this);
    }
    public function get_parameter($domain, $name)
    {
        return midcom_baseclasses_core_dbobject::get_parameter($this, $domain, $name);
    }
    public function get_parent()
    {
        return midcom::get()->dbfactory->get_parent($this);
    }
    public function get_privilege($privilege, $assignee, $classname = '')
    {
        return midcom_baseclasses_core_dbobject::get_privilege($this, $privilege, $assignee, $classname);
    }
    public function get_privileges()
    {
        return midcom_baseclasses_core_dbobject::get_privileges($this);
    }
    public function is_in_parent_tree($root, $id)
    {
        return $this->__object->is_in_parent_tree($root, $id);
    }
    public function is_in_tree($root, $id)
    {
        return $this->__object->is_in_tree($root, $id);
    }
    public function has_dependents()
    {
        return $this->__object->has_dependents();
    }
    public function list_children($class_name)
    {
        // FIXME: ACL checks
        return $this->__object->list_children($class_name);
    }
    public function parent()
    {
        return $this->__object->parent();
    }
    public function is_object_visible_onsite()
    {
        return midcom_baseclasses_core_dbobject::is_object_visible_onsite($this);
    }
    public function has_attachments()
    {
        return $this->__object->has_attachments();
    }
    public function list_attachments()
    {
        return midcom_baseclasses_core_dbobject::list_attachments($this);
    }
    public function find_attachments($constraints)
    {
        return $this->__object->find_attachments($constraints);
    }
    public function delete_attachments($constraints)
    {
        return $this->__object->delete_attachments($constraints);
    }
    public function purge_attachments($constraints)
    {
        return $this->__object->purge_attachments($constraints);
    }
    public static function serve_attachment($guid)
    {
        midcom::get()->serve_attachment(new midcom_db_attachment($guid));
    }
    public function has_parameters()
    {
        return $this->__object->has_parameters();
    }
    public function list_parameters($domain = null)
    {
        return midcom_baseclasses_core_dbobject::list_parameters($this, $domain);
    }
    public function find_parameters($constraints)
    {
        return $this->__object->find_parameters($constraints);
    }
    public function delete_parameters($constraints)
    {
        return $this->__object->delete_parameters($constraints);
    }
    public function purge_parameters($constraints)
    {
        return $this->__object->purge_parameters($constraints);
    }
    public function refresh()
    {
        return midcom_baseclasses_core_dbobject::refresh($this);
    }
    public function set_parameter($domain, $name, $value)
    {
        return midcom_baseclasses_core_dbobject::set_parameter($this, $domain, $name, $value);
    }
    public function set_privilege($privilege, $assignee = null, $value = MIDCOM_PRIVILEGE_ALLOW, $classname = '')
    {
        return midcom_baseclasses_core_dbobject::set_privilege($this, $privilege, $assignee, $value, $classname);
    }
    public function unset_privilege($privilege, $assignee = null, $classname = '')
    {
        return midcom_baseclasses_core_dbobject::unset_privilege($this, $privilege, $assignee, $classname);
    }
    public function unset_all_privileges()
    {
        return midcom_baseclasses_core_dbobject::unset_all_privileges($this);
    }
    public function update()
    {
        return midcom_baseclasses_core_dbobject::update($this);
    }
    public function is_locked()
    {
        return $this->__object->is_locked();
    }
    public function lock()
    {
        if ($this->__object->is_locked()) {
            return true;
        }
        return $this->__object->lock();
    }
    public function unlock()
    {
        if (!$this->__object->is_locked()) {
            return true;
        }
        return $this->__object->unlock();
    }
    public function is_approved()
    {
        return $this->__object->is_approved();
    }
    public function approve()
    {
        if ($this->__object->is_approved()) {
            return true;
        }
        if ($this->__object->approve()) {
            midcom::get()->dispatcher->dispatch(dbaevent::APPROVE, new dbaevent($this));
            return true;
        }
        return false;
    }

    public function unapprove()
    {
        if (!$this->__object->is_approved()) {
            return true;
        }
        if ($this->__object->unapprove()) {
            midcom::get()->dispatcher->dispatch(dbaevent::UNAPPROVE, new dbaevent($this));
            return true;
        }
        return false;
    }

    public function get_properties()
    {
        return midcom_helper_reflector::get_object_fieldnames($this);
    }

    public static function new_reflection_property()
    {
        $classname = midcom::get()->dbclassloader->get_mgdschema_class_name_for_midcom_class(get_called_class());
        return call_user_func([$classname, 'new_reflection_property']);
    }

    // ACL Shortcuts
    public function can_do($privilege, $user = null)
    {
        return midcom::get()->auth->can_do($privilege, $this, $user);
    }
    public function can_user_do($privilege, $user = null)
    {
        return midcom::get()->auth->can_user_do($privilege, $user, $this->__midcom_class_name__);
    }
    public function require_do($privilege, $message = null)
    {
        midcom::get()->auth->require_do($privilege, $this, $message);
    }
    public function require_user_do($privilege, $message = null)
    {
        midcom::get()->auth->require_user_do($privilege, $message, $this->__midcom_class_name__);
    }

    // DBA API
    public function get_class_magic_default_privileges()
    {
        return [
            'EVERYONE' => [],
            'ANONYMOUS' => [],
            'USERS' => []
        ];
    }

    private function _delete_dependents()
    {
        foreach ($this->autodelete_dependents as $classname => $link_property) {
            $qb = midcom::get()->dbfactory->new_query_builder($classname);
            $qb->add_constraint($link_property, '=', $this->id);
            $results = $qb->execute();
            foreach ($results as $result) {
                if (!$result->delete()) {
                    debug_add('Could not delete dependent ' . $classname . ' #' . $result->id . ', aborting', MIDCOM_LOG_WARN);
                    return false;
                }
            }
        }
        return true;
    }

    // Event handlers
    public function _on_created()
    {
    }
    public function _on_creating()
    {
        return true;
    }
    public function _on_deleted()
    {
    }
    public function _on_deleting()
    {
        return $this->_delete_dependents();
    }
    public function _on_loaded()
    {
    }
    public static function _on_prepare_exec_query_builder(&$qb)
    {
        return true;
    }
    public static function _on_prepare_new_query_builder(&$qb)
    {
    }
    public static function _on_process_query_result(&$result)
    {
    }
    public static function _on_prepare_new_collector(&$mc)
    {
    }
    public static function _on_prepare_exec_collector(&$mc)
    {
        return true;
    }
    public static function _on_process_collector_result(&$result)
    {
    }
    public function _on_updated()
    {
    }
    public function _on_updating()
    {
        return true;
    }
    public function _on_imported()
    {
    }
    public function _on_importing()
    {
        return true;
    }

    // functions related to the RCS service.
    public function disable_rcs()
    {
        $this->_use_rcs = false;
    }
    public function enable_rcs()
    {
        $this->_use_rcs  = true;
    }
    public function set_rcs_message($msg)
    {
        $this->_rcs_message = $msg;
    }
    public function get_rcs_message()
    {
        return $this->_rcs_message;
    }
}
