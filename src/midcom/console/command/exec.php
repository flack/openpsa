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

/**
 * CLI wrapper for midcom-exec calls
 *
 * @package midcom.console
 */
class exec extends Command
{
    use loginhelper;

    private $_component;

    private $_filename;

    protected function configure()
    {
        $parts = explode(':', $this->getName());
        $this->_component = $parts[0];
        $this->_filename = $parts[1] . '.php';

        $this->setDescription('Run midcom-exec script ' . $this->_filename)
            ->addArgument('get', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Additional GET parameters (key=value pairs, space-separated)')
            ->addOption('login', 'l', InputOption::VALUE_NONE, 'Use Midgard authorization');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('login')) {
            $dialog = $this->getHelperSet()->get('question');
            $this->login($dialog, $input, $output);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('Running ' . $this->_component . '/' . $this->_filename);
        }
        \midcom::get()->auth->request_sudo($this->_component);

        $context = \midcom_core_context::get();
        $context->parser = \midcom::get()->serviceloader->load('midcom_core_service_urlparser');
        $context->parser->parse(['midcom-exec-' . $this->_component, $this->_filename]);

        $resolver = new \midcom_core_urlmethods($context);
        $resolver->process();
        \midcom::get()->auth->drop_sudo();
    }
}
