<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_notifier
{
    private $actions = array(
        MIDCOM_OPERATION_DBA_CREATE => 'create',
        MIDCOM_OPERATION_DBA_UPDATE => 'update',
        MIDCOM_OPERATION_DBA_DELETE => 'delete',
    );

    public function run($operation, midcom_core_dbaobject $object)
    {
        if (!array_keys($this->actions, $operation)) {
            throw new midcom_error('Unsupported action');
        }
        $action = $this->actions[$operation];
    }
}