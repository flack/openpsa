<h1>&(data['view_title']:h);</h1>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<dl>
<?php
foreach ($data['preview'] as $attribute => $value) {
    // Three fold fallback in localization
    echo "<dt>". $data['handler']->translate($attribute) ."</dt>\n";
    echo "    <dd>" . nl2br($value) . "</dd>\n";
}
?>
</dl>
