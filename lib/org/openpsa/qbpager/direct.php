<?php
/**
 * @package org.openpsa.qbpager
 */
/**
 * Pages QB resultsets (uses midgard QB directly)
 *
 * @package org.openpsa.qbpager
 */
class org_openpsa_qbpager_direct extends org_openpsa_qbpager
{
    function __construct($classname, $pager_id)
    {
        $this->_component = 'org.openpsa.qbpager';
        midcom_baseclasses_components_purecode::__construct();
        $this->_limit =& $this->results_per_page;
        $this->_pager_id = $pager_id;
        $this->_midcom_qb = new midgard_query_builder($classname);
        // Make another QB for counting, we need to do this to avoid trouble with core internal references system
        $this->_midcom_qb_count = new midgard_query_builder($classname);
        if (!$this->_sanity_check())
        {
            return false;
        }
        $this->_prefix = 'org_openpsa_qbpager_' . $this->_pager_id . '_';

        return true;
    }

    function execute()
    {
        if (!$this->_sanity_check())
        {
            return false;
        }
        /* In fact in 1.8 it's always reference to the core level QB so this doesn't really circumvent the problem of setting limit and offset before counting...
        $qb_copy = $this->_midcom_qb;
        $this->_qb_limits($qb_copy);
        return @$qb_copy->execute();
        */
        $this->_qb_limits($this->_midcom_qb);
        return $this->_midcom_qb->execute();
    }

    /**
     * Wraps to count since midgard QB does not support said method yet
     */
    function execute_unchecked()
    {
        return $this->execute();
    }

    /**
     * Wraps to count since midgard QB does not support said method yet
     */
    function count_unchecked()
    {
        return $this->count();
    }
}
?>