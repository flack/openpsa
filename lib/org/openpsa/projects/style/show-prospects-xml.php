<?php
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<prospects>
    <?php
    foreach ($data['prospects'] as $prospect) {
        try {
            $person = org_openpsa_contacts_person_dba::get_cached($prospect->person);
        } catch (midcom_error $e) {
            continue;
        } ?>
        <person>
            <guid>&(person.guid);</guid>
            <prospect>&(prospect.guid);</prospect>
            <label>&(person.name);</label>
        </person>
        <?php

    }
    ?>
</prospects>