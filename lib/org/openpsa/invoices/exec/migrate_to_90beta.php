<?php
set_time_limit(50000);
ini_set('memory_limit', "800M");

while(@ob_end_flush());
echo "<pre>\n";

/* TODO
 *
 * convert task relatedtos to item->task
 * convert deliverable relatedtos to item->deliverable
 */

echo "</pre>\n";
?>