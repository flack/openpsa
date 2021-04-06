<?php
echo "<h1>" . $data['l10n']->get('urlmethods') . "</h1>\n";
if (!empty($data['urlmethods'])) {
    $first = true;
    foreach ($data['urlmethods'] as $method_info) {
        $id = basename($method_info['url'], ".php");
        $title = $method_info['url']; ?>
<fieldset id="handler_&(id);">
    <legend onclick="javascript:toggle_twisty('&(id);_contents')">
        &(title);
        <img class="twisty" src="<?php echo MIDCOM_STATIC_URL; ?>/midcom.admin.styleeditor/twisty-<?php echo ($first) ? 'down' : 'hidden'; ?>.gif" alt="-" />
    </legend>
    <div id="&(id);_contents" style="display: <?php echo ($first) ? 'block' : 'none'; ?>;" class="description">
<?php
        $first = false;
        echo "<p>\n";
        echo $method_info['description'] ?? '';
        echo "</p>\n"; ?>
    </div>
</fieldset>
<?php

    }
} else {
    echo "<p>" . $data['l10n']->get('no url methods found') . "</p>";
}
?>