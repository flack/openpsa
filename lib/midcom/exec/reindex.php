<?php
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

$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_root_node());
$nodes = $nodedata = [];

while ($node !== null) {
    $nodedata[$node[MIDCOM_NAV_ID]] = $node[MIDCOM_NAV_FULLURL];
    // Retrieve all child nodes and append them to $nodes:
    $nodes = array_merge($nodes, $nap->get_nodes($node[MIDCOM_NAV_ID]));
    $node = array_shift($nodes);
}
?>
<pre id="output"></pre>
<script>

    async function process_nodes() {
        let start = new Date(),
            endpoint = '<?= midcom::get()->get_page_prefix() ?>midcom-exec-midcom/reindex_singlenode.php',
            language = '<?= $_REQUEST['language'] ?? midcom::get()->i18n->get_current_language() ?>',
            nodes = <?= json_encode($nodedata)?>,
            output = document.getElementById('output'),
            url, response, body;

        for (const [id, node_url] of Object.entries(nodes)) {
            output.textContent += "Processing node #" + id + ", " + node_url + ": ";
            url = endpoint + '?nodeid=' + id + '&language=' + language;
            response = await fetch(url);
            body = await response.text();
            let parser = new DOMParser(),
                parsed = parser.parseFromString(body, 'text/html');
            if (!response.ok) {
                output.textContent += "failure.\n   Background processing failed, error: " + parsed.getElementsByTagName('body')[0].innerHTML + "\n";
                output.textContent += "URL: " + url + "\n";
            } else {
                if (!body.match(/(\n|\r\n)Reindex complete for node http.*\s*<\/pre>\s*$/)) {
                    output.textContent += "failure.\n   Background reindex returned unexpected data:\n---\n" + parsed.getElementsByTagName('body')[0].innerHTML + "\n---\n";
                    output.textContent += "URL: " + url + "\n";
                } else {
                    output.textContent += "OK.\n";
                }
            }
        }
        let end = new Date(),
            duration = Math.round((end.getTime() - start.getTime()) / 10) / 100;

        output.textContent += "\nReindex complete. Time elapsed: " + duration + "s";
    }

    process_nodes();

</script>
