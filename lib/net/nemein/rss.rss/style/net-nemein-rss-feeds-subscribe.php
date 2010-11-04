<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<h1><?php echo sprintf($_MIDCOM->i18n->get_string('subscribe feeds for %s', 'net.nemein.rss'), $data['folder']->extra); ?></h1>

<form method="post" class="datamanager" enctype="multipart/form-data">

    <fieldset>
        <legend>
            <?php echo $_MIDCOM->i18n->get_string('add new feed', 'net.nemein.rss'); ?>
        </legend>

        <label>
            <span>
                <?php echo $_MIDCOM->i18n->get_string('feed url', 'net.nemein.rss'); ?>
            </span>
            <input class="shorttext" type="text" name="net_nemein_rss_manage_newfeed[url]" />  
        </label>
    </fieldset>

    <fieldset>
        <legend>
            <?php echo $_MIDCOM->i18n->get_string('import opml subscriptions', 'net.nemein.rss'); ?>
        </legend>

        <label>
            <span>
                <?php echo $_MIDCOM->i18n->get_string('opml file', 'net.nemein.rss'); ?>
            </span>
            <input type="file" class="fileselector" name="net_nemein_rss_manage_opml" />
        </label>
    </fieldset>

    <div class="form_toolbar">
        <input type="submit" class="save" accesskey="s" name="net_nemein_rss_manage_submit" value="<?php echo $_MIDCOM->i18n->get_string('save', 'midcom'); ?>" />
        <input type="submit" class="cancel" accesskey="c" name="net_nemein_rss_manage_cancel" value="<?php echo $_MIDCOM->i18n->get_string('cancel', 'midcom'); ?>" />
    </div>
</form>