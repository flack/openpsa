<div class="expenses">
     <h2><?php echo $data['l10n']->get('this week'); ?></h2>
     <div id="content_expenses">

<?php
if (!empty($data['hours'])) {
    $invoiceable_hours = $data['hours']['total_invoiceable'];
    $uninvoiceable_hours = $data['hours']['total_uninvoiceable'];
    $total_hours = $invoiceable_hours + $uninvoiceable_hours;

    echo "<table class=\"hours\">\n";
    echo "    <tr>\n";
    echo "        <td>" . $data['l10n']->get('invoiceable') . "</td>\n";
    echo "        <td>" . round($invoiceable_hours, 2);
    $invoiceable_count = count($data['hours']['invoiceable']);
    if ($invoiceable_count > 0) {
        echo " (";
        $i = 1;
        foreach ($data['hours']['invoiceable'] as $customer_id => $hours) {
            echo $data['customers'][$customer_id] . " " . $hours;
            if ($i++ != $invoiceable_count) {
                echo ", ";
            }
        }
        echo " )";
    }
    echo "        </td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "        <td>" . $data['l10n']->get('uninvoiceable') . "</td>\n";
    echo "        <td>" . round($uninvoiceable_hours, 2);
    $uninvoiceable_count = count($data['hours']['invoiceable']);
    if ($uninvoiceable_count > 0) {
        echo " (";
        $i = 1;
        foreach ($data['hours']['uninvoiceable'] as $customer_id => $hours) {
            echo $data['customers'][$customer_id] . " " . $hours;
            if ($i++ != $uninvoiceable_count) {
                echo ", ";
            }
        }
        echo ") ";
    }
    echo "        </td>\n";
    echo "    </tr>\n";
    echo "</table>\n";
    echo "<form action=\"{$data['expenses_url']}\" method='post'><div>";
    $current_user = midcom::get()->auth->user->get_storage();
    echo "<input type=\"hidden\" name=\"person[]\" value=\"{$current_user->id}\" />";
    echo "<input type=\"submit\" value=\"".sprintf($data['l10n']->get('see all %s hours'), round($total_hours, 2))."\" />";
    echo "</div></form>";
} else {
    echo "<p><a href=\"{$data['expenses_url']}\">" . midcom::get()->i18n->get_string('report hours', 'org.openpsa.expenses') . "</a></p>\n";
}
?>
</div>
</div>
