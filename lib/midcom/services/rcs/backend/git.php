<?php
/**
 * @package midcom.services.rcs
 * @author CONTENT CONTROL https://contentcontrol.berlin
 * @copyright CONTENT CONTROL https://contentcontrol.berlin
 */

/**
 * @package midcom.services.rcs
 */
class midcom_services_rcs_backend_git extends midcom_services_rcs_backend
{
    /**
     * Save a new revision
     */
    public function update($updatemessage = null) : bool
    {
        $author = midcom::get()->auth->user->id ?? 'NOBODY';
        $author .= ' <' . $author . '@' . $_SERVER['REMOTE_ADDR'] . '>';

        $filename = $this->generate_filename();
        $mapper = new midcom_helper_exporter_xml;
        file_put_contents($filename, $mapper->object2data($this->object));

        if ($this->exec('add ' . $this->relative_path($filename))) {
            $command = 'commit -q --allow-empty --allow-empty-message -m ' . escapeshellarg($updatemessage) .
            ' --author ' . escapeshellarg($author);
            return $this->exec($command);
        }
        return false;
    }

    /**
     * Get the object of a revision
     *
     * @param string $revision identifier of revision wanted
     * @return array array representation of the object
     */
    public function get_revision($revision) : array
    {
        $filename = $this->generate_filename();
        $lines = $this->read_handle('show ' . $revision . ':' . $this->relative_path($filename));
        $mapper = new midcom_helper_exporter_xml();
        return $mapper->data2array(implode("\n", $lines));
    }

    protected function load_history() : array
    {
        $filename = $this->generate_filename();
        if (!is_readable($filename)) {
            debug_add('file ' . $filename . ' is not readable, returning empty result', MIDCOM_LOG_INFO);
            return [];
        }

        $lines = $this->read_handle('log --shortstat --format=format:"%h%n%ae%n%at%n%s" ' . $this->relative_path($filename));
        $total = count($lines);
        $revisions = [];

        for ($i = 0; $i < $total; $i += 6) {
            [$user, $ip] = explode('@', $lines[$i + 1], 2);
            $stat = preg_replace('/.*?\d file changed/', '', $lines[$i + 4]);
            $stat = preg_replace('/\, (\d+) .+?tions?\(([\+\-])\)/', '$2$1 ', $stat);

            $revisions[$lines[$i]] = [
                'revision' => $lines[$i],
                'date' => $lines[$i + 2],
                'lines' => $stat,
                'user' => $user,
                'ip' => $ip,
                'message' => $lines[$i + 3]
            ];
        }

        return $revisions;
    }

    protected function generate_filename() : string
    {
        $root = $this->config->get_rootdir();
        $initialized = true;
        if (!file_exists($root . '/.git')) {
            if ((count(scandir($root)) > 2)) {
                // This is probably an old rcs dir
                throw new midcom_error($root . ' is not empty. Run tools/rcs2git to convert');
            }
            $initialized = false;
        }
        $filename = parent::generate_filename();

        if (!$initialized) {
            $this->exec('init');
        }
        return $filename;
    }

    protected function read_handle(string $command) : array
    {
        return parent::read_handle('cd ' . $this->config->get_rootdir() . ' && git ' . $command);
    }

    private function exec(string $command, string $filename = null) : bool
    {
        return $this->run_command('cd ' . $this->config->get_rootdir() . ' && git ' . $command);
    }

    private function relative_path(string $filename) : string
    {
        $relative_path = substr($filename, strlen($this->config->get_rootdir()));
        return escapeshellarg(trim($relative_path, '/'));
    }
}
