<table>
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get('user'); ?></th>
            <?php
            if (isset($data['category']))
            {
                echo "<th>{$data['category']}</th>\n";
            }
            ?>
            <th><?php echo $data['l10n']->get('karma'); ?></th>
        </tr>
    </thead>
    <tbody>