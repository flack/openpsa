<?php
/**
 * @package midcom.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\console\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use midcom\console\loginhelper;
use midcom_services_auth;
use midcom_helper__componentloader;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * CLI wrapper for midcom-exec calls
 *
 * @package midcom.console
 */
#[AsCommand(
    name: 'midcom:exec',
    description: 'Runs a script in midcom context',
    aliases: ['exec']
)]
class exec extends Command
{
    use loginhelper;

    private midcom_services_auth $auth;

    private midcom_helper__componentloader $loader;

    private string $projectdir;

    public function __construct(midcom_services_auth $auth, midcom_helper__componentloader $loader, string $projectdir)
    {
        $this->auth = $auth;
        $this->loader = $loader;
        $this->projectdir = $projectdir;
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'The file to run (leave empty to list available files)')
            ->addArgument('get', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Additional GET parameters (key=value pairs, space-separated)')
            ->addOption('login', 'l', InputOption::VALUE_NONE, 'Use Midgard authorization');
    }

    protected function interact(InputInterface $input, OutputInterface $output) : void
    {
        if ($input->getOption('login')) {
            $dialog = $this->getHelperSet()->get('question');
            $this->login($dialog, $input, $output);
        }
        $get = $input->getArgument('get');

        if (is_array($get)) {
            foreach ($get as $data) {
                $input_data = explode('=', $data);
                if (count($input_data) == 1) {
                    $_GET[] = $input_data;
                } elseif (count($input_data) == 2) {
                    $_GET[$input_data[0]] = $input_data[1];
                } else {
                    throw new \RuntimeException('Could not parse input ' . $data);
                }
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $file = $input->getArgument('file');

        if (empty($file)) {
            $this->list_files($output);
            return Command::SUCCESS;
        }

        $basedir = $this->projectdir . '/';
        if (!file_exists($basedir . $file)) {
            throw new \midcom_error('File not found');
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('Running ' . $file);
        }

        $this->auth->request_sudo('midcom.exec');

        try {
            require $basedir . $file;
        } catch (\midcom_error_forbidden) {
            $dialog = $this->getHelperSet()->get('question');
            $this->login($dialog, $input, $output);
            require $basedir . $file;
        }

        $this->auth->drop_sudo();
        return Command::SUCCESS;
    }

    private function list_files(OutputInterface $output)
    {
        $output->writeln("\n<comment>Available exec files:</comment>\n");

        foreach ($this->loader->get_manifests() as $manifest) {
            $exec_dir = $this->loader->path_to_snippetpath($manifest->name) . '/exec';

            if (is_dir($exec_dir)) {
                foreach (glob($exec_dir . '/*.php') as $file) {
                    $path = preg_replace('/^' . preg_quote($this->projectdir, '/') . '/', '', $file);
                    $parts = pathinfo($path);
                    $path = '  <info>' . $parts['dirname'] . '/</info>' . $parts['filename'] . '<info>.' . $parts['extension'] . '</info>';

                    $output->writeln($path);
                }
            }
        }
    }
}
