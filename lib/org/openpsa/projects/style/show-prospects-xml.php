<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<prospects>
    <?php
    foreach ($data['prospects'] as $prospect)
    {
        $person = org_openpsa_contacts_person_dba::get_cached($prospect->person);
        ?>
        <person>
            <guid>&(person.guid);</guid>
            <prospect>&(prospect.guid);</prospect>
            <label>&(person.name);</label>
        </person>
        <?php
    }
    ?>
</prospects>