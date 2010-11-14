<?php
echo "</ul>\n";

if (   isset($data['products_qb'])
    && is_object($data['products_qb'])
    && method_exists($data['products_qb'], 'show_pages'))
{
    $data['products_qb']->show_pages();
}
?>