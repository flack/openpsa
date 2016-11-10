<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

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
            $unsubscribe_code = '<a href="' . $prefix . 'campaign/unsubscribe/' . $data['membership']->guid .'/" target="_blank"><img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png"/></a>';
            break;
    }
}
else
{
    $class = 'campaign';
    $unsubscribe_code = false;
}
echo "<dt class=\"{$class}\"><a href=\"{$prefix}campaign/{$data['campaign']->guid}/\">{$data['campaign']->title}</a>{$unsubscribe_code}</dt>\n";
echo "    <dd class=\"description\">{$data['campaign']->description}</dd>\n";