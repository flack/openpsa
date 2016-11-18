<?php
// Available request data: comments.
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<?php
$data['l10n']->show('from below you can choose what kind of comments you like to administrate');
?>
<ul>
    <li><a href="&(prefix);moderate/reported_abuse/"><?php $data['l10n']->show('reported abuses'); ?></a></li>
    <li><a href="&(prefix);moderate/abuse/"><?php $data['l10n']->show('abuses'); ?></a></li>
    <li><a href="&(prefix);moderate/junk/"><?php $data['l10n']->show('junks'); ?></a></li>
    <li><a href="&(prefix);moderate/latest/"><?php $data['l10n']->show('latest comments'); ?></a></li>
    <ul>
        <li><a href="&(prefix);moderate/latest_new/"><?php $data['l10n']->show('only new'); ?></a></li>
        <li><a href="&(prefix);moderate/latest_approved/"><?php $data['l10n']->show('only approved'); ?></a></li>
    </ul>
</ul>