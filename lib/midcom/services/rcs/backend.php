<?php
/**
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemabuilder;

/**
 * @package midcom.services.rcs
 */
abstract class midcom_services_rcs_backend
{
    /**
     * @var midcom_services_rcs_history
     */
    private $history;

    /**
     * @var midcom_core_dbaobject
     */
    protected $object;

    /**
     * @var midcom_services_rcs_config
     */
    protected $config;

    public function __construct(midcom_core_dbaobject $object, midcom_services_rcs_config $config)
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

    protected function write_object() : string
    {
        $filename = $this->generate_filename();
        $mapper = new midcom_helper_exporter_xml;
        file_put_contents($filename, $mapper->object2data($this->object));
        return $filename;
    }

    /**
     * Save a revision of an object, or create a revision if none exists
     */
    abstract public function update(string $updatemessage = '');

    /**
     * Get a revision
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

        $dm = new datamanager((new schemabuilder($this->object))->create(null));
        $oldest_dm = $dm
            ->set_defaults($oldest)
            ->set_storage(new $this->object->__midcom_class_name__)
            ->render('plaintext');
        $newest_dm = $dm
            ->set_defaults($newest)
            ->set_storage(new $this->object->__midcom_class_name__)
            ->render('plaintext');

        foreach ($oldest_dm as $attribute => $oldest_value) {
            if (is_array($oldest_value)) {
                continue;
            }

            $entry = [
                'old' => $oldest_value,
                'new' => $newest_dm[$attribute] ?? ''
            ];

            if ($entry['old'] != $entry['new']) {
                $lines1 = explode("\n", $entry['old']);
                $lines2 = explode("\n", $entry['new']);

                $renderer = new Diff_Renderer_Html_SideBySide([
                    'old' => $oldest_version,
                    'new' => $latest_version
                ]);

                $diff = new Diff($lines1, $lines2);
                // Run the diff
                $entry['diff'] = $diff->render($renderer);
            }
            $return[$attribute] = $entry;
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
