<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: dbaobject.php 26507 2010-07-06 13:31:06Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM DBA baseclass for MgdSchema object decorators..
 *
 * @package midcom
 */
abstract class midcom_core_dbaobject extends midcom_baseclasses_core_object
{
    /**
     * MgdSchema object
     * 
     * @access public
     * @var mixed MgdSchema object
     */
    public $__object = null;
    
    /**
     * Metadata object
     * 
     * @acccess public
     * @var midcom_helper_metadata MidCOM metadata object
     */
    public $__metadata = null;
    
    /**
     * Should the revision control system be enabled for object updates
     * 
     * @access private
     * @var boolean
     */
    var $_use_rcs = true;
    
    /**
     * Should the Activity Log be enabled for object actions
     *
     * @access private
     * @var boolean
     */
    var $_use_activitystream = true;
    
    /**
     * Change message used for RCS and the Activity Log summary
     * 
     * @access private
     * @var string
     */
    var $_rcs_message = false;

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
     * @access public
     * @var boolean
     */
    var $allow_name_catenate = false;

    /**
     * Constructor. Creates an abstraction layer for an MgdSchema object.
     * 
     * @access public
     */
    public function __construct($id = null)
    {
        if (is_object($id))
        {
            $this->__object = $_MIDCOM->dbfactory->convert_midcom_to_midgard($id);
        }
        else
        {
            if (   is_string($id)
                && strlen($id) == 1)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Constructing ' . $this->__mgdschema_class_name__ . ' object ' . $id . ' with ID typecast to string. Changing typecast.', MIDCOM_LOG_INFO);
                debug_pop();
                $id = (int) $id;
            }
            try
            {
                $mgdschemaclass = $this->__mgdschema_class_name__;
                $this->__object = new $mgdschemaclass($id);
            }
            catch (midgard_error_exception $e)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Constructing ' . $this->__mgdschema_class_name__ . ' object ' . $id . ' failed, reason: ' . $e->getMessage(), MIDCOM_LOG_INFO);
                debug_pop();
                return;
            }
            
            //Some useful information for performance tuning
            if (   $GLOBALS['midcom_config']['log_level'] >= MIDCOM_LOG_DEBUG
                && $this->__object->guid)
            {
                static $guids = array();
                static $total = 0;

                $total++;

                //If the GUID was loaded already, write the appropriate log entry
                if (array_key_exists($this->__object->guid, $guids))
                {
                    $guids[$this->__object->guid]++;
                    $message = $this->__mgdschema_class_name__ . ' ' . $this->__object->guid;
                    $message .= ' loaded from db ' . $guids[$this->__object->guid] . ' times.';
                    $stats = 'Objects loaded (Total/Unique): ' . $total . '/' . sizeof($guids);

                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add($message);
                    debug_add($stats);
                    debug_pop();
                }
                else
                {
                   $guids[$this->__object->guid] = 1;
                }
            }
        }
          
        if (   $this->__object->guid
            && mgd_is_guid($this->__object->guid))
        {
            midcom_baseclasses_core_dbobject::post_db_load_checks($this);
        }
    }
    
    /**
     * Magic getter for object property mapping
     * 
     * @access public
     * @param string $property Name of the property
     */
    public function __get($property) 
    { 
        if (null === $this->__object)
        {
            return null;
        }

        if (   $property === 'sitegroup'
            && isset($_MIDGARD['config']['sitegroup'])
            && !$_MIDGARD['config']['sitegroup'])
        {
            // This Midgard setup doesn't support sitegroups
            return 0;
        }

        if ($property === 'metadata')
        {
            if (null === $this->__metadata)
            {
                $this->__metadata = $this->get_metadata();
            }
            return $this->__metadata;
        }

        if (   substr($property, 0, 2) === '__'
            && $property !== '__guid')
        {
            // API change safety
            if ($property === '__new_class_name__')
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Deprecated property __new_class_name__ used with object of type {$this->__mgdschema_class_name__}", MIDCOM_LOG_WARN);
                debug_pop();

                $property = '__mgdschema_class_name__';
            }

            if ($property === '__table__')
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Deprecated property __table__ used with object of type {$this->__mgdschema_class_name__}", MIDCOM_LOG_WARN);
                debug_pop();
                return null;
            }

            return $this->$property;
        }

        return $this->__object->$property;
    }
    
    /**
     * Magic setter for object property mapping
     * 
     * @access public
     * @param string $property  Name of the property
     * @param mixed $value      Property value
     */
    public function __set($property, $value) 
    {
        return $this->__object->$property = $value;
    }

    public function set_guid($guid)
    {
        return $this->__object->set_guid($guid);
    }

    /**
     * Magic isset test for object property mapping
     * 
     * @access public
     * @param string $property  Name of the property
     */
    public function __isset($property) 
    {
        return isset($this->__object->$property); 
    }
    
    /**
     * Shortcut for accessing MidCOM Query Builder
     * 
     * @return midcom_core_querybuilder The initialized instance of the query builder.
     * @see midcom_core_querybuilder
     * @static
     */
    abstract static function new_query_builder();
    
    /**
     * Shortcut for accessing MidCOM Collector
     *
     * @param string $domain The domain property of the collector instance
     * @param mixed $value Value match for the collector instance
     * @return midcom_core_collector The initialized instance of the collector.
     * @see midcom_core_collector
     * @static
     */
    abstract static function new_collector($domain, $value);

    /**
     * Stub for accessing MidCOM object cache.
     *
     * @param string $classname Which DBA are we dealing with (PHP 5.3 could figure this
                                out with late static bindings, but...)
     * @param mixed $src GUID of object (ids work but are discouraged)
     * @return mixed Reference to the object or false
     * @static
     */
    static function get_cached($src)
    {
        echo 'This has to be implemented in the DBA class itself';
        _midcom_stop_request();
    }

    /**
     * API for creating a new object
     * 
     * @access public
     * @return boolean Indicating success
     */
    public function create() 
    {
        return midcom_baseclasses_core_dbobject::create($this);
    }
    
    /**
     * API for creating an attachment for the object
     * 
     * @access public
     * @param string $name      Machine-readable name of the attachment
     * @param string $title     Human-readable title of the attachment
     * @param string $mimetype  MIME-type of the attachment
     * @return boolean Indicating success
     */
    public function create_attachment($name, $title, $mimetype) 
    {
        return midcom_baseclasses_core_dbobject::create_attachment($this, $name, $title, $mimetype);
    }
    
    /**
     * Create new privilege for the object.
     * 
     * @access public
     * @param string $privilege  Privilege name
     * @param mixed $assignee    ID or GUID of the assignee
     * @param int $value         Privilege level
     * @param string $classname  An optional class name to which a SELF privilege gets restricted to. Only valid for SELF privileges.
     * @return boolean Indicating success
     */
    public function create_new_privilege_object($privilege, $assignee = null, $value = MIDCOM_PRIVILEGE_ALLOW, $classname = '') 
    {
        return midcom_baseclasses_core_dbobject::create_new_privilege_object($this, $privilege, $assignee, $value, $classname);
    }
    
    /**
     * Delete the current object
     * 
     * @access public
     * @return boolean Indicating success
     */
    public function delete()
    {
        return midcom_baseclasses_core_dbobject::delete($this);
    }

    /**
     * Undelete object defined by a GUID
     *
     * @access static
     * @return boolean Indicating success
     */
    public static function undelete($guid)
    {
        // TODO: This will work only in PHP 5.3 thanks to late static bindingss
        return midcom_baseclasses_core_dbobject::undelete(array($guid), __CLASS__);
    }

    /**
     * Purge the current object from database
     * 
     * @access public
     * @return boolean Indicating success
     */
    public function purge()
    {
        if (!$this->__object)
        {
            return false;
        }
        return $this->__object->purge();
    }

    /**
     * Delete an attachment of the this object
     * 
     * @access public
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
     * @access public
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
     * @access public
     * @return boolean Indicating success
     */
    public function delete_tree()
    {
        return midcom_baseclasses_core_dbobject::delete_tree($this);
    }
    
    /**
     * Get the requested attachment object
     * 
     * @access public
     * @param string $name    Attachment URL name
     * @return boolean Indicating success
     */
    public function get_attachment($name)
    {
        return midcom_baseclasses_core_dbobject::get_attachment($this, $name);
    }
    public function get_attachment_qb()
    {
        return midcom_baseclasses_core_dbobject::get_attachment_qb($this);
    }
    public function get_by_guid($guid) 
    { 
        return midcom_baseclasses_core_dbobject::get_by_guid($this, $guid);
    }
    public function get_by_id($id)
    {
        return midcom_baseclasses_core_dbobject::get_by_id($this, $id);
    }
    public function get_by_path($path)
    {
        return midcom_baseclasses_core_dbobject::get_by_path($this, $path);
    }  
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
        return midcom_baseclasses_core_dbobject::get_parent($this);
    }
    public function get_parent_guid()
    { 
        return midcom_baseclasses_core_dbobject::get_parent_guid($this);
    }
    public function get_languages()
    {
        if (   isset($_MIDGARD['config']['multilang'])
            && !$_MIDGARD['config']['multilang'])
        {
            return array();
        }
        return $this->__object->get_languages();
    }
    public function get_privilege($privilege, $assignee, $classname = '')
    {
        return midcom_baseclasses_core_dbobject::get_privilege($this, $privilege, $assignee, $classname);
    }
    public function get_privileges() {
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
        $attachment = new midcom_baseclasses_database_attachment($guid);
        $_MIDCOM->serve_attachment($guid);
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
        if ($this->__object->is_locked())
        {
            return true;
        }
        return $this->__object->lock();
    }
    public function unlock()
    {
        if (!$this->__object->is_locked())
        {
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
        if ($this->__object->is_approved())
        {
            return true;
        }
        $_MIDCOM->cache->invalidate($this->guid);
        $_MIDCOM->componentloader->trigger_watches(MIDCOM_OPERATION_DBA_UPDATE, $this);
        return $this->__object->approve();
    }
    public function unapprove()
    {
        if (!$this->__object->is_approved())
        {
            return true;
        }
        $_MIDCOM->cache->invalidate($this->guid);
        $_MIDCOM->componentloader->trigger_watches(MIDCOM_OPERATION_DBA_UPDATE, $this);
        return $this->__object->unapprove();
    }
    public function get_properties()
    {
        if (!$this->__object)
        {
            $classname = $this->__mgdschema_class_name__;
            $this->__object = new $classname();
        }
        return array_keys(get_object_vars($this->__object));
    }


    public function connect($signal, $callback, $user_data = null)
    {
        $this->__object->connect($signal, $callback, $user_data);
    }
    public function emit($signal)
    {
        $this->__object->emit($signal);
    }
    public static function new_reflection_property()
    {
        // TODO: This will work only in PHP 5.3 thanks to late static binding
        $classname = $_MIDCOM->dbclassloader->get_mgdschema_class_name_for_midcom_class(__CLASS__);
        return call_user_func(array($classname, 'new_reflection_property'));
    }

    // Legacy API
    // TODO: Get rid of these
    function parameter($domain, $name)
    {
        if (func_num_args() == 2)
        {
            return $this->get_parameter($domain, $name);
        }
        else
        {
            $value = func_get_arg(2);
            if (   $value === false
                || $value === null
                || $value === '')
            {
                return $this->delete_parameter($domain, $name);
            }
            else
            {
                return $this->set_parameter($domain, $name, $value);
            }
        }
    }

    // ACL Shortcuts
    public function can_do($privilege, $user = null)
    {
        return $_MIDCOM->auth->can_do($privilege, $this->__object, $user);
    }
    public function can_user_do($privilege, $user = null)
    {
        return $_MIDCOM->auth->can_user_do($privilege, $user, $this->__midcom_class_name__);
    }
    public function require_do($privilege, $message = null)
    {
        $_MIDCOM->auth->require_do($privilege, $this->__object, $message);
    }
    public function require_user_do($privilege, $message = null)
    {
        $_MIDCOM->auth->require_user_do($privilege, $message, $this->__midcom_class_name__);
    }

    // DBA API
    public function get_class_magic_default_privileges()
    {
        return array
        (
            'EVERYONE' => array(),
            'ANONYMOUS' => array(),
            'USERS' => array()
        );
    }

    public function get_parent_guid_uncached()
    {
        $reflector = new midgard_reflection_property($this->__mgdschema_class_name__);
        $up_property = midgard_object_class::get_property_up($this->__mgdschema_class_name__);
        if (!empty($up_property))
        {
            $target_property = $reflector->get_link_target($up_property);

            /**
             * Taken out from the generated code as this will cause infinite loop in ACL resolving, using direct QB in stead
             * (when instantiating the parent ACLs will be checked in any case)
             *
            $mc = {$this->__midcom_class_name__}::new_collector($target_property, $this->{$up_property});
            */
            if (!empty($this->{$up_property}))
            {
                $mc = new midgard_collector($this->__mgdschema_class_name__, $target_property, $this->{$up_property});
                $mc->set_key_property('guid');
                $mc->execute();
                $guids = $mc->list_keys();
                if (!is_array($guids))
                {
                    unset($mc, $guids);
                    return null;
                }
                list ($parent_guid, $dummy) = each($guids);

                unset($mc, $guids, $dummy);
                return $parent_guid;
            }
        }
        $parent_property = midgard_object_class::get_property_parent($this->__mgdschema_class_name__);
        if (   !empty($parent_property)
            && $reflector->get_link_target($parent_property))
        {
            $target_property = $reflector->get_link_target($parent_property);
            $target_class = $reflector->get_link_name($parent_property);
            /**
             * Taken out from the generated code as this will cause infinite loop in ACL resolving, using direct QB in stead
             * (when instantiating the parent ACLs will be checked in any case)
             *
            $dummy_object = new {$target_class}();
            $midcom_dba_classname = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($dummy_object);
            if (empty($midcom_dba_classname))
            {
                return null;
            }
            $mc = call_user_func(array($midcom_dba_classname, 'new_collector'), array($target_property, $this->$parent_property));
            */

            if (!empty($this->{$parent_property}))
            {
                $mc = new midgard_collector($target_class, $target_property, $this->{$parent_property});
                $mc->set_key_property('guid');
                $mc->execute();
                $guids = $mc->list_keys();
                if (!is_array($guids))
                {
                    unset($mc, $guids);
                    return null;
                }
                list ($parent_guid, $dummy) = each($guids);
                unset($mc, $guids, $dummy);
                return $parent_guid;
            }
        }
        return null;
    }
    
    /**
     * Get the GUID of the object's parent. This is done by reading up or parent
     * property values, which will give us the parent's ID. Since the ID => GUID relation 
     * won't change, the corresponding GUID is then stored in an in-request static cache
     */
    public function get_parent_guid_uncached_static($object_guid, $class_name)
    {
        static $parent_mapping = array();

        $class_name = $_MIDCOM->dbclassloader->get_mgdschema_class_name_for_midcom_class($class_name);
        $reflector = new midgard_reflection_property($class_name);
        $up_property = midgard_object_class::get_property_up($class_name);
        if (!empty($up_property))
        {
            $target_property = $reflector->get_link_target($up_property);

            // Up takes precedence over parent
            $mc = new midgard_collector($class_name, 'guid', $object_guid);
            $mc->set_key_property($up_property);
            $mc->execute();
            $link_values = $mc->list_keys();

            if (!empty($link_values))
            {
                list ($link_value, $dummy) = each($link_values);
                unset($mc, $link_values, $dummy);
                if (!empty($link_value))
                {
                    if (!array_key_exists($class_name, $parent_mapping))
                    {
                        $parent_mapping[$class_name] = array();
                    }
                    if (array_key_exists($link_value, $parent_mapping[$class_name]))
                    {
                        return $parent_mapping[$class_name][$link_value];
                    }

                    $mc2 = new midgard_collector($class_name, $target_property, $link_value);
                    $mc2->set_key_property('guid');
                    $mc2->execute();
                    $guids = $mc2->list_keys();
                    if (!is_array($guids))
                    {
                        unset($mc2, $guids, $link_value);
                        $parent_mapping[$class_name][$link_value] = null;
                        return $parent_mapping[$class_name][$link_value];
                    }
                    list ($parent_guid, $dummy) = each($guids);
                    $parent_mapping[$class_name][$link_value] = $parent_guid;

                    unset($mc2, $guids, $link_value, $dummy);

                    return $parent_guid;
                }
            }
            else
            {
                unset($mc, $link_values);
            }
        }

        $parent_property = midgard_object_class::get_property_parent($class_name);
        if (   !empty($parent_property)
            && $reflector->get_link_target($parent_property))
        {
            $target_property = $reflector->get_link_target($parent_property);
            $target_class = $reflector->get_link_name($parent_property);

            $mc = new midgard_collector($class_name, 'guid', $object_guid);
            $mc->set_key_property($parent_property);
            $mc->execute();
            $link_values = $mc->list_keys();
            if (!empty($link_values))
            {
                list ($link_value, $dummy) = each($link_values);
                unset($mc, $link_values, $dummy);
                if (!empty($link_value))
                {
                    if (!array_key_exists($target_class, $parent_mapping))
                    {
                        $parent_mapping[$target_class] = array();
                    }
                    if (array_key_exists($link_value, $parent_mapping[$target_class]))
                    {
                        return $parent_mapping[$target_class][$link_value];
                    }

                    $mc2 = new midgard_collector($target_class, $target_property, $link_value);
                    $mc2->set_key_property('guid');
                    $mc2->execute();
                    $guids = $mc2->list_keys();
                    if (!is_array($guids))
                    {
                        unset($mc2, $guids, $link_value);
                        $parent_mapping[$target_class][$link_value] = null;
                        return $parent_mapping[$target_class][$link_value];
                    }
                    list ($parent_guid, $dummy) = each($guids);
                    $parent_mapping[$target_class][$link_value] = $parent_guid;

                    unset($mc2, $guids, $link_value, $dummy);

                    return $parent_guid;
                }
            }
            else
            {
                unset($mc, $link_values);
            }
        }
        // FIXME: Handle GUID linking

        return null;
    }

    public function get_dba_parent_class()
    {
        // TODO: Try to figure this out via reflection (NOTE: this must return a midcom DBA class...)
        return null;
    }

    // Event handlers
    function _on_created() {}
    function _on_creating() { return true; }
    function _on_deleted() {}
    function _on_deleting() { return true; }
    function _on_loaded() { return true; }
    function _on_prepare_exec_query_builder(&$qb) { return true; }
    function _on_prepare_new_query_builder(&$qb) {}
    function _on_process_query_result(&$result) {}
    function _on_prepare_new_collector(&$mc) {}
    function _on_prepare_exec_collector(&$mc) { return true; }
    function _on_process_collector_result(&$result) {}
    function _on_updated() {}
    function _on_updating() { return true; }
    function _on_imported() {}
    function _on_importing() { return true; }

    // Exec handlers
    public function __exec_create() { return @$this->__object->create(); }
    public function __exec_update() { return @$this->__object->update(); }
    public function __exec_delete() { return @$this->__object->delete(); }
    public function __exec_get_by_id($id) { return $this->__object->get_by_id($id); }
    public function __exec_get_by_guid($guid) { return $this->__object->get_by_guid($guid); }
    public function __exec_get_by_path($path) { return $this->__object->get_by_path($path); }

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
?>