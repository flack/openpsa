<?php
/**
 * Update script to convert all relatedto objects to use the new DBA classnames in their
 * properties.
 *
 * The script requires admin privileges to execute properly.
 *
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: convert_dba_classnames.php 25183 2010-02-23 22:19:32Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
$_MIDCOM->auth->require_admin_user();

$replaces = array
(
    'org_openpsa_calendar_event' => 'org_openpsa_calendar_event_dba',
    'org_openpsa_contacts_person' => 'org_openpsa_contacts_person_dba',
    'org_openpsa_projects_task' => 'org_openpsa_projects_task_dba',
    'org_openpsa_projects_hour_report' => 'org_openpsa_projects_hour_report_dba',
    'org_openpsa_documents_document' => 'org_openpsa_documents_document_dba',
    'org_openpsa_invoices_invoice' => 'org_openpsa_invoices_invoice_dba',
    'org_openpsa_sales_salesproject' => 'org_openpsa_sales_salesproject_dba',
);

@ini_set('max_execution_time', 0);
while(@ob_end_flush());
echo "<pre>\n";
flush();

$qb = org_openpsa_relatedto_dba::new_query_builder();
$qb->begin_group('OR');
    $qb->add_constraint('fromClass', 'LIKE', 'org_openpsa_%');
    $qb->add_constraint('toClass', 'LIKE', 'org_openpsa_%');
$qb->end_group();
$results = $qb->execute();
foreach ($results as $result)
{
    $needs_update = false;
    if (array_key_exists($result->fromClass, $replaces))
    {
        $needs_update = true;
        $result->fromClass = $replaces[$result->fromClass];
    }
    if (array_key_exists($result->toClass, $replaces))
    {
        $needs_update = true;
        $result->toClass = $replaces[$result->toClass];
    }
    if ($needs_update)
    {
        $result->update();
        echo $result->guid . " updated\n";
        flush();
    }
}

$_MIDCOM->cache->invalidate_all();

echo "</pre>";
ob_start();
?>