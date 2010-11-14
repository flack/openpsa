<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: querybuilder.php 26618 2010-08-23 12:00:49Z piotras $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Memcached decorator around the querybuilder object
 *
 * Use this class to connect to Memcached for selects
 *
 * @todo go through a defined api on the memcached module instead of the private object
 * @package midcom
 */
class midcom_core_querybuilder_cached
{
    /**
     * The timeout to use for this cache
     * @var int nr of seconds until object expiry
     */
    var $timeout = 3600;
    protected $key = array();
    protected $cache = null;

    function __construct ($cache = FALSE)
    {
        if ($cache === FALSE)
        {
            $this->cache = $_MIDCOM->cache->memcache;
        }
        else
        {
            $this->cache = $cache;
        }
    }

    /**
     * Recursive walk to build a key no matter the inputs.
     * Note: With large lists of inputs, the key becomes very loong...
     */
    function rec_implode($key, $val)
    {
        $this->new_key .= "{$key}_$val";
    }

    /**
     * Makes sure that all calls are caught.
     */
    function __call($name, $args)
    {
        if ($this->qb == NULL)
        {
            throw new Exception("Querybuilder not set!");
        }
        $this->new_key = "";
        array_walk_recursive($args, array($this, 'rec_implode'));
        $this->key[] = $name . $this->new_key;
        if (method_exists($this->qb, $name)) {
            return call_user_func_array(array($this->qb, $name), $args);
        }
        throw new Exception('Tried to call unknown method '.get_class($this->qb).'::'.$name);
    }

    /**
     * Executes the query and saves it to memcached
     */
    function execute()
    {
        $key = "midcom_querybuilder_cache_{$this->qb->classname}" . implode($this->key , "_");

        $return = $this->cache->get($key);
        if ($return)
        {
            return $return;
        }
        $return = $this->qb->execute();
        $this->cache->put('MISC', $key, $return, $this->timeout);
        return $return;
    }
}


/**
 * MidCOM DBA level wrapper for the Midgard Query Builder.
 *
 * This class must be used anyplace within MidCOM instead of the real
 * midgard_query_builder object within the MidCOM Framework. This wrapper is
 * required for the correct operation of many MidCOM services.
 *
 * It essentially wraps the calls to {@link midcom_helper__dbfactory::new_query_builder()}
 * and {@link midcom_helper__dbfactory::exec_query_builder()}.
 *
 * Normally you should never have to create an instance of this type directly,
 * instead use the get_new_qb() method available in the MidCOM DBA API or the
 * midcom_helper__dbfactory::new_query_builder() method which is still available.
 *
 * If you have to do create the instance manually however, do not forget to call the
 * {@link initialize()} function after construction, or the creation callbacks will fail.
 *
 * <i>Developer's Note:</i>
 *
 * Due to the limitations of the Zend engine this class does not extend the
 * QueryBuilder but proxy to it.
 *
 * @package midcom
 */
class midcom_core_querybuilder extends midcom_baseclasses_core_object
{

    /**
     * This private helper holds the type that the application expects to retrieve
     * from this instance.
     *
     * @var string
     * @access private
     */
    private $_real_class;

    /**
     * The query builder instance that is internally used.
     *
     * @var midgard_query_builder
     * @access private
     */
    private $_qb;

    /**
     * The number of groups open
     *
     * @var int
     * @access private
     */
    var $_groups = 0;

    /**
     * The number of records to return to the client at most.
     *
     * @var int
     * @access private
     */
    var $_limit = 0;

    /**
     * The offset of the first record the client wants to have available.
     *
     * @var int
     * @access private
     */
    var $_offset = 0;

    /**
     * This is an internal count which is incremented by one each time a constraint is added.
     * It is used to emit a warning if no constraints have been added to the QB during execution.
     *
     * @var int
     * @access private
     */
    var $_constraint_count = 0;

    /**
     * The number of records found by the last execute() run. This is -1 as long as no
     * query has been executed. This member is read-only.
     *
     * @var int
     */
    var $count = -1;

    /**
     * The number of objects for which access was denied.
     *
     * This is especially useful for reimplementations of functions like mgd_get_article_by_name
     * which must use the QB in the first place.
     *
     * @var int
     */
    var $denied = 0;

    /**
     * Set this element to true to hide all items which are currently invisible according
     * to the approval/scheduling settings made using Metadata. This must be set before executing
     * the query.
     *
     * Be aware, that this setting will currently not use the QB to filter the objects accordingly,
     * since there is no way yet to filter against parameters. This will mean some performance
     * impact.
     *
     */
    var $hide_invisible = true;

    /**
     * The class this qb is working on.
     * @var string classname
     */
    var $classname = null;

    var $_qb_error_result = 'UNDEFINED';

    /**
     * Which window size to use. Is calculated when executing for the first time
     */
    private $_window_size = 0;

    /**
     * When determining window sizes for offset/limit queries use this as minimum size
     */
    var $min_window_size = 10;

    /**
     * When determining window sizes for offset/limit queries use this as maximum size
     */
    var $max_window_size = 500;

    /**
     * The constructor wraps the class resolution into the MidCOM DBA system.
     * Currently, Midgard requires the actual MgdSchema base classes to be used
     * when dealing with the QB, so we internally note the corresponding class
     * information to be able to do correct typecasting later.
     *
     * @param string $classname The classname which should be queried.
     * @todo remove baseclass resolution, Midgard core can handle extended classnames correctly nowadays
     */
    function __construct($classname)
    {
        $this->classname = $classname;

        if (!class_exists($classname))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Cannot create a midcom_core_querybuilder instance for the type {$classname}: Class does not exist.");
            // This will exit.
        }

        static $_class_mapping_cache = Array();

        $this->_real_class = $classname;
        if (isset($_class_mapping_cache[$classname]))
        {
            $mgdschemaclass = $_class_mapping_cache[$classname];
        }
        else
        {
            // Validate the class, we check for a single callback representatively only
            if (!method_exists($classname, '_on_prepare_new_query_builder'))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Cannot create a midcom_core_querybuilder instance for the type {$classname}: Does not seem to be a DBA class name.");
                // This will exit.
            }

            // Figure out the actual MgdSchema class from the decorator
            $dummy = new $classname();
            $mgdschemaclass = $dummy->__mgdschema_class_name__;
            $_class_mapping_cache[$classname] = $mgdschemaclass;
        }

        $this->_qb = new midgard_query_builder($mgdschemaclass);
        /* Force read only mode when in anonymous mode */
        if (!$_MIDCOM->auth->is_valid_user())
        {
            $this->toggle_read_only(true);
        }
    }

    /**
     * The initialization routine executes the _on_prepare_new_querybuilder callback on the class.
     * This cannot be done in the constructor due to the reference to $this that is used.
     */
    function initialize()
    {
        call_user_func_array(array($this->_real_class, '_on_prepare_new_query_builder'), array(&$this));
    }

    /**
     * Executes the internal QB and filters objects based on ACLs and metadata
     *
     * @param boolean $false_on_empty_mgd_resultset used in the moving window loop to get false in stead of empty array back from this method in case the **core** QB returns empty resultset
     * @return array of objects filtered by ACL and metadata visibility (or false in case of failure)
     */
    function _execute_and_check_privileges($false_on_empty_mgd_resultset = false)
    {
        try
        {
            $result = $this->_qb->execute();
        }
        catch (Exception $e)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Query failed: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            debug_pop();
            return array();
        }

        if (!is_array($result))
        {
            $this->_qb_error_result = $result;
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Last Midgard error was: ' . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            if (isset($php_errormsg))
            {
                debug_add("Error message was: {$php_errormsg}", MIDCOM_LOG_ERROR);
            }
            debug_pop();
            return array();
        }
        if (   empty($result)
            && $false_on_empty_mgd_resultset)
        {
            return false;
        }

        // Workaround until the QB returns the correct type, refetch everything
        $newresult = array();
        $this->denied = 0;
        debug_push_class(__CLASS__, __FUNCTION__);
        foreach ($result as $key => $object)
        {
            $classname = $this->_real_class;
            $object = new $classname($object);


            // Check read privileges
            if (midcom_application::get_error() === MGD_ERR_ACCESS_DENIED)
            {
                $this->denied++;
                midcom_application::set_error(MGD_ERR_OK); // reset error-code
                continue;
            }

            if (   ! $object
                || ! is_object($object)
                || ! $object->guid)
            {
                debug_add("Could not create a MidCOM DBA instance of the {$this->_real_class} ID {$object->id}. See debug level log for details.",
                    MIDCOM_LOG_INFO);
                continue;
            }

            // Check visibility
            if ($this->hide_invisible)
            {
                if (!is_object($object->metadata))
                {
                    debug_add("Could not create a MidCOM metadata instance for {$this->_real_class} ID {$object->id}, assuming an invisible object", MIDCOM_LOG_INFO);
                    continue;

                }
                if (! $object->metadata->is_object_visible_onsite())
                {
                    debug_add("The {$this->_real_class} ID {$object->id} is hidden by metadata.", MIDCOM_LOG_INFO);
                    continue;
                }
            }

            $newresult[] = $object;
        }
        //debug_add('Returning ' . count($newresult) . ' items');
        debug_pop();

        return $newresult;
    }

    /**
     * Resets some internal variables for re-execute
     */
    function _reset()
    {
        $this->_qb_error_result = 'UNDEFINED';
        $this->count = -1;
        $this->denied = 0;
    }

    /**
     * This function will execute the Querybuilder and call the appropriate callbacks from the
     * class it is associated to. This way, class authors have full control over what is actually
     * returned to the application.
     *
     * The calling sequence of all event handlers of the associated class is like this:
     *
     * 1. boolean _on_prepare_exec_query_builder(&$this) is called before the actual query execution. Return false to
     *    abort the operation.
     * 2. The query is executed.
     * 3. void _on_process_query_result(&$result) is called after the successful execution of the query. You
     *    may remove any unwanted entries from the resultset at this point.
     *
     * @return Array The result of the query builder or null on any error. Note, that empty resultsets
     *     will return an empty array.
     */
    function execute_windowed()
    {
        $this->_reset();

        if (! call_user_func_array(array($this->_real_class, '_on_prepare_exec_query_builder'), array(&$this)))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('The _on_prepare_exec_query_builder callback returned false, so we abort now.');
            debug_pop();
            return null;
        }

        if ($this->_constraint_count == 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('This Query Builder instance has no constraints (set loglevel to debug to see stack trace)', MIDCOM_LOG_WARN);
            debug_print_function_stack('We were called from here:');
            debug_pop();
        }

        if (   empty($this->_limit)
            && empty($this->_offset))
        {
            // No point to do windowing
            $newresult = $this->_execute_and_check_privileges();
            if (!is_array($newresult))
            {
                return $newresult;
            }
        }
        else
        {
            //debug_push_class(__CLASS__, __FUNCTION__);
            $newresult = array();
            // Must be copies
            $limit = $this->_limit;
            $offset = $this->_offset;
            $i = 0;
            $this->_set_limit_offset_window($i);

            while (($resultset = $this->_execute_and_check_privileges(true)) !== false)
            {
                //debug_add("Iteration loop #{$i}");
                if ($this->_qb_error_result !== 'UNDEFINED')
                {
                    // QB failed in above method TODO: better catch
                    /*
                    debug_add('_execute_and_check_privileges caught QB error, returning that now', MIDCOM_LOG_WARN);
                    debug_pop();
                    */
                    return $this->_qb_error_result;
                }

                $size = sizeof($resultset);

                if ($offset >= $size)
                {
                    // We still have offset left to skip
                    $offset -= $size;
                    $this->_set_limit_offset_window(++$i);
                    continue;
                }
                else if ($offset)
                {
                    $resultset = array_slice($resultset, $offset);
                    $size = $size - $offset;
                }

                if ($limit >= $size)
                {
                    $limit -= $size;
                }
                else if ($limit > 0)
                {
                    // We have reached the limit
                    $resultset = array_slice($resultset, 0, $limit);
                    $newresult = array_merge($newresult, $resultset);
                    break;
                }

                $newresult = array_merge($newresult, $resultset);

                $this->_set_limit_offset_window(++$i);
            }
        }

        call_user_func_array(array($this->_real_class, '_on_process_query_result'), array(&$newresult));

        $this->count = count($newresult);

        //debug_pop();
        return $newresult;
    }


    function _set_limit_offset_window($iteration)
    {
        /*
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Called for iteration #{$iteration}");
        */
        if (!$this->_window_size)
        {
            // Try to be smart about the window size
            switch (true)
            {
                case (   empty($this->_offset)
                      && $this->_limit):
                    // Get limited number from start (I supposed generally less than 50% will be unreadable)
                    debug_add('offset empty');
                    $this->_window_size = round($this->_limit * 1.5);
                    break;
                case (   empty($this->_limit)
                      && $this->_offset):
                    // Get rest from offset
                    /* TODO: Somehow factor in that if we have huge number of objects and relatively small offset we want to increase window size
                    $full_object_count = $this->_qb->count();
                    */
                    $this->_window_size = round($this->_offset * 2);
                case (   $this->_offset > $this->_limit):
                    // Offset is greater than limit, basically this is almost the same problem as above
                    $this->_window_size = round($this->_offset * 2);
                    break;
                case (   $this->_limit > $this->_offset):
                    // Limit is greater than offset, this is probably similar to getting limited number from beginning
                    $this->_window_size = round($this->_limit * 2);
                    break;
                case ($this->_limit == $this->_offset):
                    $this->_window_size = round($this->_offset * 2);
                    break;
            }

            if ($this->_window_size > $this->max_window_size)
            {
                $this->_window_size = $this->max_window_size;
            }
            if ($this->_window_size < $this->min_window_size)
            {
                $this->_window_size = $this->min_window_size;
            }
        }

        //debug_add("Got window size {$this->_window_size}");
        $offset = $iteration * $this->_window_size;
        if ($offset)
        {
            //debug_add("Setting offset to {$offset}");
            $this->_qb->set_offset($offset);
        }
        //debug_add("Setting limit to {$window_size}");
        $this->_qb->set_limit($this->_window_size);
        //debug_pop();
    }

    function _check_groups()
    {
        while ($this->_groups > 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Ending unterminated QB group', MIDCOM_LOG_INFO);
            debug_pop();
            $this->end_group();
        }
    }

    function execute()
    {
        $this->_check_groups();

        return $this->execute_windowed();
        //return $this->execute_notwindowed();
    }

    /**
     * This function will execute the Querybuilder and call the appropriate callbacks from the
     * class it is associated to. This way, class authors have full control over what is actually
     * returned to the application.
     *
     * The calling sequence of all event handlers of the associated class is like this:
     *
     * 1. boolean _on_prepare_exec_query_builder(&$this) is called before the actual query execution. Return false to
     *    abort the operation.
     * 2. The query is executed.
     * 3. void _on_process_query_result(&$result) is called after the successful execution of the query. You
     *    may remove any unwanted entries from the resultset at this point.
     *
     * @return Array The result of the query builder or null on any error. Note, that empty resultsets
     *     will return an empty array.
     */
    function execute_notwindowed()
    {
        $this->_reset();
        if (! call_user_func_array(array($this->_real_class, '_on_prepare_exec_query_builder'), array(&$this)))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('The _on_prepare_exec_query_builder callback returned false, so we abort now.');
            debug_pop();
            return null;
        }

        if ($this->_constraint_count == 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('This Query Builder instance has no constraints, see debug log for stacktrace', MIDCOM_LOG_WARN);
            debug_print_function_stack('We were called from here:');
            debug_pop();
        }

        $result = $this->_execute_and_check_privileges();
        if (!is_array($result))
        {
            return $result;
        }

        // Workaround until the QB returns the correct type, refetch everything
        $newresult = Array();
        $classname = $this->_real_class;
        $limit = $this->_limit;
        $offset = $this->_offset;
        $this->denied = 0;

        foreach ($result as $key => $object)
        {
            if (   $this->_limit > 0
                && $limit == 0)
            {
                break;
            }

            // We need to skip this one, because we are outside the offset.
            if (   $this->_offset > 0
                && $offset > 0)
            {
                $offset--;
                continue;
            }

            $newresult[] = $object;

            if ($this->_limit > 0)
            {
                $limit--;
            }
        }

        call_user_func_array(array($this->_real_class, '_on_process_query_result'), array(&$newresult));

        $this->count = count($newresult);

        return $newresult;
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
     * @see execute()
     */
    function execute_unchecked()
    {
        $this->_check_groups();

        $this->_reset();

        if (! call_user_func_array(array($this->_real_class, '_on_prepare_exec_query_builder'), array(&$this)))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('The _on_prepare_exec_query_builder callback returned false, so we abort now.');
            debug_pop();
            return null;
        }

        if ($this->_constraint_count == 0)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('This Query Builder instance has no constraints, see debug level log for stacktrace', MIDCOM_LOG_WARN);
            debug_print_function_stack('We were called from here:');
            debug_pop();
        }

        // Add the limit / offsets
        if ($this->_limit)
        {
            // ML bug workaround, get bit above limit and trim down later
            $this->_qb->set_limit($this->_limit+5);
        }
        if ($this->_offset)
        {
            $this->_qb->set_offset($this->_offset);
        }

        $newresult = $this->_execute_and_check_privileges();
        if (!is_array($newresult))
        {
            return $newresult;
        }

        if ($this->_limit)
        {
            while(count($newresult) > $this->_limit)
            {
                array_pop($newresult);
            }
        }

        call_user_func_array(array($this->_real_class, '_on_process_query_result'), array(&$newresult));

        $this->count = count($newresult);

        return $newresult;
    }

    /**
     * Get result by its index
     *
     * @access public
     * @param int $key      Requested index in result set
     * @param string $mode  Execution mode: normal (default), unchecked, notwindowed
     * @return mixed        False on failure (key does not exist), object given to constructor on success
     */
    function get_result($key, $mode = null)
    {
        switch ($mode)
        {
            case 'unchecked':
                $results = $this->execute_unchecked();
                break;

            case 'notwindowed':
                $results = $this->execute_notwindowed();
                break;

            default:
                $results = $this->execute();
        }

        if (!isset($results[$key]))
        {
            return false;
        }

        return $results[$key];
    }

    /**
     * Add a constraint to the query builder.
     *
     * @param string $field The name of the MgdSchema property to query against.
     * @param string $operator The operator to use for the constraint, currently supported are
     *     <, <=, =, <>, >=, >, LIKE. LIKE uses the percent sign ('%') as a
     *     wildcard character.
     * @param mixed $value The value to compare against. It should be of the same type then the
     *     queried property.
     * @return boolean Indicating success.
     */
    function add_constraint($field, $operator, $value)
    {
        if (   $field == 'sitegroup'
            && isset($_MIDGARD['config']['sitegroup'])
            && !$_MIDGARD['config']['sitegroup'])
        {
            // This Midgard setup doesn't support sitegroups
            return false;
        }

        $this->_reset();
        // Add check against null values, Core MC is too stupid to get this right.
        if ($value === null)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("QueryBuilder: Cannot add constraint on field '{$field}' with null value.",MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }
        if (! $this->_qb->add_constraint($field, $operator, $value))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to execute add_constraint.", MIDCOM_LOG_ERROR);
            debug_add("Class = '{$this->_real_class}, Field = '{$field}', Operator = '{$operator}'");
            debug_print_r('Value:', $value);
            debug_pop();

            return false;
        }

        $this->_constraint_count++;
        return true;
    }

    /**
     * Add an ordering constraint to the query builder.
     *
     * This function has extended functionality against the pure Midgard Query Builder:
     * It can deal with legacy Midgard 'reverse $field' style sorting orders. All calls
     * to sort with such fields when using the default ordering will enforce descending
     * ordering over the default.
     *
     * @param string $field The name of the MgdSchema property to query against.
     * @param string $ordering One of 'ASC' or 'DESC' indicating ascending or descending
     *     ordering. The default is 'ASC'.
     * @return boolean Indicating success.
     */
    function add_order($field, $ordering = null)
    {
        /**
         * NOTE: So see also collector.php when making changes here
         */
        if (! $field)
        {
            // This is a workaround for a situation the 1.7 Midgard core cannot intercept for
            // some reason unknown to me. Should be removed once 1.7.x is far enough in the
            // past.

            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('QueryBuilder: Cannot order by a null field name.', MIDCOM_LOG_INFO);
            debug_pop();

            return false;
        }

        if (   $field == 'sitegroup'
            && isset($_MIDGARD['config']['sitegroup'])
            && !$_MIDGARD['config']['sitegroup'])
        {
            // This Midgard setup doesn't support sitegroups
            return false;
        }

        if ($ordering === null)
        {
            if (substr($field, 0, 8) == 'reverse ')
            {
                $result = $this->_qb->add_order(substr($field, 8), 'DESC');
            }
            else
            {
                $result = $this->_qb->add_order($field);
            }
        }
        else
        {
            $result = $this->_qb->add_order($field, $ordering);
        }

        if (! $result)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to execute add_order for column '{$field}', midgard error: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            debug_pop();
        }

        return $result;
    }

    /**
     * Creates a new logical group within the query. They are set in parentheses in the final
     * SQL and will thus be evaluated with precedence over the normal out-of-group constraints.
     *
     * While the call lets you decide whether all constraints within the group are AND'ed or OR'ed,
     * only OR constraints make logically sense in this context, which is why this proxy function
     * sets 'OR' as the default operator.
     *
     * @param string $operator One of 'OR' or 'AND' denoting the logical operation with which all
     *     constraints in the group are concatenated.
     */
    function begin_group($operator = 'OR')
    {
        $this->_groups++;
        try
        {
            @$this->_qb->begin_group($operator);
        }
        catch (Exception $e)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to execute begin_group {$operator}, Midgard Exception: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            debug_pop();
            $this->_groups--;
        }
    }

    /**
     * Ends a group previously started with begin_group().
     */
    function end_group()
    {
        $this->_groups--;

        try
        {
            @$this->_qb->end_group();
        }
        catch (Exception $e)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to execute end_group, Midgard Exception: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            debug_pop();
        }
    }

    /**
     * Limits the resultset to contain at most the specified number of records.
     * Set the limit to zero to retrieve all available records.
     *
     * This implementation overrides the original QB implementation for the implementation
     * of ACL restrictions.
     *
     * @param int $count The maximum number of records in the resultset.
     */
    function set_limit($limit)
    {
        $this->_reset();
        $this->_limit = $limit;
    }

    /**
     * Sets the offset of the first record to retrieve. This is a zero based index,
     * so if you want to retrieve from the very first record, the correct offset would
     * be zero, not one.
     *
     * This implementation overrides the original QB implementation for the implementation
     * of ACL restrictions.
     *
     * @param int $offset The record number to start with.
     */
    function set_offset($offset)
    {
        $this->_reset();
        $this->_offset = $offset;
    }

    /**
     * Include deleted objects (metadata.deleted is TRUE) in query results.
     *
     * Note: this may cause all kinds of weird behavior with the DBA helpers
     */
    function include_deleted()
    {
        $this->_reset();
        $this->_qb->include_deleted();
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
        $this->_check_groups();

        if ($this->count == -1)
        {
            $this->execute();
        }
        return $this->count;
    }

    /**
     * This is a mapping to the real count function of the Midgard Query Builder.
     *
     * It is mainly intended when speed is important over accuracy, as it bypasses
     * access control to get a fast impression of how many objects are available
     * in a given query. It should always be kept in mind that this is a
     * preliminary number, not a final one.
     *
     * Use this function with care. The information you obtain in general is negligible, but a creative
     * mind might nevertheless be able to take advantage of it.
     *
     * @return int The number of records matching the constraints without taking access control or visibility into account.
     */
    function count_unchecked()
    {
        $this->_check_groups();

        if ($this->_limit)
        {
            $this->_qb->set_limit($this->_limit);
        }
        if ($this->_offset)
        {
            $this->_qb->set_offset($this->_offset);
        }
        return $this->_qb->count();
    }

    /**
     * Sets read-only mode for underlying midgard_query_builder instance.
     * If $toggle is true, all objects returned with execute method have read-only properties,
     * which can not be set, and all intances are created with better performance.
     * This method is dedicated for resultsets which are not meant to be updated or edited.
     *
     * If underlying midgard_query_buidler doesn't provide read-only toggle, this method does nothing.
     *
     * @param bool $toggle enables or disables query builder read-only mode.
     */
    function toggle_read_only($toggle = false)
    {
        if (method_exists($this->_qb, "toggle_read_only"))
        {
            $this->_qb->toggle_read_only($toggle);
        }
    }
}

?>
