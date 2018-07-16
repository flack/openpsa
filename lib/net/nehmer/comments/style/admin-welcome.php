<?php
$data['l10n']->show('from below you can choose what kind of comments you like to administrate');
?>
<ul>
    <li><a href="<?php echo $data['router']->generate('moderate', ['status' => 'reported_abuse']); ?>"><?php $data['l10n']->show('reported abuses'); ?></a></li>
    <li><a href="<?php echo $data['router']->generate('moderate', ['status' => 'abuse']); ?>"><?php $data['l10n']->show('abuses'); ?></a></li>
    <li><a href="<?php echo $data['router']->generate('moderate', ['status' => 'junk']); ?>"><?php $data['l10n']->show('junks'); ?></a></li>
    <li><a href="<?php echo $data['router']->generate('moderate', ['status' => 'latest']); ?>"><?php $data['l10n']->show('latest comments'); ?></a>
        <ul>
            <li><a href="<?php echo $data['router']->generate('moderate', ['status' => 'latest_new']); ?>"><?php $data['l10n']->show('only new'); ?></a></li>
            <li><a href="<?php echo $data['router']->generate('moderate', ['status' => 'latest_approved']); ?>"><?php $data['l10n']->show('only approved'); ?></a></li>
        </ul>
    </li>
</ul>