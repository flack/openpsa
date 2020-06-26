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
class midcom_services_rcs_backend_rcs implements midcom_services_rcs_backend
{
    /**
     * The current object
     */
    private $object;

    /**
     * Cached revision history for the object
     *
     * @var midcom_services_rcs_history
     */
    private $history;

    /**
     * @var midcom_services_rcs_config
     */
    private $_config;

    public function __construct($object, midcom_services_rcs_config $config)
    {
        $this->_config = $config;
        $this->object = $object;
    }

    private function generate_filename() : string
    {
        $guid = $this->object->guid;
        // Keep files organized to subfolders to keep filesystem sane
        $dirpath = $this->_config->get_rcs_root() . "/{$guid[0]}/{$guid[1]}";
        if (!file_exists($dirpath)) {
            debug_add("Directory {$dirpath} does not exist, attempting to create", MIDCOM_LOG_INFO);
            mkdir($dirpath, 0777, true);
        }
        return "{$dirpath}/{$guid}";
    }

    /**
     * Save a new revision
     */
    public function update($updatemessage = null) : bool
    {
        // Store user identifier and IP address to the update string
        $message = $_SERVER['REMOTE_ADDR'] . '|' . $updatemessage;
        $message = (midcom::get()->auth->user->id ?? 'NOBODY') . '|' . $message;

        $filename = $this->generate_filename();
        $rcsfilename = "{$filename},v";
        $message = escapeshellarg($message);

        if (file_exists($rcsfilename)) {
            $this->exec('co -q -f -l ' . escapeshellarg($filename));
            $command = 'ci -q -m' . $message . " {$filename}";
        } else {
            $command = 'ci -q -i -t-' . $message . ' -m' . $message . " {$filename}";
        }
        $mapper = new midcom_helper_exporter_xml;
        file_put_contents($filename, $mapper->object2data($this->object));
        $stat = $this->exec($command);

        if (file_exists($rcsfilename)) {
            chmod($rcsfilename, 0770);
        }

        // The methods return basically what the RCS unix level command returns, so nonzero value is error and zero is ok...
        return $stat == 0;
    }

    /**
     * Get the object of a revision
     *
     * @param string $revision identifier of revision wanted
     * @return array array representation of the object
     */
    public function get_revision($revision) : array
    {
        $filepath = $this->generate_filename();
        if ($this->exec('co -q -f -r' . escapeshellarg(trim($revision)) . " {$filepath} 2>/dev/null") != 0) {
            return [];
        }

        $data = (file_exists($filepath)) ? file_get_contents($filepath) : '';

        $mapper = new midcom_helper_exporter_xml();
        $revision = $mapper->data2array($data);

        $this->exec("rm -f {$filepath}", false);

        return $revision;
    }

    /**
     * Lists the number of changes that has been done to the object
     * Order: The first entry is the newest.
     *
     * @return array list of changeids
     */
    public function get_history() : ?midcom_services_rcs_history
    {
        if ($this->history === null) {
            $revisions = $this->load_history($this->generate_filename() . ',v');
            $this->history = new midcom_services_rcs_history($revisions);
        }

        return $this->history;
    }

    private function load_history(string $filename) : array
    {
        if (!is_readable($filename)) {
            debug_add('file ' . $filename . ' is not readable, returning empty result', MIDCOM_LOG_INFO);
            return [];
        }
        $fh = popen($this->_config->get_bin_prefix() . 'rlog "' . $filename . '" 2>&1', "r");
        $history = stream_get_contents($fh);
        pclose($fh);
        $revisions = [];
        $lines = explode("\n", $history);
        $total = count($lines);

        for ($i = 0; $i < $total; $i++) {
            if (substr($lines[$i], 0, 9) == "revision ") {
                $history = $this->parse_history_entry($lines[$i], $lines[$i + 1], $lines[$i + 2]);
                $revisions[$history['revision']] = $history;

                $i += 3;
                while (   $i < $total
                        && substr($lines[$i], 0, 4) != '----'
                        && substr($lines[$i], 0, 5) != '=====') {
                    $i++;
                }
            }
        }
        return $revisions;
    }

    private function parse_history_entry(string $line1, string $line2, string $line3) : array
    {
        // Create potentially empty defaults
        $history = ['date' => null, 'lines' => null, 'user' => null, 'ip' => null];

        // Revision number is in format
        // revision 1.11
        $history['revision'] = preg_replace('/(\d+\.\d+).*/', '$1', substr($line1, 9));

        // Entry metadata is in format
        // date: 2006/01/10 09:40:49;  author: www-data;  state: Exp;  lines: +2 -2
        // NOTE: Time here appears to be stored as UTC according to http://parand.com/docs/rcs.html
        $metadata_array = explode(';', $line2);
        foreach ($metadata_array as $metadata) {
            $metadata = trim($metadata);
            if (substr($metadata, 0, 5) == 'date:') {
                $history['date'] = strtotime(substr($metadata, 6));
            } elseif (substr($metadata, 0, 6) == 'lines:') {
                $history['lines'] = substr($metadata, 7);
            }
        }

        // Entry message is in format
        // user:27b841929d1e04118d53dd0a45e4b93a|84.34.133.194|message
        $message_array = explode('|', $line3);
        if (count($message_array) == 1) {
            $history['message'] = $message_array[0];
        } else {
            if ($message_array[0] != 'Object') {
                $history['user'] = $message_array[0];
            }
            $history['ip'] = $message_array[1];
            $history['message'] = $message_array[2];
        }
        return $history;
    }

    private function exec(string $command, $use_rcs_bindir = true)
    {
        $status = null;
        $output = null;

        // Always append stderr redirect
        $command .= ' 2>&1';

        if ($use_rcs_bindir) {
            $command = $this->_config->get_bin_prefix() . $command;
        }

        debug_add("Executing '{$command}'");

        try {
            @exec($command, $output, $status);
        } catch (Exception $e) {
            debug_add($e->getMessage());
        }

        if ($status !== 0) {
            debug_add("Command '{$command}' returned with status {$status}, see debug log for output", MIDCOM_LOG_WARN);
            debug_print_r('Got output: ', $output);
        }
        return $status;
    }

    /**
     * Get a html diff between two versions.
     *
     * @param string $oldest_revision id of the oldest revision
     * @param string $latest_revision id of the latest revision
     */
    public function get_diff($oldest_revision, $latest_revision) : array
    {
        $oldest = $this->get_revision($oldest_revision);
        $newest = $this->get_revision($latest_revision);

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

                $renderer = new midcom_services_rcs_renderer_html_sidebyside(['old' => $oldest_revision, 'new' => $latest_revision]);

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
     *
     * @param string $revision of revision to restore object to.
     * @return boolean true on success.
     */
    public function restore_to_revision($revision) : bool
    {
        $new = $this->get_revision($revision);
        $mapper = new midcom_helper_exporter_xml();
        $this->object = $mapper->data2object($new, $this->object);
        $this->object->set_rcs_message("Reverted to revision {$revision}");

        return $this->object->update();
    }
}
