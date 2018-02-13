<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.projects NAP interface class.
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_navigation extends midcom_baseclasses_components_navigation
{
    /**
     * Returns a static leaf list with access to different task lists.
     */
    public function get_leaves()
    {
        return [
            "{$this->_topic->id}:tasks_current" => [
                MIDCOM_NAV_URL => "task/list/current/",
                MIDCOM_NAV_NAME => $this->_l10n->get('current tasks'),
            ],
            "{$this->_topic->id}:tasks_open" => [
                MIDCOM_NAV_URL => "task/list/open/",
                MIDCOM_NAV_NAME => $this->_l10n->get('open tasks'),
            ],
            "{$this->_topic->id}:tasks_closed" => [
                MIDCOM_NAV_URL => "task/list/closed/",
                MIDCOM_NAV_NAME => $this->_l10n->get('closed tasks'),
            ],
            "{$this->_topic->id}:tasks_invoiceable" => [
                MIDCOM_NAV_URL => "task/list/invoiceable/",
                MIDCOM_NAV_NAME => $this->_l10n->get('invoiceable tasks'),
            ],
            "{$this->_topic->id}:tasks_invoiced" => [
                MIDCOM_NAV_URL => "task/list/invoiced/",
                MIDCOM_NAV_NAME => $this->_l10n->get('invoiced tasks'),
            ]
        ];
    }
}
