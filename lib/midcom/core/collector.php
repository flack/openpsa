<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM DBA level wrapper for the Midgard Collector.
 *
 * This class must be used anyplace within MidCOM instead of the real
 * midgard_collector object within the MidCOM Framework. This wrapper is
 * required for the correct operation of many MidCOM services.
 *
 * It essentially wraps the calls to {@link midcom_helper__dbfactory::new_collector()}.
 *
 * Normally you should never have to create an instance of this type directly,
 * instead use the get_new_mc() method available in the MidCOM DBA API or the
 * midcom_helper__dbfactory::new_collector() method which is still available.
 *
 * If you have to do create the instance manually however, do not forget to call the
 * {@link initialize()} function after construction, or the creation callbacks will fail.
 *
 * @package midcom
 * @todo Refactor the class to promote code reuse in the execution handlers.
 */
class midcom_core_collector extends midcom_core_query
{
    /**
     * Keep track if $this->execute has been called
     *
     * @var boolean
     */
    private $_executed = false;

    /**
     * This private helper holds the user id for ACL checks. This is set when executing
     * to avoid unnecessary overhead
     *
     * @var string
     */
    private $_user_id = false;

    /**
     * The constructor wraps the class resolution into the MidCOM DBA system.
     * Currently, Midgard requires the actual MgdSchema base classes to be used
     * when dealing with the QB, so we internally note the corresponding class
     * information to be able to do correct typecasting later.
     *
     * @param string $classname The classname which should be queried.
     */
    public function __construct($classname, $domain, $value)
    {
        static $_class_mapping_cache = array();

        $this->_real_class = $classname;

        if (isset($_class_mapping_cache[$classname]))
        {
            $mgdschemaclass = $_class_mapping_cache[$classname];
        }
        else
        {
            // Validate the class, we check for a single callback representatively only
            if (!method_exists($classname, '_on_prepare_new_collector'))
            {
                throw new midcom_error
                (
                    "Cannot create a midcom_core_collector instance for the type {$classname}: Does not seem to be a DBA class name."
                );
            }

            // Figure out the actual MgdSchema class from the decorator
            $dummy = new $classname();
            $mgdschemaclass = $dummy->__mgdschema_class_name__;
            $_class_mapping_cache[$classname] = $mgdschemaclass;
        }

        $this->_query = new midgard_collector($mgdschemaclass, $domain, $value);

        // MidCOM's collector always uses the GUID as the key for ACL purposes
        $this->_query->set_key_property('guid');
    }

    /**
     * The initialization routine executes the _on_prepare_new_collector callback on the class.
     * This cannot be done in the constructor due to the reference to $this that is used.
     */
    function initialize()
    {
        call_user_func_array(array($this->_real_class, '_on_prepare_new_collector'), array(&$this));
    }

    /**
     * This function will execute the Querybuilder and call the appropriate callbacks from the
     * class it is associated to. This way, class authors have full control over what is actually
     * returned to the application.
     *
     * The calling sequence of all event handlers of the associated class is like this:
     *
     * 1. boolean _on_prepare_exec_collector(&$this) is called before the actual query execution. Return false to
     *    abort the operation.
     *
     * @return boolean indicating success/failure
     * @see _real_execute()
     */
    function execute()
    {
        if (! call_user_func_array(array($this->_real_class, '_on_prepare_exec_collector'), array(&$this)))
        {
            debug_add('The _on_prepare_exec_collector callback returned false, so we abort now.');
            return false;
        }

        if (!$_MIDCOM->auth->admin)
        {
            $this->_user_id = $_MIDCOM->auth->acl->get_user_id();
        }

        $this->_executed = true;
        return true;
    }

    /**
     * Executes the MC
     *
     * @see midgard_collector::execute()
     */
    private function _real_execute()
    {
        // Add the limit / offsets
        if ($this->_limit)
        {
            $this->_query->set_limit($this->_limit);
        }
        if ($this->_offset)
        {
            $this->_query->set_offset($this->_offset);
        }

        $this->_add_visibility_checks();
        
        return $this->_query->execute();
    }

    /**
     * Resets some internal variables for re-execute
     */
    protected function _reset()
    {
        $this->_executed = false;
        parent::_reset();
    }

    /**
     * Runs a query where <i>limit and offset is taken into account prior to
     * execution in the core.</i>
     *
     * This is useful in cases where you can safely assume read privileges on all
     * objects, and where you would otherwise have to deal with huge resultsets.
     *
     * Be aware that this might lead to empty resultsets "in the middle" of the
     * actual full resultset when read privileges are missing.
     *
     * @see list_keys()
     */
    function list_keys_unchecked()
    {
        $this->_reset();

        $newresult = $this->_list_keys_and_check_privileges();

        if (!is_array($newresult))
        {
            return $newresult;
        }

        call_user_func_array(array($this->_real_class, '_on_process_collector_result'), array(&$newresult));

        $this->count = count($newresult);

        return $newresult;
    }

    private function _list_keys_and_check_privileges()
    {
        $this->_real_execute();
        $result = $this->_query->list_keys();
        if (!is_array($result))
        {
            return $result;
        }
        $newresult = array();
        $classname = $this->_real_class;

        foreach ($result as $object_guid => $empty_copy)
        {
            if (    $this->_user_id
                && !$_MIDCOM->auth->acl->can_do_byguid('midgard:read', $object_guid, $classname, $this->_user_id))
            {
                debug_add("Failed to load result, read privilege on {$object_guid} not granted for the current user.", MIDCOM_LOG_INFO);
                $this->denied++;
                continue;
            }

            // Check visibility
            if ($this->hide_invisible)
            {
                // TODO: Implement
            }

            // Register the GUID as loaded in this request
            $_MIDCOM->cache->content->register($object_guid);

            $newresult[$object_guid] = array();
        }
        return $newresult;
    }

    /**
     * implements midgard_collector::list_keys with ACL checking
     */
    function list_keys()
    {
        $this->_reset();
        $result = $this->_list_keys_and_check_privileges();
        if (!is_array($result))
        {
            return $result;
        }

        $size = sizeof($result);

        if ($this->_offset)
        {
            if ($this->_offset > $size)
            {
                $result = array();
                $size = 0;
            }
            else
            {
                $result = array_slice($result, $this->_offset);
                $size = $size - $this->_offset;
            }
        }

        if (   $this->_limit > 0
            && $this->_limit < $size)
        {
            $result = array_slice($result, 0, $this->_limit);
        }

        call_user_func_array(array($this->_real_class, '_on_process_collector_result'), array(&$result));

        $this->count = count($result);

        return $result;
    }

    function get_subkey($key, $property)
    {
        if (   $this->_user_id
            && !$_MIDCOM->auth->acl->can_do_byguid('midgard:read', $key, $this->_real_class, $this->_user_id))
        {
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }
        return $this->_query->get_subkey($key, $property);
    }

    function get($key)
    {
        if (   $this->_user_id
            && !$_MIDCOM->auth->acl->can_do_byguid('midgard:read', $key, $this->_real_class, $this->_user_id))
        {
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }
        return $this->_query->get($key);
    }

    function destroy()
    {
        return $this->_query->destroy();
    }

    function set_key_property($property, $value = null)
    {
        debug_add("MidCOM collector does not allow switching key properties. It is always GUID.", MIDCOM_LOG_ERROR);

        return false;
    }

    function add_value_property($property)
    {
        if (!$this->_query->add_value_property($property))
        {
            debug_add("Failed to execute add_value_property '{$property}' for {$this->_real_class}.", MIDCOM_LOG_ERROR);

            return false;
        }

        return true;
    }

    /**
     * Returns the number of elements matching the current query.
     *
     * Due to ACL checking we must first execute the full query
     *
     * @return int The number of records found by the last query.
     */
    function count()
    {
        if ($this->count == -1)
        {
            if (!$this->_executed)
            {
                $this->execute();
            }
            $this->list_keys();
        }
        return $this->count;
    }

    /**
     * This is a mapping to the real count function of the Midgard Collector. It is mainly
     * intended when speed is important over accuracy, as it bypasses access control to get a
     * fast impression of how many objects are available in a given query. It should always
     * be kept in mind that this is a preliminary number, not a final one.
     *
     * Use this function with care. The information you obtain in general is negligible, but a creative
     * mind might nevertheless be able to take advantage of it.
     *
     * @return int The number of records matching the constraints without taking access control or visibility into account.
     */
    function count_unchecked()
    {
        $this->_reset();
        if ($this->_limit)
        {
            $this->_query->set_limit($this->_limit);
        }
        if ($this->_offset)
        {
            $this->_query->set_offset($this->_offset);
        }
        return $this->_query->count();
    }
}
?>
