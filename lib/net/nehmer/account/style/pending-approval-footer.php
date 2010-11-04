<?php
if (!isset($_POST['f_submit']))
{
?>
    <script type="text/javascript">
        // <![CDATA[
            function toggle_visibility(id)
            {
                var object = document.getElementById(id);
                
                if (!object)
                {
                    return false;
                }
                
                if (object.style.display == 'none')
                {
                    new Effect.BlindDown(id);
                }
                else
                {
                    new Effect.BlindUp(id);
                }
            }
        // ]]>
    </script>
    <div id="net_nehmer_account_rejected_message">
        <h2 onclick="javascript:toggle_twisty('net_nehmer_account_rejected_message_contents');">
            <?php echo $data['l10n']->get('custom message for rejected applicants'); ?>
            <img class="twisty" src="<?php echo MIDCOM_STATIC_URL; ?>/net.nehmer.account/twisty-hidden.gif" alt="-" />
        </h2>
        <p>
            <?php echo $data['l10n']->get('click on the title to edit the message'); ?>
        </p>
        <div id="net_nehmer_account_rejected_message_contents" style="display: none;">
            <div>
                <label for="net_nehmer_account_rejected_message_subject">
                    <span class="label">
                        <?php echo $data['l10n']->get('email subject'); ?>
                    </span>
                    <input type="text" name="subject" value="<?php echo $data['rejected_message_subject']; ?>" id="net_nehmer_account_rejected_message_subject" />
                </label>
                <label for="net_nehmer_account_rejected_message_body">
                    <span class="label">
                        <?php echo $data['l10n']->get('email message'); ?>
                    </span>
                    <textarea id="net_nehmer_account_rejected_message_body" name="body"><?php echo $data['rejected_message_body']; ?></textarea>
                </label>
            </div>
        </div>
    </div>
<?php
}
?>
    <div class="form_toolbar">
<?php
if (!isset($_POST['f_mass_reject']))
{
?>
        <input class="approve" type="submit" name="f_approve" value="<?php echo $data['l10n_midcom']->get('approve'); ?>" />
<?php
}

if (!isset($_POST['f_submit']))
{
?>
        <input class="reject" type="submit" name="f_reject" value="<?php echo $data['l10n']->get('reject'); ?>" />
<?php
}
?>
        <input class="cancel" type="submit" name="f_cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
    </div>
</form>
