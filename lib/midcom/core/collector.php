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
 * instead use the new_collector() method available in the MidCOM DBA API or the
 * midcom_helper__dbfactory::new_collector() method which is still available.
 *
 * If you have to do create the instance manually however, do not forget to call the
 * {@link initialize()} function after construction, or the creation callbacks will fail.
 *
 * @package midcom
 */
class midcom_core_collector extends midcom_core_query
{
    /**
     * The initialization routine
     */
    public function __construct(string $classname, ?string $domain = null, $value = null)
    {
        $mgdschemaclass = $this->_convert_class($classname);

        $this->_query = new midgard_collector($mgdschemaclass, $domain, $value);

        // MidCOM's collector always uses the GUID as the key for ACL purposes
        $this->_query->set_key_property('guid');
    }

    /**
     * Execute the Querybuilder and call the appropriate callbacks from the associated
     * class. This way, class authors have full control over what is actually returned to the application.
     *
     * The calling sequence of all event handlers of the associated class is like this:
     *
     * 1. boolean _on_execute() is called before the actual query execution. Return false to
     *    abort the operation.
     *
     * @return boolean True if the query was executed, false otherwise (e.g. if it had been executed already)
     * @see midgard_collector::execute()
     */
    public function execute()
    {
        if ($this->prepare_execute()) {
            $this->_add_visibility_checks();
            return $this->_query->execute();
        }
        return false;
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
     * @see list_keys()
     */
    public function list_keys_unchecked() : array
    {
        $this->_reset();

        // Add the limit / offsets
        if ($this->_limit) {
            $this->_query->set_limit($this->_limit);
        }
        $this->_query->set_offset($this->_offset);

        $newresult = $this->_list_keys_and_check_privileges(false);

        call_user_func_array([$this->_real_class, '_on_process_collector_result'], [&$newresult]);

        $this->count = count($newresult);

        return $newresult;
    }

    private function _list_keys_and_check_privileges(bool $apply_offset_limit = true) : array
    {
        $this->execute();
        $result = $this->_query->list_keys();
        $newresult = [];
        $counter = 0;

        foreach ($result as $object_guid => $empty_copy) {
            if (!$this->is_readable($object_guid)) {
                debug_add("Failed to load result, read privilege on {$object_guid} not granted for the current user.", MIDCOM_LOG_INFO);
                continue;
            }

            if ($apply_offset_limit) {
                $counter++;
                if ($counter <= $this->_offset) {
                    continue;
                }
                if (   $this->_limit
                    && $counter > ($this->_offset + $this->_limit)) {
                    break;
                }
            }

            // Register the GUID as loaded in this request
            midcom::get()->cache->content->register($object_guid);

            $newresult[$object_guid] = [];
        }
        return $newresult;
    }

    /**
     * Convenience function to get all values of a specific column, indexed by GUID
     *
     * @param string $field the column name
     */
    public function get_values(string $field) : array
    {
        $this->add_value_property($field);
        $this->execute();
        $results = $this->list_keys();
        foreach ($results as $guid => &$value) {
            $value = $this->get_subkey($guid, $field);
        }
        return $results;
    }

    /**
     * Convenience function to get all values of a number of columns
     * They are indexed by GUID unless you specify something else
     */
    public function get_rows(array $fields, string $indexed_by = 'guid') : array
    {
        array_map([$this, 'add_value_property'], $fields);

        if ($indexed_by !== 'guid') {
            $this->add_value_property($indexed_by);
        }

        $this->execute();
        $results = [];
        $keys = $this->list_keys();
        foreach ($keys as $guid => $values) {
            $values = $this->get($guid);
            $index = $guid;
            if ($indexed_by !== 'guid') {
                $index = $values[$indexed_by];
            }
            $results[$index] = $values;
        }
        return $results;
    }

    /**
     * implements midgard_collector::list_keys with ACL checking
     */
    public function list_keys() : array
    {
        $result = $this->_list_keys_and_check_privileges();

        call_user_func_array([$this->_real_class, '_on_process_collector_result'], [&$result]);

        $this->count = count($result);

        return $result;
    }

    public function get_subkey(string $key, string $property)
    {
        if (!$this->is_readable($key)) {
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }
        return $this->_query->get_subkey($key, $property);
    }

    public function get(string $key)
    {
        if (!$this->is_readable($key)) {
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }
        return $this->_query->get($key);
    }

    public function destroy()
    {
        return $this->_query->destroy();
    }

    public function add_value_property(string $property) : bool
    {
        if (!$this->_query->add_value_property($property)) {
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
    public function count() : int
    {
        if ($this->count == -1) {
            $this->execute();
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

    public function get_objects() : array
    {
        $this->execute();
        $guids = $this->list_keys();

        if (empty($guids)) {
            return [];
        }

        $qb = new midcom_core_querybuilder($this->_real_class);
        $qb->hide_invisible = $this->hide_invisible;
        $qb->add_constraint('guid', 'IN', array_keys($guids));
        foreach ($this->_orders as $order) {
            $qb->add_order($order['field'], $order['direction']);
        }

        return $qb->execute();
    }
}
