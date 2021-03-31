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
 * instead use the new_query_builder() method available in the MidCOM DBA API or the
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
     * When determining window sizes for offset/limit queries use this as maximum size
     */
    private $max_window_size = 500;

    /**
     * The initialization routine
     *
     * @param string $classname The classname which should be queried.
     */
    public function __construct(string $classname)
    {
        $mgdschemaclass = $this->_convert_class($classname);

        $this->_query = new midgard_query_builder($mgdschemaclass);
    }

    /**
     * Executes the internal QB and filters objects based on ACLs and metadata
     *
     * @return midcom_core_dbaobject[] Array filtered by ACL and metadata visibility
     */
    private function _execute_and_check_privileges() : array
    {
        $result = [];

        foreach ($this->_query->execute() as $object) {
            $classname = $this->_real_class;
            if ($this->is_readable($object->guid)) {
                $result[] = new $classname($object);
            } else {
                $this->denied++;
            }
        }

        return $result;
    }

    /**
     * Execute the Querybuilder and call the appropriate callbacks from the associated
     * class. This way, class authors have full control over what is actually
     * returned to the application.
     *
     * The calling sequence of all event handlers of the associated class is like this:
     *
     * 1. boolean _on_execute($this) is called before the actual query execution. Return false to
     *    abort the operation.
     * 2. The query is executed.
     * 3. void _on_process_query_result(&$result) is called after the successful execution of the query. You
     *    may remove any unwanted entries from the resultset at this point.
     *
     * @return midcom_core_dbaobject[] The result of the query builder.
     */
    public function execute() : array
    {
        $this->_reset();

        if (!$this->prepare_execute()) {
            return [];
        }

        $this->_add_visibility_checks();

        if (   empty($this->_limit)
            && empty($this->_offset)) {
            // No point to do windowing
            $newresult = $this->_execute_and_check_privileges();
        } else {
            $newresult = $this->execute_windowed();
        }

        call_user_func_array([$this->_real_class, '_on_process_query_result'], [&$newresult]);

        $this->count = count($newresult);

        return $newresult;
    }

    /**
     * Windowed querying.
     *
     * Since ACLs/approval can hide objects on the PHP level, we cannot apply offsets
     * directly to the QB, but must instead verify that all objects are visible to the
     * current user before discarding them until the offset is met. To reduce memory consumption,
     * a maximum number of entries is loaded in each iteration.
     *
     * If we have a limit, we use it to dynamically adjust the window size between
     * iterations to try to not load too much data, i.e. we start by querying the minimum number
     * required to fill the offset and limit. If we end up with too few results,
     * we do another iteration where we adjust the window according to how many we still need
     * plus a padding that corresponds to the weighted number of denieds so far. This is repeated
     * until the limit is filled or we run out of db results
     *
     * @return midcom_core_dbaobject[]
     */
    private function execute_windowed() : array
    {
        $newresult = [];
        $denied = $this->denied;
        $offset = $this->_offset;
        $limit = $this->_limit;
        $total_queried = 0;
        $window_size = $this->max_window_size;
        if ($limit > 0) {
            $window_size = min($offset + $limit, $this->max_window_size);
        }
        $this->_query->set_limit($window_size);

        while (    ($resultset = $this->_execute_and_check_privileges())
                || $this->denied > $denied) {
            $size = count($resultset);
            $total_size = $size + ($this->denied - $denied);

            if ($offset >= $size) {
                // We still have offset left to skip
                $offset -= $size;
            } else {
                if ($offset) {
                    $resultset = array_slice($resultset, $offset);
                    $size -= $offset;
                    $offset = 0;
                }

                if ($limit > $size) {
                    $limit -= $size;
                } elseif ($limit > 0) {
                    // We have reached the limit
                    $resultset = array_slice($resultset, 0, $limit);
                    $newresult = array_merge($newresult, $resultset);
                    break;
                }

                $newresult = array_merge($newresult, $resultset);
            }

            if ($total_size < $window_size) {
                // if we got less results than we asked for, we've reached the end of data
                break;
            }

            $denied = $this->denied;
            $total_queried += $window_size;
            if ($limit > 0) {
                $denied_ratio = $denied / $total_queried;
                $window_size = min(ceil($limit + ($denied * $denied_ratio)), $this->max_window_size);
            }
            $this->_query->set_offset($total_queried);
            $this->_query->set_limit($window_size);
        }
        return $newresult;
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
    public function execute_unchecked() : array
    {
        $this->_reset();

        if (!$this->prepare_execute()) {
            return [];
        }

        // Add the limit / offsets
        if ($this->_limit) {
            $this->_query->set_limit($this->_limit);
        }
        if ($this->_offset) {
            $this->_query->set_offset($this->_offset);
        }
        $this->_add_visibility_checks();

        $newresult = $this->_execute_and_check_privileges();

        call_user_func_array([$this->_real_class, '_on_process_query_result'], [&$newresult]);

        $this->count = count($newresult);

        return $newresult;
    }

    /**
     * Get result by its index
     *
     * @param int $key      Requested index in result set
     * @return mixed        False on failure (key does not exist), object given to constructor on success
     */
    public function get_result(int $key)
    {
        $results = $this->execute();

        if (!isset($results[$key])) {
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
        $this->_query->include_deleted();
    }

    /**
     * Returns the number of elements matching the current query.
     *
     * Due to ACL checking we must first execute the full query
     */
    public function count() : int
    {
        if ($this->count == -1) {
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
     */
    public function count_unchecked() : int
    {
        if ($this->_limit) {
            $this->_query->set_limit($this->_limit);
        }
        if ($this->_offset) {
            $this->_query->set_offset($this->_offset);
        }
        return $this->_query->count();
    }
}
