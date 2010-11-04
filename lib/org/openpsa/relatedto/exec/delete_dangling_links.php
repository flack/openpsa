<?php

/**
 * Script to remove dangling relatedto links
 * 
 * The script requires admin privileges to execute properly.
 *
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: convert_dba_classnames.php 22992 2009-07-23 16:11:41Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
$_MIDCOM->auth->require_admin_user();

@ini_set('max_execution_time', 0);
while(@ob_end_flush());
echo "<pre>\n";
flush();

$qb = org_openpsa_relatedto_dba::new_query_builder();
$results = $qb->execute();

$total = sizeof($results);

echo "Checking " . $total . " relatedto links. \n";
flush();

$i = 0;
foreach ($results as $result)
{
    $i++;
    if (   !$_MIDCOM->dbfactory->get_object_by_guid($result->fromGuid)
        || !$_MIDCOM->dbfactory->get_object_by_guid($result->toGuid))
    {
        echo $i . "/" . $total . ": Deleting relatedto #" . $result->id . "\n";
        flush();
        $result->delete();
    }
}

echo "\nDone.";
echo "</pre>";
ob_start();