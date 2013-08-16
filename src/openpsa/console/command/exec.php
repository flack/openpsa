<?php
/**
 * @package openpsa.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\console\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI wrapper for midcom-exec calls
 *
 * @package openpsa.console
 */
class exec extends Command
{
    protected function configure()
    {
        $this->setName('midcom:exec')
            ->setDescription('Run midcom-exec script')
            ->addArgument('component', InputArgument::REQUIRED, 'Component name')
            ->addArgument('filename', InputArgument::REQUIRED, 'Name of the file to run')
            ->addArgument('get', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Additional GET parameters (key=value pairs, space-separated)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $component = $input->getArgument('component');
        $filename = $input->getArgument('filename');
        $get = $input->getArgument('get');

        if (is_array($get))
        {
            foreach ($get as $data)
            {
                $input_data = explode('=', $data);
                if (sizeof($input_data) == 1)
                {
                    $_GET[] = $input_data;
                }
                else if (sizeof($input_data) == 2)
                {
                    $_GET[$input_data[0]] = $input_data[1];
                }
                else
                {
                    throw new \RuntimeException('Could not parse input ' . $data);
                }
            }
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL)
        {
            $output->writeln('Running ' . $component . ' ' . $filename);
        }
        \midcom::get('auth')->request_sudo($component);

        $context = \midcom_core_context::get();
        $context->parser = \midcom::get('serviceloader')->load('midcom_core_service_urlparser');
        $context->parser->parse(array('midcom-exec-' . $component, $filename));

        $resolver = new \midcom_core_resolver($context);
        $resolver->process();
        \midcom::get('auth')->drop_sudo();
    }
}
