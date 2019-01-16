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
use Symfony\Component\Console\Question\Question;
use midcom;
use midcom_error;
use midcom_helper_nav;
use midcom_services_indexer_client;

/**
 * Redinex command
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
    protected function configure()
    {
        $this->setName('midcom:reindex')
            ->setDescription('Reindex');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty($input->getParameterOption(['--servername', '-s'], null))) {
            throw new midcom_error('Please specify host name (with --servername or -s)');
        }
        if (midcom::get()->config->get('indexer_backend') === false) {
            throw new midcom_error('No indexer backend has been defined. Aborting.');
        }

        $dialog = $this->getHelperSet()->get('question');
        $username = $dialog->ask($input, $output, new Question('<question>Username:</question> '));
        $pw_question = new Question('<question>Password:</question> ');
        $pw_question->setHidden(true);
        $pw_question->setHiddenFallback(false);
        $password = $dialog->ask($input, $output, $pw_question);
        if (!midcom::get()->auth->login($username, $password)) {
            throw new \RuntimeException('Login failed');
        }
        midcom::get()->auth->require_admin_user();

        $nap = new midcom_helper_nav();
        $nodes = [];
        $nodeid = $nap->get_root_node();

        $output->writeln('Dropping the index...');
        midcom::get()->indexer->delete_all();

        while ($nodeid !== null) {
            // Reindex the node...
            $node = $nap->get_node($nodeid);

            $output->writeln("Processing Node {$node[MIDCOM_NAV_FULLURL]}...");
            $interface = midcom::get()->componentloader->get_interface_class($node[MIDCOM_NAV_COMPONENT]);
            $stat = $interface->reindex($node[MIDCOM_NAV_OBJECT]);
            if (is_a($stat, midcom_services_indexer_client::class)) {
                $stat->reindex();
            } elseif ($stat === false) {
                $output->writeln("<error>Failed to reindex the node {$nodeid} which is of {$node[MIDCOM_NAV_COMPONENT]}.</error>");
            }

            // Retrieve all child nodes and append them to $nodes:
            $children = $nap->list_nodes($nodeid);
            if ($children === false) {
                throw new midcom_error("Failed to list the child nodes of {$nodeid}.");
            }
            $nodes = array_merge($nodes, $children);
            $nodeid = array_shift($nodes);
        }

        $output->writeln('Reindex complete');
    }
}
