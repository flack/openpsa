<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$page_var = $data['prefix'] . 'page';
$results_var =  $data['prefix'] . 'results';

echo '<div class="org_openpsa_qbpager_pages">';
$page = 0;
while ($page < $data['page_count'])
{
    $page++;
    if ($page == $data['current_page'])
    {
        echo "\n<span class=\"current_page\">{$page}</span>";
        continue;
    }
    echo "\n<a class=\"select_page\" href=\"?{$page_var}={$page}\">{$page}</a>";
}
echo "</div>\n";
//TODO: "showing results offset-offset+limit
?>