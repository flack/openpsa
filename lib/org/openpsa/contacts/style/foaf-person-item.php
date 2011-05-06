<?php
$person = $data['person'];
?>
<foaf:Person>
    <mgd:guid>&(person.guid);</mgd:guid>
    <mgd:id>&(person.id);</mgd:id>
    <foaf:firstName>&(person.firstname:h);</foaf:firstName>
    <foaf:lastName>&(person.lastname:h);</foaf:lastName>
</foaf:Person>