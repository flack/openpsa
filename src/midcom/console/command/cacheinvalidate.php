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
use midcom_services_cache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * clear the cache of the current site
 *
 * @see midcom_services_cache
 * @package midcom.console
 */
class cacheinvalidate extends Command
{
    private midcom_services_cache $cache;

    private string $cachedir;

    public function __construct(midcom_services_cache $cache, string $cachedir)
    {
        $this->cache = $cache;
        $this->cachedir = $cachedir;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('midcom:cache-invalidate')
            ->setAliases(['cache-invalidate'])
            ->setDescription('Clears the cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->cache->invalidate_all();
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
        }

        $fs = new Filesystem;
        $fs->remove([$this->cachedir]);
        return 0;
    }
}
