<div class="object_edit">
<?php
$data['controller']->display_form();
?>
</div>
<?php
if (!empty($data['code']))
{ ?>
    <h3><?php echo $data['l10n']->get('script output'); ?></h3>
    <pre id="shell-output"><?php
    midcom::get('cache')->content->enable_live_mode();
    while (@ob_end_flush());
    ob_implicit_flush(true);
    try
    {
        ?>&(data['code']:p);<?php
    }
    catch (Exception $e)
    {
        echo '<div class="shell-error">' . $e->getMessage() . '</div>';
        echo htmlentities($e->getTraceAsString());
    }
    ob_implicit_flush(false);
    ?></pre>
<?php }
?>
