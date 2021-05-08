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
    protected function _prepare_qbs(string $classname)
    {
        $this->_midcom_qb = new midgard_query_builder($classname);
        // Make another QB for counting, we need to do this to avoid trouble with core internal references system
        $this->_midcom_qb_count = new midgard_query_builder($classname);
    }

    /**
     * Wraps to count since this is what midcom QB does, too
     */
    public function count_unchecked() : int
    {
        if (!$this->count) {
            $this->count = $this->_midcom_qb_count->count();
        }
        return $this->count;
    }
}
