<div class="main">
    <h1><?php printf($data['l10n']->get('import subscribers to "%s"'), $data['campaign']->title); ?></h1>

    <table>
        <thead>
            <tr>
                <th><?php echo $data['l10n']->get('status'); ?></th>
                <th><?php echo $data['l10n']->get('contacts'); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php
            foreach ($data['import_status'] as $status => $count)
            {
                echo "<tr>\n";
                echo "<td>" . $data['l10n']->get($status) . "</td>\n";
                echo "<td>{$count}</td>\n";
                echo "</tr>\n";
            }
            ?>
        </tbody>
    </table>

    <p>
        <?php
        printf($data['l10n']->get('import took %s seconds'), $data['time_end'] - $data['time_start']);
        ?>
    </p>
</div>