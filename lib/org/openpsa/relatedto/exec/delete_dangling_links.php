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
echo "<pre>\n";

$qb = org_openpsa_relatedto_dba::new_query_builder();
$results = $qb->execute();

$total = count($results);

echo "Checking " . $total . " relatedto links. \n";

foreach ($results as $i => $result) {
    try {
        midcom::get()->dbfactory->get_object_by_guid($result->fromGuid);
        midcom::get()->dbfactory->get_object_by_guid($result->toGuid);
    } catch (midcom_error $e) {
        echo $i . "/" . $total . ": Deleting relatedto #" . $result->id . "\n";
        $result->delete();
    }
}

echo "\nDone.";
echo "</pre>";
