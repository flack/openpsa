<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: viewer.php,v 1.2 2006/05/08 13:18:40 rambo Exp $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Phone interview interface class.
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_viewer extends midcom_baseclasses_components_request
{
    function _on_initialize()
    {
        // Match /
        $this->_request_switch[] = array
        (
            'handler' => Array('org_openpsa_interviews_handler_index', 'index'),
        );

        // Match /campaign/<campaign>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'campaign',
            'variable_args' => 1,
            'handler' => Array('org_openpsa_interviews_handler_campaign', 'summary'),
        );

        // Match /next/<campaign>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'next',
            'variable_args' => 1,
            'handler' => Array('org_openpsa_interviews_handler_campaign', 'next'),
        );

        // Match /interview/<member>
        $this->_request_switch[] = array
        (
            'fixed_args' => 'interview',
            'variable_args' => 1,
            'handler' => Array('org_openpsa_interviews_handler_interview', 'interview'),
        );

        // Match /report/all/<campaign>
        $this->_request_switch[] = array
        (
            'fixed_args' => Array('report', 'all'),
            'variable_args' => 1,
            'handler' => Array('org_openpsa_interviews_handler_report', 'all'),
        );
    }
}
?>