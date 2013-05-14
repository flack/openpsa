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
    public function __construct($classname, $pager_id)
    {
        midcom_baseclasses_components_purecode::__construct();
        $this->_limit =& $this->results_per_page;
        $this->_pager_id = $pager_id;
        $this->_midcom_qb = new midgard_query_builder($classname);
        // Make another QB for counting, we need to do this to avoid trouble with core internal references system
        $this->_midcom_qb_count = new midgard_query_builder($classname);
        $this->_prefix = 'org_openpsa_qbpager_' . $this->_pager_id . '_';
    }

    function execute()
    {
        if (!$this->_sanity_check())
        {
            return false;
        }
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