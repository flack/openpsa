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

/**
 * clear the cache of the current site
 *
 * @see midcom_services_cache
 * @package midcom.console
 */
class cacheinvalidate extends Command
{
    protected function configure()
    {
        $this->setName('midcom:cache-invalidate')
            ->setAliases(['cache-invalidate'])
            ->setDescription('Clears the cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        midcom::get()->cache->invalidate_all();
        return 0;
    }
}
