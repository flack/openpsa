<?php
$n = 10;
$length = 8;
$max_amount = 100;
$max_length = 16;

$no_similars = !isset($_GET['f_submit']);

extract($_GET);

if (!isset($_GET['ajax-form'])) {
    ?>
        <form method="get" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midgard_admin_user_generated_passwords_form">
            <label for="amount">
                <span class="label"><?php echo $data['l10n']->get('amount'); ?></span> <input type="number" name="n" id="amount" value="<?php echo $n; ?>" size="3" min="1" max="<?php echo $max_amount; ?>" required /> (<?php printf($data['l10n']->get('maximum %s'), $max_amount); ?>)
            </label>
            <label for="length">
                <span class="label"><?php echo $data['l10n']->get('password length'); ?></span> <input type="number" name="length" id="length" value="<?php echo $length; ?>" size="2" min="1" max="<?php echo $max_length; ?>" required /> (<?php printf($data['l10n']->get('maximum %s'), $max_length); ?>)
            </label>
            <label for="similar_characters">
                <input type="checkbox" id="similar_characters" name="no_similars" value="1" <?php if ($no_similars) {
        echo ' checked="checked"';
    } ?> />
                <span class="label"><?php echo $data['l10n']->get('prevent similar characters'); ?> (<em>I, l, 1, 0, O</em>)</span>
            </label>
            <input type="submit" name="f_submit" value="<?php echo $data['l10n']->get('generate'); ?>" />
<?php
    if (isset($_GET['ajax'])) {
        $time = time();
        echo "    <input type=\"hidden\" name=\"timestamp\" value=\"{$time}\" />\n";
        echo "    <input type=\"hidden\" name=\"ajax\" value=\"true\" />\n";
        echo "    <input type=\"hidden\" name=\"ajax-form\" value=\"false\" />\n";
    } ?>
        </form>
    <pre id="midgard_admin_user_generated_passwords_random">
<?php

}

if (   !is_numeric($n)
    || $n <= 0
    || !is_numeric($length)
    || $length <= 0) {
    echo $data['l10n']->get('use positive numeric values');
} elseif ((int) $n > $max_amount
    || (int) $length > $max_length) {
    printf($data['l10n']->get('only up to %s passwords with maximum length of %s characters'), $max_amount, $max_length);
} else {
    for ($i = 0; $i < $n; $i++) {
        $password = midgard_admin_user_plugin::generate_password($length, $no_similars);

        echo "<input type=\"text\" class=\"plain-text\" value=\"{$password}\" onclick=\"this.select();\" />\n";
    }
}

if (!isset($_GET['ajax-form'])) {
    echo '</pre>';
    if (isset($_GET['ajax'])) {
        ?>
<script type="text/javascript">
    // <![CDATA[
    var form = $('#midgard_admin_user_generated_passwords_form');
    form.on('submit', function(e)
    {
        e.preventDefault();
        $.get(form.attr('action'), form.serialize(), function(data)
        {
            $('#midgard_admin_user_generated_passwords_random').html(data);
        });
    });
    // ]]>
</script>
<?php

    }
}
?>