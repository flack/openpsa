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
use midcom_services_indexer;
use midcom_helper__componentloader;
use midcom_error;
use midcom_helper_nav;
use midcom_services_indexer_client;
use Symfony\Component\Console\Input\InputOption;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use midcom\console\loginhelper;
use Symfony\Component\Console\Attribute\AsCommand;

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
#[AsCommand(
    name: 'midcom:reindex',
    description: 'Reindex',
    aliases: ['reindex']
)]
class reindex extends Command
{
    use loginhelper;

    private midcom_services_indexer $indexer;

    private midcom_helper__componentloader $loader;

    public function __construct(midcom_services_indexer $indexer, midcom_helper__componentloader $loader)
    {
        $this->indexer = $indexer;
        $this->loader = $loader;
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'Start node (root if empty)');
    }

    protected function interact(InputInterface $input, OutputInterface $output) : void
    {
        if (!$this->indexer->enabled()) {
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
            if (!$this->indexer->delete_all("__TOPIC_GUID:{$node[MIDCOM_NAV_OBJECT]->guid}")) {
                $output->writeln("\n<error>Failed to remove documents from index.</error>");
            }
            $interface = $this->loader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);
            $stat = $interface->reindex($node[MIDCOM_NAV_OBJECT]);
            if ($stat instanceof midcom_services_indexer_client) {
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
        return Command::SUCCESS;
    }
}
