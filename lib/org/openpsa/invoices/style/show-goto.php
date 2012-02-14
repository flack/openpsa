<div class="sidebar">
    <?php
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
    ?>
    <div class="area">
        <form method="get" action="<?php echo $prefix;?>goto/">
            <input type="text" name="query" value="<?php echo $data['l10n']->get('search title'); ?>" onclick="javascript:this.value='';" />
            <input style="display:none" type="submit" value="<?php echo $data['l10n']->get('goto'); ?>" />
        </form>
    </div>
</div>
<br style="clear: right" />