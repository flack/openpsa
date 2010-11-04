<?php

$_MIDCOM->auth->require_valid_user('basic');
$_MIDCOM->auth->require_admin_user();

$topic = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ROOTTOPIC);
if (!$topic || !$topic->guid)
{
    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to get root topic. Aborting.');
}

if (isset($_REQUEST['topic']))
{
    try
    {
        $topic = new midgard_topic($_REQUEST['topic']);
    }
    catch (Exception $e)
    {
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to get topic {$_REQUEST['topic']}. Aborting.");
    }
}

?>

<h1>Multilangs</h1>
<p>Syncing the site tree in all languages...<p>

<?php

$langs = array();

if ($GLOBALS['midcom_config']['multilang_lang0_langs'])
{
    $langs = array_merge($langs, $GLOBALS['midcom_config']['multilang_lang0_langs']);
}
if ($GLOBALS['midcom_config']['multilang_auto_langs'])
{
    $langs = array_merge($langs, $GLOBALS['midcom_config']['multilang_auto_langs']);
}

$real_lang = midcom_services_multilang::get_lang();
$synced = array();
while ($lang = array_pop($langs))
{
    if (isset($synced[$lang])) continue;
    midgard_connection::set_lang($lang);
    midcom_services_multilang::tree($topic);
    $synced[$lang] = true;
}
midgard_connection::set_lang($real_lang);

midcom_services_multilang::tree($topic, false);

?>

<p>Done.</p>

<h2>Notes</h2>

<h3>Subtree support</h3>
<p>If you want to sync only certain subtree, give its guid/id as GET parameter: 'topic'.</p>

<h3>multilang.php</h3>
<p>If you don't have the same workflow languages in all language hosts, you
should run /midcom-exec-midcom/multilang.php which will sync the site tree in
the host language only.</p>
