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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use midcom\console\loginhelper;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Purge deleted objects
 *
 * @package midcom.console
 */
#[AsCommand(
    name: 'midcom:purgedeleted',
    description: 'Purge deleted objects',
    aliases: ['purgedeleted']
)]
class purgedeleted extends Command
{
    use loginhelper;

    protected function configure() : void
    {
        $config = new \midcom_config;
        $this->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Grace period in days', $config->get('cron_purge_deleted_after'));
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $handler = new \midcom_cron_purgedeleted;
        $handler->set_cutoff((int) $input->getOption('days'));
        \midcom::get()->auth->request_sudo('midcom.core');

        $output->writeln('Purging entries deleted before ' . gmdate('Y-m-d H:i:s', $handler->get_cutoff()));

        $total_purged = 0;
        $total_errors = 0;
        $start = microtime(true);
        foreach ($handler->get_classes() as $mgdschema) {
            $output->writeln("\n\nProcessing class <info>{$mgdschema}</info>");
            $errors = 0;
            $stats = $handler->process_class($mgdschema);
            foreach ($stats['errors'] as $error) {
                $output->writeln('  <error>' . $error . '</error>');
                $errors++;
            }
            $output->write("  Purged <info>{$stats['purged']}</info> deleted objects, <comment>" . $errors . " failures</comment>");
            $total_purged += $stats['purged'];
            $total_errors += $errors;
        }
        $elapsed = round(microtime(true) - $start, 2);
        $output->writeln("\n\nPurged <info>{$total_purged}</info> deleted objects in {$elapsed}s, <comment>" . $total_errors . " failures</comment>");
        return Command::SUCCESS;
    }
}
