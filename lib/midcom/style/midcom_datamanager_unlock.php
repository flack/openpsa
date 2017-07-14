<?php
$metadata = $this->data['handler']->get_datamanager()->get_storage()->get_value()->metadata;
$formatter = $data['l10n']->get_formatter();
$l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
$l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');

try {
    $person = new midcom_db_person($metadata->locker);
    $name = $person->name;
} catch (midcom_error $e) {
    $name = $l10n_midcom->get('unknown user');
    $e->log();
}
?>
    <div class="midcom_helper_datamanager2_unlock">
        <h2><?php echo $l10n->get('object locked'); ?></h2>
        <p>
            <?php printf($l10n->get('this object was locked by %s'), $name); ?>.
            <?php printf($l10n->get('lock will expire on %s'), $formatter->datetime(($metadata->get('locked') + (midcom::get()->config->get('metadata_lock_timeout') * 60)))); ?>.
        </p>
<?php
if ($metadata->can_unlock()) {
    echo "<form method=\"post\">\n";
    echo "    <p class=\"unlock\">\n";
    echo "        <input type=\"submit\" name=\"midcom_datamanager_unlock\" value=\"" . $l10n->get('break the lock') . "\" class=\"unlock\" />\n";
    echo "    </p>\n";
    echo "</form>\n";
}
?>
</div>