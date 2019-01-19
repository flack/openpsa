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

/**
 * Purge deleted objects
 *
 * @package midcom.console
 */
class purgedeleted extends Command
{
    use loginhelper;

    protected function configure()
    {
        $config = new \midcom_config;
        $this->setName('midcom:purgedeleted')
            ->setAliases(['purgedeleted'])
            ->setDescription('Purge deleted objects')
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Grace period in days', $config->get('cron_purge_deleted_after'))
            ->addOption('chunksize', 'c', InputOption::VALUE_REQUIRED, 'Maximum number of objects purged per class at once (use 0 to disable limit)', '50');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('question');
        $this->require_admin($dialog, $input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $handler = new \midcom_cron_purgedeleted;
        $handler->set_cutoff((int) $input->getOption('days'));
        $chunk_size = (int) $input->getOption('chunksize');

        $output->writeln('Purging entries deleted before ' . gmdate('Y-m-d H:i:s', $handler->get_cutoff()));

        $total_purged = 0;
        $total_errors = 0;
        $start = microtime(true);
        foreach ($handler->get_classes() as $mgdschema) {
            $output->writeln("\n\nProcessing class <info>{$mgdschema}</info>");
            $purged = 0;
            $errors = 0;
            do {
                $stats = $handler->process_class($mgdschema, $chunk_size);
                foreach ($stats['errors'] as $error) {
                    $output->writeln('  <error>' . $error . '</error>');
                }
                if ($purged > 0) {
                    $output->write("\x0D");
                }
                $purged += $stats['purged'];
                $errors += count($stats['errors']);
                $output->write("  Purged <info>{$purged}</info> deleted objects, <comment>" . $errors . " failures</comment>");
            } while ($stats['found'] == $chunk_size);
            $total_purged += $purged;
            $total_errors += $errors;
        }
        $elapsed = round(microtime(true) - $start, 2);
        $output->writeln("\n\nPurged <info>{$total_purged}</info> deleted objects in {$elapsed}s, <comment>" . $total_errors . " failures</comment>");
    }
}
