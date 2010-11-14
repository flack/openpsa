<h2><?php $data['l10n']->show('select an account type'); ?></h2>

<ul>
<?php foreach ($data['types'] as $url => $name) { ?>
    <li><a href="&(url);">&(name);</a></li>
<?php } ?>
</ul>