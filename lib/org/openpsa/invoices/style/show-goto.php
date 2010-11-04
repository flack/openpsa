<div class="sidebar">
    <?php
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
    ?>
    <div class="area">
        <form method="get" action="<?php echo $prefix;?>goto/">
            <input type="text" name="query" value="<?php echo $data['l10n']->get("go to invoice number"); ?>" onclick="javascript:this.value='';" />
            <input style="display:none" type="submit" value="<?php echo $data['l10n']->get("goto"); ?>" />
        </form>
    </div>
</div>
<br style="clear: right" />