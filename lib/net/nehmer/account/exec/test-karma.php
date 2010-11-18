<?php
require_once(MIDCOM_ROOT . '/net/nehmer/account/calculator.php');
$_MIDCOM->auth->require_admin_user();

$calculator = new net_nehmer_account_calculator();

$cache = false;
if (   isset($_GET['cache'])
    && $_GET['cache'] == true)
{
    $cache = true;
}

$qb = midcom_db_person::new_query_builder();
$qb->add_order('metadata.revised', 'ASC');
$qb->set_limit((int) $GLOBALS['midcom_component_data']['net.nehmer.account']['config']->get('karma_calculate_per_hour'));
$persons = $qb->execute();
$persons_array = array();

foreach ($persons as $person)
{
    $karmas = $calculator->calculate_person($person, $cache);
    $karma_string = '';
    foreach ($karmas as $source => $karma)
    {
        $karma_string .= " {$source}: {$karma}";
    }
    $karma_string = trim($karma_string);
    $person->tmp = $karma_string;
    $persons_array[sprintf('%003d', $karmas['karma'])."_{$person->guid}"] = $person;
}
echo count($persons_array) . " persons processed.";

krsort($persons_array);
echo "<ul>\n";
foreach ($persons_array as $person)
{
    echo "<li><a href=\"{$person->homepage}\">{$person->name}</a> ({$person->tmp})</li>\n";
}
echo "</ul>\n";
?>