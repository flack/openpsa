<?php
/**
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Wrapper for midcom_core_collector. It adds some additional logic to return related objects directly
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_collector extends midcom_core_collector
{
    /**
     * The prefix for query constraints concerning the object(s) at hand
     */
    private string $_object_prefix = '';

    /**
     * The prefix for query constraints concerning the objects we're looking for
     */
    private string $_other_prefix = '';

    /**
     * The class(es) of the objects we're looking for
     */
    private array $_target_classes = [];

    /**
     * Additional constraints for the QBs used to find the related objects
     */
    private array $_object_constraints = [];

    /**
     * Limit for the QBs used to find the related objects
     */
    private int $_object_limit = 0;

    /**
     * Orders for the QBs used to find the related objects
     */
    private array $_object_orders = [];

    /**
     * Takes one or more object guids and classnames and constructs a collector accordingly.
     *
     * Attention: At least one of these arguments has to be a string
     *
     * @param mixed $guids One or more object guids
     * @param mixed $classes One or more target classes
     * @param string $direction incoming or outgoing
     */
    public function __construct($guids, $classes, string $direction = 'incoming')
    {
        $this->_set_direction($direction);

        if (is_string($guids)) {
            parent::__construct(org_openpsa_relatedto_dba::class, $this->_object_prefix . 'Guid', $guids);
            $this->add_constraint($this->_other_prefix . 'Class', 'IN', (array) $classes);
        } elseif (is_string($classes)) {
            parent::__construct(org_openpsa_relatedto_dba::class, $this->_other_prefix . 'Class', $classes);
            $this->add_constraint($this->_object_prefix . 'Guid', 'IN', (array) $guids);
        } else {
            throw new midcom_error('None of the arguments was passed as a string');
        }

        //save target classes for later use
        $this->_target_classes = (array) $classes;

        $this->add_value_property($this->_other_prefix . 'Guid');
    }

    private function _set_direction(string $dir)
    {
        if ($dir == 'incoming') {
            $this->_object_prefix = 'to';
            $this->_other_prefix = 'from';
        } else {
            $this->_object_prefix = 'from';
            $this->_other_prefix = 'to';
        }
    }

    /**
     * Save object QB constraints for later use
     */
    public function add_object_constraint(string $field, string $operator, $value)
    {
        $this->_object_constraints[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
    }

    /**
     * Save object QB orders for later use
     */
    public function add_object_order(string $field, string $direction)
    {
        $this->_object_orders[] = [
            'field' => $field,
            'direction' => $direction
        ];
    }

    /**
     * Save object QB limit for later use
     *
     * @param integer $limit The query limit
     */
    public function set_object_limit($limit)
    {
        $this->_object_limit = $limit;
    }

    /**
     * Apply constraints (if any) to the final object QBs
     */
    private function _apply_object_constraints(midcom_core_querybuilder $qb)
    {
        foreach ($this->_object_constraints as $constraint) {
            $qb->add_constraint($constraint['field'], $constraint['operator'], $constraint['value']);
        }
    }

    /**
     * Apply orders (if any) to the final object QBs
     */
    private function _apply_object_orders(midcom_core_querybuilder $qb)
    {
        foreach ($this->_object_orders as $order) {
            $qb->add_order($order['field'], $order['direction']);
        }
    }

    /**
     * Apply the limit (if any) to the final object QBs
     */
    private function _apply_object_limit(midcom_core_querybuilder $qb)
    {
        if ($this->_object_limit == 0) {
            return;
        }
        $qb->set_limit($this->_object_limit);
    }

    /**
     * @return midcom_core_dbaobject[] DBA objects grouped by the specified key
     */
    public function get_related_objects_grouped_by(string $key) : array
    {
        $entries = [];
        $guids = [];

        $this->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);

        foreach ($this->get_rows([$key]) as $relation) {
            $group_value = $relation[$key];
            if (!array_key_exists($group_value, $guids)) {
                $guids[$group_value] = [];
            }
            $guids[$group_value][] = $relation[$this->_other_prefix . 'Guid'];
        }

        foreach ($guids as $group_value => $grouped_guids) {
            foreach ($this->_target_classes as $classname) {
                $qb = $classname::new_query_builder();
                $qb->add_constraint('guid', 'IN', $grouped_guids);
                $this->_apply_object_constraints($qb);
                $this->_apply_object_orders($qb);
                $this->_apply_object_limit($qb);
                if (!array_key_exists($group_value, $entries)) {
                    $entries[$group_value] = [];
                }
                $entries[$group_value] = array_merge($entries[$group_value], $qb->execute());
            }
        }

        return $entries;
    }

    /**
     * @return midcom_core_dbaobject[] DBA objects
     */
    public function get_related_objects() : array
    {
        $entries = [];

        $guids = $this->get_related_guids();

        if (empty($guids)) {
            return $entries;
        }

        foreach ($this->_target_classes as $classname) {
            $qb = $classname::new_query_builder();
            $qb->add_constraint('guid', 'IN', $guids);
            $this->_apply_object_constraints($qb);
            $this->_apply_object_orders($qb);
            $this->_apply_object_limit($qb);
            $entries = array_merge($entries, $qb->execute());
        }

        return $entries;
    }

    /**
     * @return array Array of GUIDs
     */
    public function get_related_guids() : array
    {
        $this->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);

        return array_values($this->get_values($this->_other_prefix . 'Guid'));
    }
}
