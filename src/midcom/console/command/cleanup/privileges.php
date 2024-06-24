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
use midcom_services_auth;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Cleanup dangling privileges
 *
 * @package midcom.console
 */
#[AsCommand(
    name: 'midcom:cleanup:privileges',
    description: 'Cleanup dangling privileges',
    aliases: ['privilegecleanup']
)]
class privileges extends Command
{
    private midcom_services_auth $auth;

    public function __construct(midcom_services_auth $auth)
    {
        $this->auth = $auth;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('dry', 'd', InputOption::VALUE_NONE, 'If set, privileges will not be deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if ($dry = $input->getOption("dry")) {
            $output->writeln("<comment>Running in dry mode!</comment>");
        }

        $qb = \midcom_core_privilege_db::new_query_builder();
        $output->writeln("<comment>Checking privileges</comment>");

        $progress = new ProgressBar($output, $qb->count());
        $progress->setRedrawFrequency(100);
        $progress->start();

        $seen_parents = $seen_assignees = [];
        $checker = new \midcom_core_privilege;
        $to_delete = [];
        foreach ($qb->iterate() as $priv) {
            if (!array_key_exists($priv->objectguid, $seen_parents)) {
                try {
                    \midgard_object_class::get_object_by_guid($priv->objectguid);
                    $seen_parents[$priv->objectguid] = true;
                } catch (\Exception) {
                    $seen_parents[$priv->objectguid] = false;
                }
            }
            if (!$seen_parents[$priv->objectguid]) {
                $to_delete[$priv->guid] = $priv;
            }
            if (!$checker->is_magic_assignee($priv->assignee)) {
                if (!array_key_exists($priv->assignee, $seen_assignees)) {
                    $seen_assignees[$priv->assignee] = (bool) $this->auth->get_assignee($priv->assignee);
                }
                if (!$seen_assignees[$priv->assignee]) {
                    $to_delete[$priv->guid] = $priv;
                }
            }

            $progress->advance();
        }
        $progress->finish();

        $output->writeln("\nFound <info>" . count($to_delete) . "</info> dangling privileges");

        if (!$dry) {
            $output->writeln("<comment>Deleting privileges</comment>");
            $progress = new ProgressBar($output, count($to_delete));
            $progress->start();

            foreach ($to_delete as $priv) {
                $priv->purge();
                $progress->advance();
            }
            $progress->finish();
        }

        $output->writeln("\nDone");
        return Command::SUCCESS;
    }
}
