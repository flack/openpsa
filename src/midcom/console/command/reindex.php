<?php
/**
 * @package midcom.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\console\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use midcom;
use midcom_error;
use midcom_helper_nav;
use midcom_services_indexer_client;
use Symfony\Component\Console\Input\InputOption;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use midcom\console\loginhelper;

/**
 * Reindex command
 *
 * Drops the index, then iterates over all existing topics, retrieves the corresponding
 * interface class and invokes the reindexing.
 *
 * This may take some time.
 *
 * @package midcom.console
 */
class reindex extends Command
{
    use loginhelper;

    protected function configure()
    {
        $this->setName('midcom:reindex')
            ->setAliases(['reindex'])
            ->setDescription('Reindex')
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'Start node (root if empty)');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (midcom::get()->config->get('indexer_backend') === false) {
            throw new midcom_error('No indexer backend has been defined. Aborting.');
        }

        if (empty($input->getParameterOption(['--servername', '-s'], null))) {
            throw new midcom_error('Please specify host name (with --servername or -s)');
        }

        $dialog = $this->getHelperSet()->get('question');
        $this->require_admin($dialog, $input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $nap = new midcom_helper_nav();
        $nodes = [];
        $node = $nap->get_node((int) $input->getOption('id') ?: $nap->get_root_node());

        while ($node !== null) {
            // Reindex the node...
            $output->write("Processing Node #{$node[MIDCOM_NAV_ID]}, {$node[MIDCOM_NAV_FULLURL]}...");
            if (!midcom::get()->indexer->delete_all("__TOPIC_GUID:{$node[MIDCOM_NAV_OBJECT]->guid}")) {
                $output->writeln("\n<error>Failed to remove documents from index.</error>");
            }
            $interface = midcom::get()->componentloader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);
            $stat = $interface->reindex($node[MIDCOM_NAV_OBJECT]);
            if (is_a($stat, midcom_services_indexer_client::class)) {
                try {
                    $stat->reindex();
                } catch (RequestException $e) {
                    if ($e->hasResponse()) {
                        $crawler = new Crawler($e->getResponse()->getBody()->getContents());
                        $body = $crawler->filterXPath('//body')->html();
                        $output->writeln("\n<error>" . strip_tags($body) . '</error>');
                    } else {
                        $output->writeln("\n<error>" . $e->getMessage() . '</error>');
                    }
                }
            } elseif ($stat === false) {
                $output->writeln("\n<error>Failed to reindex the node {$node[MIDCOM_NAV_ID]} which is of {$node[MIDCOM_NAV_COMPONENT]}.</error>");
            }

            // Retrieve all child nodes and append them to $nodes:
            $nodes = array_merge($nodes, $nap->get_nodes($node[MIDCOM_NAV_ID]));
            $node = array_shift($nodes);
            $output->writeln("Done");
        }

        $output->writeln('Reindex complete');
        return 0;
    }
}
