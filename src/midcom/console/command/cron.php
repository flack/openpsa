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
use midcom;
use midcom_error;
use midcom_services_cron;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Cron command
 *
 * @package midcom.console
 */
class cron extends Command
{
    protected function configure()
    {
        $this->setName('midcom:cron')
            ->setDescription('Reindex')
            ->addArgument('type', InputArgument::OPTIONAL, 'Recurrence (minute, hour, or day)', 'minute');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!midcom::get()->auth->request_sudo('midcom.services.cron')) {
            throw new midcom_error('Failed to get sudo');
        }

        // compat for old-style calls (type=minute)
        $type = str_replace('type=', '', $input->getArgument('type'));

        $recurrence = 'MIDCOM_CRON_' . strtoupper($type);
        if (!defined($recurrence)) {
            throw new midcom_error('Unsupported type ' . $type);
        }
        // Instantiate cron service and run
        $cron = new midcom_services_cron(constant($recurrence));
        $cron->execute();

        midcom::get()->auth->drop_sudo();
    }
}
