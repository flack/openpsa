<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
/* If we need to do any specific mangling to the query data before we use it,
   we do it here. */
$query =& $data['query_data'];

// Force grouping by person
$query['grouping'] = 'person';

// Limit the report to the week that it starts on
$start =& $query['start'];
$end =& $query['end'];
$daymod = 0;
switch (date('w', $start))
{
    case 0:
        $daymod = -6;
    break;
    default:
        $daymod = 1-date('w', $start);
        break;
}
$start = mktime(0, 0, 0, date('n', $start), date('j', $start)+$daymod, date('Y', $start));
$end = mktime(23, 59, 59, date('n', $start), date('j', $start)+6, date('Y', $start));

//echo "DEBUG: Hello World! " . date('Y-m-d H:i:s', $start) . " - " . date('Y-m-d H:i:s', $end) . "<br>\n";

?>