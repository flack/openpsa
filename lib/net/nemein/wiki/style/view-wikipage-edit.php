<h1>&(data['view_title']:h);</h1>

<?php
if ($data['preview_mode'])
{
    echo "<div class=\"wiki_preview\">\n";
    midcom_show_style('view-wikipage');
    echo "</div>\n";
}

$data['controller']->display_form();
?>