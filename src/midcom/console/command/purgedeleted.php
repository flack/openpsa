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

/**
 * Purge deleted objects
 *
 * @package midcom.console
 */
class purgedeleted extends Command
{
    protected function configure()
    {
        $this->setName('midcom:purgedeleted')
            ->setDescription('Purge deleted objects')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Grace period in days', '25')
            ->addOption('chunksize', null, InputOption::VALUE_REQUIRED, 'Maximum number of objects purged per class at once (use 0 to disable limit)', '500');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $username = $dialog->ask($output, '<question>Username:</question> ');
        $password = $dialog->askHiddenResponse($output, '<question>Password:</question> ', false);
        if (!\midcom::get('auth')->login($username, $password))
        {
            throw new \RuntimeException('Login failed');
        }
        \midcom::get('auth')->require_admin_user();

        $handler = new \midcom_cron_purgedeleted;
        $handler->set_cutoff((int) $input->getOption('days'));
        $chunk_size = (int) $input->getOption('chunksize');

        $output->writeln('Purging entries deleted before ' . gmdate('Y-m-d H:i:s', $handler->get_cutoff()));

        foreach ($handler->get_classes() as $mgdschema)
        {
            $output->writeln("\n\nProcessing class <info>{$mgdschema}</info>");
            $purged = 0;
            $errors = 0;
            do
            {
                $stats = $handler->process_class($mgdschema, $chunk_size);
                foreach ($stats['errors'] as $error)
                {
                    $output->writeln('  <error>' . $error . '</error>');
                }
                if ($purged > 0)
                {
                    $output->write("\x0D");
                }
                $purged += $stats['purged'];
                $errors += count($stats['errors']);
                $output->write("  Purged <info>{$purged}</info> deleted objects, <comment>" . $errors . " failures</comment>");
            }
            while ($stats['found'] == $chunk_size);
        }
        $output->writeln("\nDone.");
    }
}
