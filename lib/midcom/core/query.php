<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Common base class for collector and querybuilder
 *
 * @package midcom
 */
abstract class midcom_core_query
{
    /**
     * Set this element to true to hide all items which are currently invisible according
     * to the approval/scheduling settings made using Metadata. This must be set before executing
     * the query.
     *
     * NOTE: Approval checks not implemented in collector yet
     *
     * Be aware, that this setting will currently not use the QB to filter the objects accordingly,
     * since there is no way yet to filter against parameters. This will mean some performance
     * impact.
     *
     * @var boolean
     */
    public $hide_invisible = true;

    /**
     * Flag that tracks whether deleted visibility check have already been added
     *
     * @var boolean
     */
    protected $_visibility_checks_added = false;

    /**
     * The number of records to return to the client at most.
     *
     * @var int
     */
    protected $_limit = 0;

    /**
     * The offset of the first record the client wants to have available.
     *
     * @var int
     */
    protected $_offset = 0;

    /**
     * The ordering instructions used for this query
     *
     * @var array
     */
    protected $_orders = [];

    /**
     * Type that the application expects to retrieve from this instance.
     *
     * @var string
     */
    protected $_real_class;

    /**
     * The number of records found by the last execute() run. This is -1 as long as no
     * query has been executed. This member is read-only.
     *
     * @var int
     */
    protected $count = -1;

    /**
     * The query backend, should be set in constructor. Currently collector or querybuilder
     *
     * @var \midgard\portable\query
     */
    protected $_query;

    /**
     * The number of objects for which access was denied.
     *
     * @var int
     */
    public $denied = 0;

    /**
     * Class resolution into the MidCOM DBA system.
     * Currently, Midgard requires the actual MgdSchema base classes to be used
     * when dealing with the query, so we internally note the corresponding class
     * information to be able to do correct typecasting later.
     *
     * @param string $classname The classname which should be converted.
     * @return string MgdSchema class name
     */
    protected function _convert_class($classname)
    {
        if (!class_exists($classname)) {
            throw new midcom_error("Cannot create a midcom_core_query instance for the type {$classname}: Class does not exist.");
        }

        static $_class_mapping_cache = [];

        $this->_real_class = $classname;
        if (!array_key_exists($classname, $_class_mapping_cache)) {
            if (!is_subclass_of($classname, midcom_core_dbaobject::class)) {
                throw new midcom_error(
                    "Cannot create a midcom_core_query instance for the type {$classname}: Does not seem to be a DBA class name."
                );
            }

            // Figure out the actual MgdSchema class from the decorator
            $dummy = new $classname();
            $mgdschemaclass = $dummy->__mgdschema_class_name__;
            $_class_mapping_cache[$classname] = $mgdschemaclass;
        }
        return $_class_mapping_cache[$classname];
    }

    protected function _add_visibility_checks()
    {
        if (   $this->hide_invisible
            && !$this->_visibility_checks_added) {
            if (!midcom::get()->config->get('show_hidden_objects')) {
                $this->add_constraint('metadata.hidden', '=', false);
                $now = strftime('%Y-%m-%d %H:%M:%S');
                $this->begin_group('OR');
                    $this->add_constraint('metadata.schedulestart', '>', $now);
                    $this->add_constraint('metadata.schedulestart', '=', '0000-00-00 00:00:00');
                $this->end_group();
                $this->add_constraint('metadata.scheduleend', '<', $now);
            }

            if (!midcom::get()->config->get('show_unapproved_objects')) {
                $this->add_constraint('metadata.isapproved', '=', true);
            }
            $this->_visibility_checks_added = true;
        }
    }

    /**
     * Resets some internal variables for re-execute
     */
    protected function _reset()
    {
        $this->count = -1;
        $this->denied = 0;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function get_doctrine()
    {
        return $this->_query->get_doctrine();
    }

    /**
     * @return \Doctrine\ORM\Query\Expr:
     */
    public function get_current_group()
    {
        return $this->_query->get_current_group();
    }

    /**
     * Add a constraint to the query.
     *
     * @param string $field The name of the MgdSchema property to query against.
     * @param string $operator The operator to use for the constraint, currently supported are
     *     <, <=, =, <>, >=, >, LIKE. LIKE uses the percent sign ('%') as a
     *     wildcard character.
     * @param mixed $value The value to compare against. It should be of the same type as the
     *     queried property.
     * @return boolean Indicating success.
     */
    public function add_constraint($field, $operator, $value)
    {
        $this->_reset();
        // Add check against null values, Core MC is too stupid to get this right.
        if ($value === null) {
            debug_add("Query: Cannot add constraint on field '{$field}' with null value.", MIDCOM_LOG_WARN);
            return false;
        }
        // Deal with empty arrays, which would produce invalid queries
        // This is done here to avoid repetitive code in callers, and because
        // it's easy enough to generalize: IN empty set => always false, NOT IN empty set => always true
        if (   is_array($value)
            && empty($value)) {
            if ($operator == 'NOT IN') {
                return true;
            }
            if ($operator == 'IN') {
                return $this->add_constraint('id', '=', 0);
            }
        }
        if (!$this->_query->add_constraint($field, $operator, $value)) {
            debug_add("Failed to execute add_constraint.", MIDCOM_LOG_ERROR);
            debug_add("Class = '{$this->_real_class}, Field = '{$field}', Operator = '{$operator}'");
            debug_print_r('Value:', $value);

            return false;
        }

        return true;
    }

    /**
     * Add a constraint against another DB column to the query.
     *
     * @param string $field The name of the MgdSchema property to query against.
     * @param string $operator The operator to use for the constraint, currently supported are
     *     <, <=, =, <>, >=, >, LIKE. LIKE uses the percent sign ('%') as a
     *     wildcard character.
     * @param string $compare_field The field to compare against.
     * @return boolean Indicating success.
     */
    public function add_constraint_with_property($field, $operator, $compare_field)
    {
        $this->_reset();
        if (!$this->_query->add_constraint_with_property($field, $operator, $compare_field)) {
            debug_add("Failed to execute add_constraint_with_property.", MIDCOM_LOG_ERROR);
            debug_add("Class = '{$this->_real_class}, Field = '{$field}', Operator = '{$operator}', compare_field: '{$compare_field}'");

            return false;
        }

        return true;
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
    public function begin_group($operator = 'OR')
    {
        if (!$this->_query->begin_group($operator)) {
            debug_add("Failed to execute begin_group {$operator}", MIDCOM_LOG_ERROR);
        }
    }

    /**
     * Ends a group previously started with begin_group().
     */
    public function end_group()
    {
        if (!$this->_query->end_group()) {
            debug_add("Failed to execute end_group", MIDCOM_LOG_ERROR);
        }
    }

    /**
     * Limits the resultset to contain at most the specified number of records.
     * Set the limit to zero to retrieve all available records.
     *
     * @param int $limit The maximum number of records in the resultset.
     */
    public function set_limit($limit)
    {
        $this->_reset();
        $this->_limit = $limit;
    }

    /**
     * Sets the offset of the first record to retrieve. This is a zero based index,
     * so if you want to retrieve from the very first record, the correct offset would
     * be zero, not one.
     *
     * @param int $offset The record number to start with.
     */
    public function set_offset($offset)
    {
        $this->_reset();

        $this->_offset = $offset;
    }

    /**
     * Add an ordering constraint to the query builder.
     *
     * @param string $field The name of the MgdSchema property to query against.
     * @param string $direction One of 'ASC' or 'DESC' indicating ascending or descending
     *     ordering. The default is 'ASC'.
     * @return boolean Indicating success.
     */
    public function add_order($field, $direction = 'ASC')
    {
        if (!$this->_query->add_order($field, $direction)) {
            debug_add("Failed to execute add_order for column '{$field}', midgard error: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }
        $this->_orders[] = [
            'field' => $field,
            'direction' => $direction
        ];

        return true;
    }

    /**
     * Get the DBA class we're currently working on
     */
    public function get_classname()
    {
        return $this->_real_class;
    }

    abstract public function execute();

    abstract public function count();

    abstract public function count_unchecked();
}
