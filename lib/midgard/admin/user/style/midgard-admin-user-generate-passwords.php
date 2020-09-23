<form method="get" action="<?php echo midcom_connection::get_url('uri'); ?>?f_submit" id="midgard_admin_user_generated_passwords_form">
    <label for="amount">
        <span class="label"><?php echo $data['l10n']->get('amount'); ?></span>
        <input type="number" name="n" id="amount" value="<?php echo $data['n']; ?>" size="3" min="1" max="<?php echo $data['max_amount']; ?>" required />
        (<?php printf($data['l10n']->get('maximum %s'), $data['max_amount']); ?>)
    </label>
    <label for="length">
        <span class="label"><?php echo $data['l10n']->get('password length'); ?></span>
        <input type="number" name="length" id="length" value="<?php echo $data['length']; ?>" size="2" min="1" max="<?php echo $data['max_length']; ?>" required />
        (<?php printf($data['l10n']->get('maximum %s'), $data['max_length']); ?>)
    </label>
    <label for="similar_characters">
        <input type="checkbox" id="similar_characters" name="no_similars" value="1" <?php if ($data['no_similars']) {
            echo ' checked="checked"';
        } ?> />
        <span class="label"><?php echo $data['l10n']->get('prevent similar characters'); ?> (<em>I, l, 1, 0, O</em>)</span>
    </label>
    <input type="submit" value="<?php echo $data['l10n']->get('generate'); ?>" />
</form>
<pre id="midgard_admin_user_generated_passwords_random">
</pre>
<script type="text/javascript">
var form = $('#midgard_admin_user_generated_passwords_form');
form.on('submit', function(e) {
    e.preventDefault();
    $.get(form.attr('action'), form.serialize(), function(data) {
        $('#midgard_admin_user_generated_passwords_random').html(data);
    });
});
</script>
