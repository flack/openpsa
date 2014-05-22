<p><?php echo $data['l10n']->get('event conflict'); ?></p>

<ul>
<?php
foreach ($data['conflictmanager']->busy_members as $uid => $events)
{
    ini_set('memory_limit', -1);
    echo '<li>' . org_openpsa_widgets_contact::get($uid)->show_inline();
    echo '<ul>';
    foreach ($events as $event)
    {
        echo '<li>' . $event->get_label() . '</li>';

    }
    echo '</li>';
    echo '</ul>';
}
?>
</ul>