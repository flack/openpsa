<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
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
        $unsubscribe_code = '<a href="' . $prefix . 'campaign/unsubscribe/' . $data['membership']->guid .'/" target="_blank"><i class="fa fa-trash delete"></i></a>';
        break;
}
echo "<dt class=\"{$class}\"><a href=\"{$prefix}campaign/{$data['campaign']->guid}/\">{$data['campaign']->title}</a>{$unsubscribe_code}</dt>\n";
echo "    <dd class=\"description\">{$data['campaign']->description}</dd>\n";
