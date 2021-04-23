<?php
midcom::get()->auth->require_valid_user();
?>
<h1>Merge tags</h1>

<?php
if (isset($_POST['from'], $_POST['to'])) {
    if (net_nemein_tag_handler::merge_tags($_POST['from'], $_POST['to'])) {
        echo "<p>Successfully merged tag \"{$_POST['from']}\" to \"{$_POST['to']}\"</p>\n";
    } else {
        echo "<p>Failed to merge tag \"{$_POST['from']}\" to \"{$_POST['to']}\"</p>\n";
    }
}
?>

<form method="post">
    <input name="from" value="from" />
    <input name="to" value="to" />
    <input type="submit" value="Merge" />
</form>