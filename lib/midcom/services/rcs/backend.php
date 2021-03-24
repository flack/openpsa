<?php
/**
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.services.rcs
 */
abstract class midcom_services_rcs_backend
{
    /**
     * Cached revision history for the object
     *
     * @var midcom_services_rcs_history
     */
    private $history;

    /**
     * The current object
     */
    protected $object;

    /**
     * @var midcom_services_rcs_config
     */
    protected $config;

    public function __construct($object, midcom_services_rcs_config $config)
    {
        $this->object = $object;
        $this->config = $config;
        $this->test_config();
    }

    protected function test_config()
    {
        if (!is_writable($this->config->get_rootdir())) {
            throw new midcom_error("The root directory {$this->config->get_rootdir()} is not writable!");
        }
    }

    protected function generate_filename() : string
    {
        $guid = $this->object->guid;
        // Keep files organized to subfolders to keep filesystem sane
        $dirpath = $this->config->get_rootdir() . "/{$guid[0]}/{$guid[1]}";
        if (!file_exists($dirpath)) {
            debug_add("Directory {$dirpath} does not exist, attempting to create", MIDCOM_LOG_INFO);
            mkdir($dirpath, 0777, true);
        }
        return "{$dirpath}/{$guid}";
    }

    protected function read_handle(string $command) : array
    {
        $fh = popen($command, "r");
        $output = stream_get_contents($fh);
        pclose($fh);
        return explode("\n", $output);
    }

    protected function run_command(string $command)
    {
        $status = $output = null;
        $command .= ' 2>&1';
        debug_add("Executing '{$command}'");
        exec($command, $output, $status);
        if ($status !== 0) {
            debug_print_r('Got output: ', $output);
            throw new midcom_error("Command '{$command}' returned with status {$status}:" . implode("\n", $output), MIDCOM_LOG_WARN);
        }
    }

    /**
     * Save a revision of an object, or create a revision if none exists
     *
     * @param midcom_core_dbaobject $object the object to save.
     * @param string $updatemessage the message to be saved with the object.
     * @throws midcom_error on serious errors.
     */
    abstract public function update(string $updatemessage = null);

    /**
     * Get the object of a revision
     *
     * @return array array representation of the object
     */
    abstract public function get_revision(string $revision) : array;

    /**
     * Lists the number of changes that has been done to the object
     * Order: The first entry is the newest.
     */
    abstract protected function load_history() : array;

    public function get_history() : midcom_services_rcs_history
    {
        if ($this->history === null) {
            $revisions = $this->load_history();
            $this->history = new midcom_services_rcs_history($revisions);
        }

        return $this->history;
    }

    /**
     * Get a html diff between two versions.
     */
    public function get_diff(string $oldest_revision, string $latest_revision) : array
    {
        $oldest = $this->get_revision($oldest_revision);
        $newest = $this->get_revision($latest_revision);
        $oldest_version = $this->history->get($oldest_revision)['version'] ?? $oldest_revision;
        $latest_version = $this->history->get($latest_revision)['version'] ?? $latest_revision;

        $return = [];
        $oldest = array_intersect_key($oldest, $newest);

        $repl = [
            '<del>' => "<span class=\"deleted\">",
            '</del>' => '</span>',
            '<ins>' => "<span class=\"inserted\">",
            '</ins>' => '</span>'
        ];
        foreach ($oldest as $attribute => $oldest_value) {
            if (is_array($oldest_value)) {
                continue;
            }

            $return[$attribute] = [
                'old' => $oldest_value,
                'new' => $newest[$attribute]
            ];

            if ($oldest_value != $newest[$attribute]) {
                $lines1 = explode("\n", $oldest_value);
                $lines2 = explode("\n", $newest[$attribute]);

                $renderer = new midcom_services_rcs_renderer_html_sidebyside([
                    'old' => $oldest_version,
                    'new' => $latest_version
                ]);

                if ($lines1 != $lines2) {
                    $diff = new Diff($lines1, $lines2);
                    // Run the diff
                    $return[$attribute]['diff'] = $diff->render($renderer);
                    // Modify the output for nicer rendering
                    $return[$attribute]['diff'] = strtr($return[$attribute]['diff'], $repl);
                }
            }
        }

        return $return;
    }

    /**
     * Restore an object to a certain revision.
     */
    public function restore_to_revision(string $revision) : bool
    {
        $new = $this->get_revision($revision);
        $mapper = new midcom_helper_exporter_xml();
        $this->object = $mapper->data2object($new, $this->object);
        $this->object->set_rcs_message("Reverted to revision {$revision}");

        return $this->object->update();
    }
}
