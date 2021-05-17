<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\events\dbaevent;
use midgard\portable\api\error\exception as mgd_exception;
use midcom\dba\parameters;
use midcom\dba\attachments;
use midcom\dba\privileges;
use midgard\portable\api\mgdobject;
use midcom\dba\softdelete;

/**
 * MidCOM DBA baseclass for MgdSchema object decorators.
 *
 * @property integer $id Local non-replication-safe database identifier
 * @property string $guid
 * @property midcom_helper_metadata $metadata the object's metadata
 * @package midcom
 */
abstract class midcom_core_dbaobject
{
    use parameters, attachments, privileges;

    /**
     * @var string
     */
    public $__midcom_class_name__;

    /**
     * @var string
     */
    public $__mgdschema_class_name__;

    /**
     * MgdSchema object
     *
     * @var mgdobject
     */
    public $__object;

    /**
     * @var midcom_helper_metadata
     */
    private $__metadata;

    /**
     * Should the revision control system be enabled for object updates
     *
     * @var boolean
     */
    public $_use_rcs = true;

    /**
     * Change message used for RCS and the Activity Log summary
     *
     * @var string
     */
    private $_rcs_message = false;

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
     * Creates an abstraction layer for an MgdSchema object.
     */
    public function __construct($id = null)
    {
        if (is_object($id)) {
            if (midcom::get()->dbclassloader->is_midcom_db_object($id)) {
                $id = $id->__object;
            }
            $this->set_object($id);
        } else {
            if (   is_int($id)
                && $id < 1) {
                throw new midcom_error($id . ' is not a valid database ID');
            }

            try {
                $mgdschemaclass = $this->__mgdschema_class_name__;
                $this->set_object(new $mgdschemaclass($id));
            } catch (mgd_exception $e) {
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
                    $stats = 'Objects loaded (Total/Unique): ' . $total . '/' . count($guids);

                    debug_add($message);
                    debug_add($stats);
                } else {
                    $guids[$this->__object->guid] = 1;
                }
            }
        }

        if ($this->__object->guid) {
            midcom_baseclasses_core_dbobject::post_db_load_checks($this);
        }
    }

    private function set_object(mgdobject $object)
    {
        $this->__object = $object;
    }

    /**
     * Magic getter for object property mapping
     *
     * @param string $property Name of the property
     */
    public function __get($property)
    {
        if ($property === 'metadata') {
            if (null === $this->__metadata) {
                $this->__metadata = new midcom_helper_metadata($this);
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
        $this->__object->$property = $value;
    }

    /**
     * Shortcut for accessing MidCOM Query Builder
     */
    public static function new_query_builder() : midcom_core_querybuilder
    {
        return midcom::get()->dbfactory->new_query_builder(get_called_class());
    }

    /**
     * Shortcut for accessing MidCOM Collector
     *
     * @param string $domain The domain property of the collector instance
     * @param mixed $value Value match for the collector instance
     */
    public static function new_collector(string $domain = null, $value = null) : midcom_core_collector
    {
        return midcom::get()->dbfactory->new_collector(get_called_class(), $domain, $value);
    }

    /**
     * Shortcut for accessing MidCOM object cache.
     *
     * @param mixed $src GUID of object (ids work but are discouraged)
     * @return static Reference to the object
     */
    public static function get_cached($src) : self
    {
        return midcom::get()->dbfactory->get_cached(get_called_class(), $src);
    }

    public function set_guid(string $guid)
    {
        $this->__object->set_guid($guid);
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
     */
    public function create() : bool
    {
        return midcom_baseclasses_core_dbobject::create($this);
    }

    /**
     * Delete the current object
     */
    public function delete() : bool
    {
        return midcom_baseclasses_core_dbobject::delete($this);
    }

    /**
     * Undelete object defined by a GUID
     *
     * @return int Size of undeleted objects
     */
    public static function undelete(string $guid) : int
    {
        return softdelete::undelete([$guid]);
    }

    /**
     * Purge the current object from database
     */
    public function purge() : bool
    {
        return $this->__object->purge();
    }

    /**
     * Delete the current object tree, starting from this object
     */
    public function delete_tree() : bool
    {
        return midcom_baseclasses_core_dbobject::delete_tree($this);
    }

    public function get_by_guid(string $guid) : bool
    {
        return midcom_baseclasses_core_dbobject::get_by_guid($this, $guid);
    }

    public function get_by_id(int $id) : bool
    {
        return midcom_baseclasses_core_dbobject::get_by_id($this, $id);
    }

    public function get_by_path(string $path) : bool
    {
        return midcom_baseclasses_core_dbobject::get_by_path($this, $path);
    }

    public function get_parent() : ?self
    {
        return midcom::get()->dbfactory->get_parent($this);
    }
    public function has_dependents() : bool
    {
        return $this->__object->has_dependents();
    }
    public function has_attachments() : bool
    {
        return $this->__object->has_attachments();
    }
    public function find_attachments(array $constraints)
    {
        return $this->__object->find_attachments($constraints);
    }
    public function delete_attachments(array $constraints)
    {
        return $this->__object->delete_attachments($constraints);
    }
    public function purge_attachments(array $constraints)
    {
        return $this->__object->purge_attachments($constraints);
    }
    public function has_parameters() : bool
    {
        return $this->__object->has_parameters();
    }
    public function find_parameters(array $constraints)
    {
        return $this->__object->find_parameters($constraints);
    }
    public function delete_parameters(array $constraints)
    {
        return $this->__object->delete_parameters($constraints);
    }
    public function purge_parameters(array $constraints)
    {
        return $this->__object->purge_parameters($constraints);
    }
    public function refresh() : bool
    {
        return midcom_baseclasses_core_dbobject::refresh($this);
    }
    public function update() : bool
    {
        return midcom_baseclasses_core_dbobject::update($this);
    }
    public function is_locked() : bool
    {
        return $this->__object->is_locked();
    }
    public function lock() : bool
    {
        if ($this->__object->is_locked()) {
            return true;
        }
        return $this->__object->lock();
    }
    public function unlock() : bool
    {
        if (!$this->__object->is_locked()) {
            return true;
        }
        return $this->__object->unlock();
    }
    public function is_approved() : bool
    {
        return $this->__object->is_approved();
    }
    public function approve() : bool
    {
        if ($this->__object->is_approved()) {
            return true;
        }
        if ($this->__object->approve()) {
            midcom::get()->dispatcher->dispatch(new dbaevent($this), dbaevent::APPROVE);
            return true;
        }
        return false;
    }

    public function unapprove() : bool
    {
        if (!$this->__object->is_approved()) {
            return true;
        }
        if ($this->__object->unapprove()) {
            midcom::get()->dispatcher->dispatch(new dbaevent($this), dbaevent::UNAPPROVE);
            return true;
        }
        return false;
    }

    public function get_properties() : array
    {
        return midcom_helper_reflector::get_object_fieldnames($this);
    }

    // ACL Shortcuts
    public function can_do(string $privilege, $user = null) : bool
    {
        return midcom::get()->auth->can_do($privilege, $this, $user);
    }
    public function can_user_do(string $privilege, $user = null) : bool
    {
        return midcom::get()->auth->can_user_do($privilege, $user, $this->__midcom_class_name__);
    }
    public function require_do(string $privilege, string $message = null)
    {
        midcom::get()->auth->require_do($privilege, $this, $message);
    }
    public function require_user_do(string $privilege, string $message = null)
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

    private function _delete_dependents() : bool
    {
        foreach ($this->autodelete_dependents as $classname => $link_property) {
            if (!class_exists($classname)) {
                continue;
            }
            $qb = midcom::get()->dbfactory->new_query_builder($classname);
            $qb->add_constraint($link_property, '=', $this->id);
            foreach ($qb->execute() as $result) {
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
    public function _on_creating() : bool
    {
        return true;
    }
    public function _on_deleted()
    {
    }
    public function _on_deleting() : bool
    {
        return $this->_delete_dependents();
    }
    public function _on_loaded()
    {
    }
    public static function _on_execute(midcom_core_query $query) : bool
    {
        return true;
    }
    public static function _on_process_query_result(array &$result)
    {
    }
    public static function _on_process_collector_result(array &$result)
    {
    }
    public function _on_updated()
    {
    }
    public function _on_updating() : bool
    {
        return true;
    }
    public function _on_imported()
    {
    }
    public function _on_importing() : bool
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
        $this->_use_rcs = true;
    }
    public function set_rcs_message(string $msg)
    {
        $this->_rcs_message = $msg;
    }
    public function get_rcs_message() : string
    {
        return $this->_rcs_message;
    }
}
