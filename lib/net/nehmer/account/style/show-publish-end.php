<?php
// Request Keys:
// datamanager, fields, schema, account, avatar, avatar_thumbnail, form_submit_name,
// processing_msg, profile_url, edit_url, avatar_url, avatar_thumbnail_url, onlinestate_checked
// account_published, account_revised
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
    <tr>
        <td style='border-top: 1px dashed black; padding-top: 1ex;'>
            <?php $data['l10n']->show('avatar'); ?>
        </td>
        <td style='border-top: 1px dashed black; padding-top: 1ex;'>
            <?php if ($data['avatar']) { ?>
                <a href="&(data['avatar_url']);"><img src="&(data['avatar_thumbnail_url']);" align='middle'/></a>
                <input type="submit" name="&(data['form_submit_name']);_delete_avatar" id="&(data['form_submit_name']);_delete_avatar"
                    value="<?php $data['l10n_midcom']->show('delete'); ?>" />
            <?php } else { ?>
                &nbsp;
            <?php } ?>
        </td>
        <td style='border-top: 1px dashed black; padding-top: 1ex;'>
            <input type="file" name="avatar" />
        </td>
    </tr>
    <tr>
        <td style='border-top: 1px dashed black; padding-top: 1ex;' colspan="2">
            <?php $data['l10n']->show('online state'); ?>
        </td>
        <td style='border-top: 1px dashed black; padding-top: 1ex;'>
            <input type="checkbox" name="onlinestate" &(data['onlinestate_checked']); />
        </td>
    </tr>
    <tr>
        <td colspan="3" style='border-top: 1px dashed black; padding-top: 1ex;'>
<?php
if ($data['processing_msg'])
{
?>
            &(data['processing_msg']);<br/>
<?php } ?>
            <input type="submit" name="&(data['form_submit_name']);" id="&(data['form_submit_name']);"
                value="<?php $data['l10n']->show('publish');?> " />
        </td>
    </tr>
</table>
</form>
