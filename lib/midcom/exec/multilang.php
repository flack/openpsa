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

<h1>Multilang</h1>
<p>Syncing the site tree in this language...<p>

<?php

midcom_services_multilang::tree($topic);

?>

<p>Done.</p>

<h2>Notes</h2>

<h3>Subtree support</h3>
<p>If you want to sync only certain subtree, give its guid/id as GET parameter: 'topic'.</p>

<h3>multilangs.php</h3>
<p>If you have the same workflow languages in all language hosts, you can run
/midcom-exec-midcom/multilangs.php which will sync the site tree in all
languages in one go - and in correct order.</p>
<p>If you have workflow languages included which aren't actually accessible,
the multilangs.php script will also handle those languages properly. Also if
you remove languages from the workflow or disable a workflow only the
multilangs.php script handles them.</p>
<p>So use it if you can (you have the same language configuration in all
hosts).</p>
