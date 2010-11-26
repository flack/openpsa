<?php
/**
 * @package org.openpsa.invoices
 * @author Nemein Oy, http://www.nemein.com/
 * @version $Id: hour.php 24773 2010-01-18 08:15:45Z rambo $
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_invoice_hour_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_invoice_hour';

    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        return parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    function _on_created()
    {
        parent::_on_created();

        // Cache the information that the hour report has been invoiced to the report itself
        $hour_report = new org_openpsa_projects_hour_report_dba($this->hourReport);
        if (!$hour_report)
        {
            return false;
        }
        $hour_report->invoiced = date('Y-m-d H:i:s', $this->metadata->created);
        $invoicer = org_openpsa_contacts_person_dba::get_cached($this->metadata->creator);
        $hour_report->invoicer = $invoicer->id;
        return $hour_report->update();
    }

    /**
     * Marks the hour report as uninvoiced
     */
    function _on_deleted()
    {
        parent::_on_deleted();

        if (! $_MIDCOM->auth->request_sudo('org.openpsa.invoices'))
        {
            debug_add('Failed to get SUDO privileges, skipping invoice hour deletion silently.', MIDCOM_LOG_ERROR);
            return;
        }

        $hour_report = new org_openpsa_projects_hour_report_dba($this->hourReport);
        if (!$hour_report)
        {
            return;
        }
        $hour_report->invoiced = '0000-00-00 00:00:00';
        $hour_report->invoicer = 0;
        if (!$hour_report->update())
        {
            debug_add("Failed to mark hour report {$hour_report->id} as uninvoiced, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
        }

        $_MIDCOM->auth->drop_sudo();
        return;
    }
}
?>