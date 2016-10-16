<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM DBA level wrapper for the Midgard Query Builder.
 *
 * This class must be used anyplace within MidCOM instead of the real
 * midgard_query_builder object within the MidCOM Framework. This wrapper is
 * required for the correct operation of many MidCOM services.
 *
 * It essentially wraps the calls to {@link midcom_helper__dbfactory::new_query_builder()}.
 *
 * Normally you should never have to create an instance of this type directly,
 * instead use the get_new_qb() method available in the MidCOM DBA API or the
 * midcom_helper__dbfactory::new_query_builder() method which is still available.
 *
 * If you have to do create the instance manually however, do not forget to call the
 * {@link initialize()} function after construction, or the creation callbacks will fail.
 *
 * @package midcom
 */
class midcom_core_querybuilder extends midcom_core_query
{
    /**
     * Which window size to use. Is calculated when executing for the first time
     */
    private $_window_size = 0;

    /**
     * When determining window sizes for offset/limit queries use this as minimum size
     */
    private $min_window_size = 10;

    /**
     * When determining window sizes for offset/limit queries use this as maximum size
     */
    private $max_window_size = 500;

    /**
     * @param string $classname The classname which should be queried.
     */
    public function __construct($classname)
    {
        $mgdschemaclass = $this->_convert_class($classname);

        $this->_query = new midgard_query_builder($mgdschemaclass);
    }

    /**
     * The initialization routine executes the _on_prepare_new_querybuilder callback on the class.
     */
    function initialize()
    {
        call_user_func_array(array($this->_real_class, '_on_prepare_new_query_builder'), array(&$this));
    }

    /**
     * Executes the internal QB and filters objects based on ACLs and metadata
     *
     * @param boolean $false_on_empty_mgd_resultset used in the moving window loop to get false instead of empty array back from this method in case the **core** QB returns empty resultset
     * @return midcom_core_dbaobject[] Array filtered by ACL and metadata visibility (or false in case of failure)
     */
    private function _execute_and_check_privileges($false_on_empty_mgd_resultset = false)
    {
        try
        {
            $result = $this->_query->execute();
        }
        catch (Exception $e)
        {
            debug_add("Query failed: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            return array();
        }

        if (!is_array($result))
        {
            debug_add('Last Midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            midcom::get()->debug->log_php_error(MIDCOM_LOG_ERROR);
            return array();
        }
        if (   empty($result)
            && $false_on_empty_mgd_resultset)
        {
            return false;
        }

        $newresult = array();
        $this->denied = 0;
        foreach ($result as $object)
        {
            $classname = $this->_real_class;
            try
            {
                $object = new $classname($object);
            }
            catch (midcom_error $e)
            {
                if ($e->getCode() == MIDCOM_ERRFORBIDDEN)
                {
                    $this->denied++;
                }
                $e->log();
                continue;
            }

            // Check approval
            if (   $this->hide_invisible
                && !midcom::get()->config->get('show_unapproved_objects')
                && !$object->__object->is_approved())
            {
                continue;
            }

            $newresult[] = $object;
        }

        return $newresult;
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
     * @return midcom_core_dbaobject[] The result of the query builder.
     */
    function execute_windowed()
    {
        $this->_reset();

        if (! call_user_func_array(array($this->_real_class, '_on_prepare_exec_query_builder'), array(&$this)))
        {
            debug_add('The _on_prepare_exec_query_builder callback returned false, so we abort now.');
            return array();
        }

        if ($this->_constraint_count == 0)
        {
            debug_add('This Query Builder instance has no constraints (set loglevel to debug to see stack trace)', MIDCOM_LOG_WARN);
            debug_print_function_stack('We were called from here:');
        }

        if (   empty($this->_limit)
            && empty($this->_offset))
        {
            // No point to do windowing
            $newresult = $this->_execute_and_check_privileges();
        }
        else
        {
            $newresult = array();
            // Must be copies
            $limit = $this->_limit;
            $offset = $this->_offset;
            $i = 0;
            $this->_set_limit_offset_window($i);

            while (($resultset = $this->_execute_and_check_privileges(true)) !== false)
            {
                $size = sizeof($resultset);

                if ($offset >= $size)
                {
                    // We still have offset left to skip
                    $offset -= $size;
                    $this->_set_limit_offset_window(++$i);
                    continue;
                }
                if ($offset)
                {
                    $resultset = array_slice($resultset, $offset);
                    $size = $size - $offset;
                }

                if ($limit > $size)
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

        return $newresult;
    }

    private function _set_limit_offset_window($iteration)
    {
        if (!$this->_window_size)
        {
            // Try to be smart about the window size
            switch (true)
            {
                case (   empty($this->_offset)
                      && $this->_limit):
                    // Get limited number from start (I supposed generally less than 50% will be unreadable)
                    $this->_window_size = round($this->_limit * 1.5);
                    break;
                case (   empty($this->_limit)
                      && $this->_offset):
                    // Get rest from offset
                    /* TODO: Somehow factor in that if we have huge number of objects and relatively small offset we want to increase window size
                    $full_object_count = $this->_query->count();
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

        $offset = $iteration * $this->_window_size;
        if ($offset)
        {
            $this->_query->set_offset($offset);
        }
        $this->_query->set_limit($this->_window_size);
    }

    private function _check_groups()
    {
        while ($this->_groups > 0)
        {
            debug_add('Ending unterminated QB group', MIDCOM_LOG_INFO);
            $this->end_group();
        }
    }

    /**
     * @return midcom_core_dbaobject[]
     */
    public function execute()
    {
        $this->_check_groups();

        return $this->execute_windowed();
    }

    /**
     * Runs a query where limit and offset is taken into account <i>prior</i> to
     * execution in the core.
     *
     * This is useful in cases where you can safely assume read privileges on all
     * objects, and where you would otherwise have to deal with huge resultsets.
     *
     * Be aware that this might lead to empty resultsets "in the middle" of the
     * actual full resultset when read privileges are missing.
     *
     * @see execute()
     */
    public function execute_unchecked()
    {
        $this->_check_groups();

        $this->_reset();

        if (! call_user_func_array(array($this->_real_class, '_on_prepare_exec_query_builder'), array(&$this)))
        {
            debug_add('The _on_prepare_exec_query_builder callback returned false, so we abort now.');
            return array();
        }

        if ($this->_constraint_count == 0)
        {
            debug_add('This Query Builder instance has no constraints, see debug level log for stacktrace', MIDCOM_LOG_WARN);
            debug_print_function_stack('We were called from here:');
        }

        // Add the limit / offsets
        if ($this->_limit)
        {
            $this->_query->set_limit($this->_limit);
        }
        if ($this->_offset)
        {
            $this->_query->set_offset($this->_offset);
        }

        $newresult = $this->_execute_and_check_privileges();

        if ($this->_limit)
        {
            while (count($newresult) > $this->_limit)
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
     * @param int $key      Requested index in result set
     * @param string $mode  Execution mode: normal (default), unchecked, notwindowed
     * @return mixed        False on failure (key does not exist), object given to constructor on success
     */
    public function get_result($key, $mode = null)
    {
        if ($mode == 'unchecked')
        {
            $results = $this->execute_unchecked();
        }
        else
        {
            $results = $this->execute();
        }

        if (!isset($results[$key]))
        {
            return false;
        }

        return $results[$key];
    }

    /**
     * Include deleted objects (metadata.deleted is true) in query results.
     *
     * Note: this may cause all kinds of weird behavior with the DBA helpers
     */
    public function include_deleted()
    {
        $this->_reset();
        $this->_include_deleted = true;
        $this->_query->include_deleted();
    }

    /**
     * Returns the number of elements matching the current query.
     *
     * Due to ACL checking we must first execute the full query
     *
     * @return integer The number of records found by the last query.
     */
    public function count()
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
     * @return integer The number of records matching the constraints without taking access control or visibility into account.
     */
    public function count_unchecked()
    {
        $this->_check_groups();

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

    /**
     * Sets read-only mode for underlying midgard_query_builder instance.
     * If $toggle is true, all objects returned with execute method have read-only properties,
     * which can not be set, and all intances are created with better performance.
     * This method is dedicated for resultsets which are not meant to be updated or edited.
     *
     * If underlying midgard_query_builder doesn't provide read-only toggle, this method does nothing.
     *
     * @param bool $toggle enables or disables query builder read-only mode.
     */
    public function toggle_read_only($toggle = false)
    {
        if (method_exists($this->_query, "toggle_read_only"))
        {
            $this->_query->toggle_read_only($toggle);
        }
    }
}
