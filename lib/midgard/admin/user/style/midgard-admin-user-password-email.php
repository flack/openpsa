<h1>&(data['view_title']:h);</h1>
<p><?php echo $data['l10n']->get('write the message that will be sent to the recipients'); ?></p>

<label for="midgard_admin_user_email_subject">
    <?php echo $data['l10n']->get('subject'); ?>
</label>
<input type="text" name="subject" value="&(data['message_subject']:h);" id="midgard_admin_user_email_subject" />
<label for="midgard_admin_user_email_body">
    <?php echo $data['l10n']->get('message'); ?>
</label>
<textarea name="body" id="midgard_admin_user_email_body">&(data['message_body']:h);&(data['message_footer']:h);</textarea>
<table>
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get('variable'); ?></th>
            <th><?php echo $data['l10n']->get('personalized equal'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php
foreach ($data['variables'] as $variable => $definition) {
    echo "        <tr>\n";
    echo "            <td><code>{$variable}</code></td>\n";
    echo "            <td>{$definition}</td>\n";
    echo "        </tr>\n";
}
?>
    </tbody>
</table>
