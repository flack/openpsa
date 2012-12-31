<?php
midcom::get('auth')->require_admin_user();

if (   empty($_REQUEST['root_topic_guid'])
    || empty($_REQUEST['wiki_root'])
    || !is_dir($_REQUEST['wiki_root'])
    || !isset($_REQUEST['import_revisions']))
{
?>
<h1>Import from MoinMoin wiki</h1>
<form method="post">
    Folder to import from (/path/to/wiki/pages): <input type="text" name="wiki_root" /><br/>
    Import revisions:
        <select name="import_revisions">
            <option value="0">only current</option>
            <option value="1">all</option>
        </select><br/>
    Import to topic:
        <select name="root_topic_guid">
<?php
    $qb = midcom_db_topic::new_query_builder();
    $qb->add_constraint('component', '=', 'net.nemein.wiki');
    $qb->add_order('name', 'ASC');
    $wiki_topics = $qb->execute();
    foreach ($wiki_topics as $topic)
    {
        if (isset($topic->title))
        {
            $title =& $topic->title;
        }
        else
        {
            $title =& $topic->extra;
        }
        if (empty($title))
        {
            $title = $topic->name;
        }
        $label = "{$title} (TBD: /path/to)";
        echo "            <option value=\"{$topic->guid}\">{$label}</option>\n";
    }
?>
        </select>
    <input type="submit" value="Import" />
</form>
<?php
    return;
}

// TODO: ask for dir and topic
$wiki_root = $_REQUEST['wiki_root'];
$root_topic = midcom::get('dbfactory')->get_object_by_guid($_REQUEST['root_topic_guid']);
$import_revisions = $_REQUEST['import_revisions'];

midcom::get('cache')->content->enable_live_mode();
while(@ob_end_flush());
midcom::get()->disable_limits();

echo "<p>\n";

$worker = new net_nemein_wiki_importer_moinmoin();
$worker->wiki_root = $wiki_root;
$worker->import_revisions = $import_revisions;
$worker->root_topic = $root_topic;
$worker->import();
?>