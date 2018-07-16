<?php
$view_url = $data['router']->generate('view_campaign', ['guid' => $data['campaign']->guid]);
$unsubscribe_code = '';

switch ($data['membership']->orgOpenpsaObtype) {
    case org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED:
        $class = 'unsubscribed';
        break;
    //This is unnecessary for now as we filter testers out earlier but in the future it might be needed
    case org_openpsa_directmarketing_campaign_member_dba::TESTER:
        $class = 'tester';
        break;
    default:
        $class = 'member';
        $unsubscribe_url = $data['router']->generate('subscriber_unsubscribe', ['member' => $data['membership']->guid]);
        $unsubscribe_code = '<a href="' . $unsubscribe_url . '" target="_blank"><i class="fa fa-trash delete"></i></a>';
        break;
}
echo "<dt class=\"{$class}\"><a href=\"{$view_url}\">{$data['campaign']->title}</a>{$unsubscribe_code}</dt>\n";
echo "    <dd class=\"description\">{$data['campaign']->description}</dd>\n";
