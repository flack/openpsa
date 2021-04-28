<div class="expenses">
    <h2><?php echo $data['l10n']->get('this week'); ?></h2>
    <div id="content_expenses">

    <?php
    $total_hours = 0;

    echo "<table class=\"hours\">\n";
    foreach (['invoiceable', 'uninvoiceable'] as $type) {
        $total = $data['hours']['total_' . $type];
        $total_hours += $total;

        echo "    <tr>\n";
        echo "        <td>" . $data['l10n']->get($type) . "</td>\n";
        echo "        <td>" . round($total, 2);
        $count = count($data['hours'][$type]);
        if ($count > 0) {
            echo " (";
            $i = 1;
            foreach ($data['hours'][$type] as $customer_id => $hours) {
                echo $data['customers'][$customer_id];
                if ($count > 1) {
                    echo " " . $hours;
                }
                if ($i++ != $count) {
                    echo ", ";
                }
            }
            echo ") ";
        }
        echo "        </td>\n";
        echo "    </tr>\n";
    }

    echo "</table>\n";
    echo "<form action=\"{$data['expenses_url']}\" method='post'><div>";
    $current_user = midcom::get()->auth->user->get_storage();
    echo "<input type=\"hidden\" name=\"person[]\" value=\"{$current_user->id}\" />";
    echo "<input type=\"submit\" value=\"" . sprintf($data['l10n']->get('see all %s hours'), round($total_hours, 2)) . "\" />";
    echo "</div></form>";
    ?>
</div>
</div>
