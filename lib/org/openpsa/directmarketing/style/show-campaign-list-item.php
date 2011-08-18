<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
if (array_key_exists('membership', $data))
{
    switch ($data['membership']->orgOpenpsaObtype)
    {
        case org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED:
            $class = 'unsubscribed';
            $unsubscribe_code = false;
            break;
        //This is unnecessary for now as we filter testers out earlier but in the future it might be needed
        case org_openpsa_directmarketing_campaign_member_dba::TESTER:
            $class = 'tester';
            $unsubscribe_code = false;
            break;
        default:
            $class = 'member';
            $unsubscribe_code = "<a href='{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe/{$data['membership']->guid}' class='target_blank'><img src='" . MIDCOM_STATIC_URL . "/stock-icons/16x16/trash.png' border=0/></a>";
            break;
    }
}
else
{
    $class = 'campaign';
    $unsubscribe_code = false;
}
echo "<dt class=\"{$class}\"><a href=\"{$node[MIDCOM_NAV_FULLURL]}campaign/{$data['campaign']->guid}/\">{$data['campaign']->title}</a>{$unsubscribe_code}</dt>\n";
echo "    <dd class=\"description\">{$data['campaign']->description}</dd>\n";
?>