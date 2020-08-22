<?php
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require dirname(__DIR__) . '/vendor/autoload.php';
} else {
    require dirname(__DIR__, 4) . '/vendor/autoload.php';
}
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class rcs2git extends Command
{
    public function configure()
    {
        $this->setName('convert')
            ->setDescription('Convert rcs directory to git')
            ->addArgument('dir', InputArgument::REQUIRED, 'The directory to convert')
            ->addOption('bindir', '-b', InputArgument::OPTIONAL, 'The containing the RCS executables', '/usr/bin/');
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $dir = $input->getArgument('dir');

        if (file_exists($dir . '.git')) {
            throw new RuntimeException('Git repo already exists');
        }
        $this->exec('git -C ' . $dir . ' init', $output);
        if (!system('git -C ' . $dir . ' config user.email')) {
            $user = get_current_user();
            $this->exec('git -C ' . $dir . ' config user.email "' . $user . '@localhost"', $output);
            $this->exec('git -C ' . $dir . ' config user.name "' . $user . '"', $output);
        }

        // root dir + 16 subdirs + 256 subsubdirs
        $progress = new ProgressBar($output, 273);
        $progress->start();

        $files = $revisions = 0;

        $it = new RecursiveDirectoryIterator($dir);
        foreach (new RecursiveIteratorIterator($it) as $file) {
            /** @var SplFileInfo $file */
            if ($file->getType() !== 'file') {
                if (substr($file->getPath(), 0, strlen($dir . '.git')) !== $dir . '.git') {
                    if ($file->getBasename() === '.') {
                        $progress->advance();
                    }
                }
                continue;
            }

            if (substr($file->getBasename(), -2, 2) !== ',v') {
                continue;
            }

            $rcsfile = $file->getPathname();
            $filename = substr($rcsfile, 0, -2);
            if (file_exists($filename)) {
                unlink($filename);
            }
            $backend = $this->get_backend(basename($filename), $input);
            $history = $backend->get_history();
            $current = $history->get_first();
            if (empty($current)) {
                $output->writeln('Could not find revisions in ' . $rcsfile);
                continue;
            }
            $files++;

            do {
                if (!$this->exec('co -q -f -r' . escapeshellarg($current['revision']) . " {$filename}", $output)) {
                    $output->writeln('Could not check out revision ' . $current['revision'] . ' in ' . $rcsfile);
                    continue 2;
                }

                $this->exec('git -C ' . $dir . ' add ' . substr($filename, strlen($dir)), $output);

                $author = escapeshellarg($current['user'] . ' <' . $current['user'] . '@' . $current['ip'] . '>');
                $cmd = 'git -C ' . $dir . ' commit -q --allow-empty-message -m ' . escapeshellarg($current['message']) .
                       ' --author ' . $author . ' --date ' . escapeshellarg($current['date']);
                $this->exec($cmd, $output);
                $revisions++;

                $next = $history->get_next_version($current['revision']);
                if (!$next) {
                    break;
                }
            } while ($current = $history->get($next));
        }
        $progress->finish();
        $output->writeln("\nConverted <info>" . $revisions . '</info> revisions in <comment>' . $files . '</comment> files');

        return 0;
    }

    private function exec(string $command, OutputInterface $output) : bool
    {
        $stat = $out = null;
        exec($command . ' 2>&1', $out, $stat);
        if ($stat != 0) {
            $output->writeln($command . ' failed with:');
            array_map([$output, 'writeln'], $out);
            return false;
        }
        return true;
    }

    private function get_backend(string $guid, InputInterface $input) : midcom_services_rcs_backend_rcs
    {
        $object = new stdClass;
        $object->guid = $guid;
        $conf = new midcom_config;
        $conf->set('midcom_services_rcs_enable', true);
        $conf->set('midcom_services_rcs_root', $input->getArgument('dir'));
        $conf->set('midcom_services_rcs_bin_dir', $input->getOption('bindir'));
        $config = new midcom_services_rcs_config($conf);
        return new midcom_services_rcs_backend_rcs($object, $config);
    }
}

(new Application('rcs2git', '1.0.0'))
    ->add(new rcs2git)
    ->getApplication()
    ->run();