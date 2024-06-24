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
use midcom_config;
use midcom_services_rcs_config;
use midgard\portable\storage\connection;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Cleanup the RCS dir
 * Searches for RCS files that don't have a corresponding entry in the repligard table
 *
 * @package midcom.console
 */
#[AsCommand(
    name: 'midcom:cleanup:rcsdir',
    description: 'Cleanup the RCS dir',
    aliases: ['rcsdircleanup']
)]
class rcsdir extends Command
{
    private int $counter = 0;

    private array $orphaned = [];

    private midcom_config $config;

    public function __construct(midcom_config $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('dry', 'd', InputOption::VALUE_NONE, 'If set, files will not be deleted');
    }

    private function check_dir(OutputInterface $output, string $outerDir)
    {
        $outerDir = rtrim($outerDir, "/");
        $output->write("\x0D");
        $output->write("Start scanning dir: <comment>" . $outerDir . "</comment>");
        $dirs = array_diff(scandir($outerDir), [".", ".."]);
        foreach ($dirs as $d) {
            if (is_dir($outerDir . "/" . $d)) {
                $this->check_dir($output, $outerDir . "/" . $d);
            } else {
                // got something
                $file = $outerDir . "/" . $d;
                if (!$this->has_repligard_entry($file)) {
                    $this->orphaned[] = $file;
                }
                $this->counter++;
            }
        }
    }

    private function has_repligard_entry(string $file) : bool
    {
        $guid = preg_replace('/^.+\/(.+?),?v?$/', '$1', $file);

        $repligard_entry = connection::get_em()
            ->getRepository('midgard:midgard_repligard')
            ->findOneBy(['guid' => $guid]);

        return !empty($repligard_entry);
    }

    private function cleanup_file(OutputInterface $output, string $file)
    {
        if (unlink($file)) {
            $output->writeln("<info>Cleanup OK</info>", Output::VERBOSITY_VERBOSE);
        } else {
            $output->writeln("<comment>Cleanup FAILED</comment>");
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $config = new midcom_services_rcs_config($this->config);
        $dir = $config->get_rootdir();
        if (!is_dir($dir)) {
            $output->writeln("<comment>Unable to detect RCS dir</comment> $dir");
            return Command::FAILURE;
        }
        if ($dry = $input->getOption("dry")) {
            $output->writeln("<comment>Running in dry mode!</comment>");
        }

        $this->check_dir($output, $dir);

        $output->writeln("\nScanned <info>" . $this->counter . "</info> files");
        $output->writeln("Found <info>" . count($this->orphaned) . "</info> orphaned files:");

        if (!$dry) {
            $output->writeln("<comment>Deleting orphans</comment>");
            $progress = new ProgressBar($output, count($this->orphaned));
            $progress->setRedrawFrequency(100);
            $progress->start();

            foreach ($this->orphaned as $file) {
                $this->cleanup_file($output, $file);
                $progress->advance();
            }
            $progress->finish();
        }

        $output->writeln("\n<comment>Done</comment>");
        return Command::SUCCESS;
    }
}
