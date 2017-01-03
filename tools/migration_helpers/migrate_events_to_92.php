<?php
/**
 * Converter script for removing midgard_event & midgard_eventmember
 * (should be run in Asgard shell)
 */

use midgard\portable\storage\connection;

$em = connection::get_em();

$q = $em->createQuery("UPDATE midgard:midgard_repligard r SET r.typename='org_openpsa_eventmember' where r.typename='midgard_eventmember'");
var_dump($q->execute());
$q = $em->createQuery("UPDATE midgard:midgard_repligard r SET r.typename='org_openpsa_event' where r.typename='midgard_event'");
var_dump($q->execute());
