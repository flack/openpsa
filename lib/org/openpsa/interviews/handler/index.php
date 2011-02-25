<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Phone interview index handler
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_handler_index extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        $this->_request_data['campaigns'] = Array();

        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('archived', '=', 0);
        $this->_request_data['campaigns'] = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_index($handler_id, array &$data)
    {
        midcom_show_style('show-index');
    }
}
?>