<?php
/**
 * Script to remove dangling relatedto links
 *
 * The script requires admin privileges to execute properly.
 *
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
midcom::get()->auth->require_admin_user();

midcom::get()->disable_limits();
while (@ob_end_flush());
echo "<pre>\n";
flush();

$qb = org_openpsa_relatedto_dba::new_query_builder();
$results = $qb->execute();

$total = sizeof($results);

echo "Checking " . $total . " relatedto links. \n";
flush();

$i = 0;
foreach ($results as $result) {
    $i++;
    try {
        midcom::get()->dbfactory->get_object_by_guid($result->fromGuid);
        midcom::get()->dbfactory->get_object_by_guid($result->toGuid);
    } catch (midcom_error $e) {
        echo $i . "/" . $total . ": Deleting relatedto #" . $result->id . "\n";
        flush();
        $result->delete();
    }
}

echo "\nDone.";
echo "</pre>";
ob_start();
