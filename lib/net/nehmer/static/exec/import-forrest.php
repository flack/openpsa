<?php
midcom::get('auth')->require_admin_user();
// Get us to full live mode
midcom::get('cache')->content->enable_live_mode();
while(@ob_end_flush());
?>
<h1>Import content from Apache Forrest XML files</h1>
<?php
if (array_key_exists('directory', $_POST))
{
    $importer = new net_nehmer_static_import_forrest();
    $folder = $importer->list_files($_POST['directory']);

    echo "<pre>\n";
    $importer->import_folder($folder, $_POST['parent']);
    echo "</pre>\n";
}
else
{
    ?>
    <form method="post">
        <label>
            <span>Directory path for the files</span>
            <input type="text" name="directory" />
        </label>
        <label>
            <span>Parent folder</span>
            <select name="parent">
                <?php
                $qb = midcom_db_topic::new_query_builder();
                $qb->add_constraint('up', '=', 0);
                $folders = $qb->execute();
                foreach ($folders as $folder)
                {
                    echo "    <option value=\"{$folder->id}\">{$folder->name} ({$folder->extra})</option>\n";
                }
                ?>
            </select>
        </label>
        <div class="form_toolbar">
            <input type="submit" value="Import" />
        </div>
    </form>
    <?php
}
?>