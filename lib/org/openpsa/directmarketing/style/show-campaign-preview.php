<div>
<?php echo $data['l10n']->get("contacts found")." : ".count($data['preview_persons']);?>
</div>

<table class="list">
<thead >
<tr>
<th>
<?php echo $data['l10n']->get('lastname'); ?>
</th>
<th>
<?php echo $data['l10n']->get('firstname'); ?>
</th>
<th>
<?php echo $data['l10n']->get('email'); ?>
</th>
</tr>
</thead>
<tbody>

<?php
$siteconfig = org_openpsa_core_siteconfig::get_instance();
$url = $siteconfig->get_node_full_url('org.openpsa.contacts');
$url = $url . "person/";
$even = 'even';
$target = "class='target_blank'";

foreach ($data['preview_persons'] as $id => $person)
{
    echo "<tr class='".$even."'>";
    echo "<td><a ".$target." href='".$url.$person['guid']."/'>".$person['lastname']."</a></td>";
    echo "<td><a ".$target." href='".$url.$person['guid']."/'>".$person['firstname']."</a></td>";
    echo "<td><a ".$target." href='".$url.$person['guid']."/'>".$person['email']."</a></td>";
    echo "</tr>";
    if($even == 'even')
    {
        $even = 'odd';
    }
    else
    {
        $even = 'even';
    }
}
?>

</tbody>
</table>


