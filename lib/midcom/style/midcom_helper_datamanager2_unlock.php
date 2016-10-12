<?php
$metadata = $this->data['handler']->datamanager->storage->object->metadata;
$formatter = $data['l10n']->get_formatter();
try
{
    $person = new midcom_db_person($metadata->locker);
    $name = $person->name;
}
catch (midcom_error $e)
{
    $name = $this->data['handler']->_l10n_midcom->get('unknown user');
    $e->log();
}
?>
    <div class="midcom_helper_datamanager2_unlock">
        <h2><?php echo $this->data['handler']->_l10n->get('object locked'); ?></h2>
        <p>
            <?php printf($this->data['handler']->_l10n->get('this object was locked by %s'), $name); ?>.
            <?php printf($this->data['handler']->_l10n->get('lock will expire on %s'), $formatter->datetime(($metadata->get('locked') + (midcom::get()->config->get('metadata_lock_timeout') * 60)))); ?>.
        </p>
<?php
if ($metadata->can_unlock())
{
    echo "<form method=\"post\">\n";
    echo "    <p class=\"unlock\">\n";
    echo "        <input type=\"hidden\" name=\"midcom_helper_datamanager2_object\" value=\"{$this->data['handler']->datamanager->storage->object->guid}\" />\n";
    echo "        <input type=\"submit\" name=\"midcom_helper_datamanager2_unlock\" value=\"" . $this->data['handler']->_l10n->get('break the lock') . "\" class=\"unlock\" />\n";
    echo "    </p>\n";
    echo "</form>\n";
}
?>
</div>