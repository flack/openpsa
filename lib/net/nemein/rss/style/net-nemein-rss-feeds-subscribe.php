<h1><?php printf($data['l10n']->get('subscribe feeds for %s'), $data['folder']->extra); ?></h1>

<form method="post" class="datamanager" enctype="multipart/form-data">

    <fieldset>
        <legend>
            <?php echo $data['l10n']->get('add new feed'); ?>
        </legend>

        <label>
            <span>
                <?php echo $data['l10n']->get('feed url'); ?>
            </span>
            <input class="shorttext" type="text" name="net_nemein_rss_manage_newfeed[url]" />
        </label>
    </fieldset>

    <fieldset>
        <legend>
            <?php echo $data['l10n']->get('import opml subscriptions'); ?>
        </legend>

        <label>
            <span>
                <?php echo $data['l10n']->get('opml file'); ?>
            </span>
            <input type="file" class="fileselector" name="net_nemein_rss_manage_opml" />
        </label>
    </fieldset>

    <div class="form_toolbar">
        <input type="submit" class="save" accesskey="s" name="net_nemein_rss_manage_submit" value="<?php echo $data['l10n_midcom']->get('save'); ?>" />
        <input type="submit" class="cancel" accesskey="c" name="net_nemein_rss_manage_cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
    </div>
</form>