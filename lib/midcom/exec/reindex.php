<?php
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Reindex script.
 *
 * Iterates through all nodes and calls reindex_singlenode.php for each of them in the background.
 *
 * This may take some time.
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

if (midcom::get()->config->get('indexer_backend') === false) {
    throw new midcom_error('No indexer backend has been defined. Aborting.');
}

$ip_sudo = midcom::get()->auth->require_admin_or_ip('midcom.services.indexer');

//check if language is passed - if not take the current-one
$language = midcom::get()->i18n->get_current_language();
if (isset($_REQUEST['language'])) {
    $language = $_REQUEST['language'];
}

debug_add('Disabling script abort through client.');
ignore_user_abort(true);
ob_implicit_flush();
midcom::get()->disable_limits();
$start = microtime(true);

$nap = new midcom_helper_nav();
$nodes = [];

// Use this to check that indexer is online (and hope the root topic isn't a gigantic wiki)
$root_node = $nap->get_node($nap->get_root_node());
midcom::get()->indexer->query("__TOPIC_GUID:{$root_node[MIDCOM_NAV_OBJECT]->guid}");
$node = $root_node;

echo "<pre>\n";

$reindex_topic_uri = midcom::get()->get_page_prefix() . 'midcom-exec-midcom/reindex_singlenode.php';

$options = ['timeout' => 300];
if (   !empty($_SERVER['PHP_AUTH_USER'])
    && !empty($_SERVER['PHP_AUTH_PW'])) {
    $options['auth'] = ['user' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']];
}
$client = new Client($options);

while ($node !== null) {
    // Reindex the node...
    echo "Processing node #{$node[MIDCOM_NAV_ID]}, {$node[MIDCOM_NAV_FULLURL]}: ";
    $uri = $reindex_topic_uri . "?" . http_build_query(['nodeid' => $node[MIDCOM_NAV_ID], 'language' => $language]);
    $client->getAsync($uri)
        ->then(function(ResponseInterface $response) use ($uri) {
            if (!preg_match("#(\n|\r\n)Reindex complete for node http.*\s*</pre>\s*$#", $response->getBody())) {
                echo "failure.\n   Background reindex returned unexpected data:\n---\n{$response->getBody()}\n---\n";
                echo "Url: " . $uri . "\n\n";
            } else {
                echo "OK.\n";
            }
        }, function (RequestException $e) use ($uri) {
            echo "failure.\n   Background processing failed, error: {$e->getMessage()}\n";
            echo "Url: " . $uri . "\n";
        })->wait();

    // Retrieve all child nodes and append them to $nodes:
    $nodes = array_merge($nodes, $nap->get_nodes($node[MIDCOM_NAV_ID]));
    $node = array_shift($nodes);
}

if ($ip_sudo) {
    midcom::get()->auth->drop_sudo();
}
?>

Reindex complete. Time elapsed: <?php echo round(microtime(true) - $start, 2) . 's'; ?>
</pre>
