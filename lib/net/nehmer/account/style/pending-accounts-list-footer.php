    </table>
    <div class="form_toolbar">
        <input class="approve" type="submit" name="f_submit" value="<?php echo $data['l10n_midcom']->get('approve'); ?>" />
        <input class="reject" type="submit" name="f_mass_reject" value="<?php echo $data['l10n']->get('reject'); ?>" />
        <input class="cancel" type="submit" name="f_cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
    </div>
</form>

<script type="text/javascript">
    // <![CDATA[
        var image_path = "<?php echo MIDCOM_STATIC_URL; ?>/net.nehmer.account/";
        var image_up = "arrow-up.gif";
        var image_down = "arrow-down.gif";
        var image_none = "arrow-none.gif";
        
        jQuery(document).ready(function()
        {
            jQuery('#net_nehmer_account_pending_table').tablesorter(
            {
                headers: {0: {sorter: false}},
                widgets: ['column_highlight'],
                sortList: [[1,0]]
            });

            jQuery("#net_nehmer_account_pending_table tbody input[type='checkbox']").each(function(i)
            {
                jQuery(this).change(function()
                {
                    var object = this.parentNode;
                    var n = 0;
                    
                    while (!object.tagName.match(/tr/i))
                    {
                        object = object.parentNode;
                        
                        // Protect against infinite loops
                        if (n > 20)
                        {
                            return;
                        }
                    }
                    
                    if (jQuery(this).attr('checked'))
                    {
                        jQuery(object).addClass('row_selected');
                    }
                    else
                    {
                        jQuery(object).removeClass('row_selected');
                    }
                });
            });
        });
    // ]]>
</script>
