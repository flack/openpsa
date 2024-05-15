<?php
/**
 * @package midcom.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\console\command\cleanup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Cleanup dangling parameters
 *
 * @package midcom.console
 */
class parameters extends Command
{
    protected function configure()
    {
        $this->setName('midcom:cleanup:parameters')
            ->setAliases(['parametercleanup'])
            ->setDescription('Cleanup dangling parameters')
            ->addOption('dry', 'd', InputOption::VALUE_NONE, 'If set, parameters will not be deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if ($dry = $input->getOption("dry")) {
            $output->writeln("<comment>Running in dry mode!</comment>");
        }

        $qb = \midgard_parameter::new_query_builder();
        $output->writeln("<comment>Checking parameters</comment>");

        $progress = new ProgressBar($output, $qb->count());
        $progress->setRedrawFrequency(100);
        $progress->start();

        $seen = [];
        $to_delete = [];
        foreach ($qb->iterate() as $param) {
            if (!array_key_exists($param->parentguid, $seen)) {
                try {
                    \midgard_object_class::get_object_by_guid($param->parentguid);
                    $seen[$param->parentguid] = true;
                } catch (\Exception) {
                    $seen[$param->parentguid] = false;
                }
            }
            if (!$seen[$param->parentguid]) {
                $to_delete[] = $param;
            }
            $progress->advance();
        }
        $progress->finish();

        $output->writeln("\nFound <info>" . count($to_delete) . "</info> dangling parameters");

        if (!$dry) {
            $output->writeln("<comment>Deleting parameters</comment>");
            $progress = new ProgressBar($output, count($to_delete));
            $progress->start();

            foreach ($to_delete as $param) {
                $param->delete();
                $progress->advance();
            }
            $progress->finish();
        }

        $output->writeln("\nDone");
        return Command::SUCCESS;
    }
}
