<?php
$view = $data['view_tinyurl'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

foreach ($view as $key => $value) {
    if (!$value) {
        $view[$key] = '[ ' . $data['l10n']->get($data['l10n_midcom']->get($key)) . ' ]';
    }
}
?>
        <tr>
            <td>&(view['name']:h);</td>
            <td><a href="&(prefix);edit/<?php echo $data['tinyurl']->guid; ?>/">&(view['title']:h);</a></td>
            <td><?php echo nl2br($view['description']); ?></td>
            <td><a href="<?php echo $data['tinyurl']->url; ?>">&(view['url']:h);</a></td>
        </tr>
