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
    public function __construct(string $classname, string $pager_id)
    {
        $this->initialize($pager_id);
        $this->_query = new midgard_query_builder($classname);
    }

    public function execute() : array
    {
        $this->parse_variables();
        $this->_query->set_limit($this->results_per_page);
        $this->_query->set_offset($this->_offset);

        return $this->_query->execute();
    }
}
