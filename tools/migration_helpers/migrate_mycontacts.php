<?php
$qb = org_openpsa_contacts_group_dba::new_query_builder();
$qb->add_constraint('owner.name', '=', '__org_openpsa_contacts_list');

foreach ($qb->execute() as $list) {
    $guid = str_replace('mycontacts_', '', $list->name);
    try {
        $person = new midcom_db_person($guid);
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        continue;
    }
    $contacts = [];
    foreach (array_keys($list->get_members()) as $uid) {
        try {
            $member = midcom_db_person::get_cached($uid);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            continue;
        }
        $contacts[] = $member->guid;
    }
    $person->set_parameter('org.openpsa.contacts', 'mycontacts', serialize($contacts));
}

