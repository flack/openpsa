<?php
$preview = $data['preview'];
?>
<h1>&(data['view_title']:h);</h1>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<dl>
<?php
foreach ($preview as $attribute => $value) {
    if ($value == '') {
        continue;
    }

    if ($value == '0000-00-00') {
        continue;
    }

    if (!midcom_services_rcs::is_field_showable($attribute)) {
        continue;
    }

    if (is_array($value)) {
        continue;
    }

    // Three fold fallback in localization
    echo "<dt>". $data['handler']->translate($attribute) ."</dt>\n";
    echo "    <dd>" . nl2br($value) . "</dd>\n";
}
?>
</dl>
