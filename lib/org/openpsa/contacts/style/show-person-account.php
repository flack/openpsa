<div class="area">
    <h2><?php echo $data['l10n']->get("user account"); ?></h2>
    <?php
    if ($data['person']->username)
    {
        echo "<p>{$data['person']->username}</p>";
    }
    else
    {
        echo '<p><span class="metadata">' . $data['l10n']->get("no account") . '</span></p>';
    }
    ?>
</div>