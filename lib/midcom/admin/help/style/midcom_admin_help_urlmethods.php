<?php
echo "<h1>" . $data['l10n']->get('urlmethods') . "</h1>\n";
if (!empty($data['urlmethods'])) {
    $first = true;
    foreach ($data['urlmethods'] as $method_info) {
        $id = basename($method_info['url'], ".php");
        $title = $method_info['url']; ?>
<fieldset id="handler_&(id);" class="<?php echo ($first) ? 'open' : 'closed'; ?>">
    <legend onclick="toggle_twisty('&(id);_contents')">
        &(title);
        <i class="fa fa-caret-<?php echo ($first) ? 'up' : 'down'; ?>"></i>
    </legend>
    <div id="&(id);_contents" class="description">
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