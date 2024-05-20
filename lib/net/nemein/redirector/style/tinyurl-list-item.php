<?php
$view = $data['view_tinyurl'];
$edit_url = $data['router']->generate('edit', ['tinyurl' => $data['tinyurl']->guid]);

foreach ($view as $key => $value) {
    if (!$value) {
        $view[$key] = '[ ' . $data['l10n']->get($data['l10n_midcom']->get($key)) . ' ]';
    }
}
?>
        <tr>
            <td>&(view['name']:h);</td>
            <td><a href="<?= $edit_url; ?>">&(view['title']:h);</a></td>
            <td><?php echo nl2br($view['description']); ?></td>
            <td><a href="<?php echo $data['tinyurl']->url; ?>">&(view['url']:h);</a></td>
        </tr>
