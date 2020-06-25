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
     * GUID of the current object
     */
    private $_guid;

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
        $this->_guid = $object->guid;
    }

    private function _generate_rcs_filename(string $guid) : string
    {
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
     *
     * @param object $object object to be saved
     * @return boolean true on success.
     */
    public function update($object, $updatemessage = null) : bool
    {
        // Store user identifier and IP address to the update string
        $message = $_SERVER['REMOTE_ADDR'] . '|' . $updatemessage;
        $message = (midcom::get()->auth->user->id ?? 'NOBODY') . '|' . $message;

        $filename = $this->_generate_rcs_filename($object->guid);
        $rcsfilename = "{$filename},v";
        $message = escapeshellarg($message);

        if (file_exists($rcsfilename)) {
            $this->exec('co -q -f -l ' . escapeshellarg($filename));
            $command = 'ci -q -m' . $message . " {$filename}";
        } else {
            $command = 'ci -q -i -t-' . $message . ' -m' . $message . " {$filename}";
        }
        $mapper = new midcom_helper_exporter_xml;
        file_put_contents($filename, $mapper->object2data($object));
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
        if (empty($this->_guid)) {
            return [];
        }
        $filepath = $this->_generate_rcs_filename($this->_guid);
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
        if (empty($this->_guid)) {
            return null;
        }

        if ($this->history === null) {
            $filepath = $this->_generate_rcs_filename($this->_guid);
            $this->history = $this->rcs_gethistory($filepath);
        }

        return $this->history;
    }

    /* it is debatable to move this into the object when it resides nicely in a library... */

    private function rcs_parse_history_entry(array $entry) : array
    {
        // Create the empty history array
        $history = [
            'revision' => null,
            'date'     => null,
            'lines'    => null,
            'user'     => null,
            'ip'       => null,
            'message'  => null,
        ];

        // Revision number is in format
        // revision 1.11
        $history['revision'] = preg_replace('/(\d+\.\d+).*/', '$1', substr($entry[0], 9));

        // Entry metadata is in format
        // date: 2006/01/10 09:40:49;  author: www-data;  state: Exp;  lines: +2 -2
        // NOTE: Time here appears to be stored as UTC according to http://parand.com/docs/rcs.html
        $metadata_array = explode(';', $entry[1]);
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
        $message_array = explode('|', $entry[2]);
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

    /*
     * the functions below are mostly rcs functions moved into the class. Someday I'll get rid of the
     * old files...
     */
    /**
     * Get a list of the object's history
     *
     * @param string $what objectid (usually the guid)
     */
    private function rcs_gethistory(string $what) : midcom_services_rcs_history
    {
        $history = $this->rcs_exec('rlog', $what . ',v');
        $revisions = [];
        $lines = explode("\n", $history);
        $total = count($lines);

        for ($i = 0; $i < $total; $i++) {
            if (substr($lines[$i], 0, 9) == "revision ") {
                $history_entry = [$lines[$i], $lines[$i + 1], $lines[$i + 2]];
                $history = $this->rcs_parse_history_entry($history_entry);

                $revisions[$history['revision']] = $history;

                $i += 3;

                while (   $i < $total
                       && substr($lines[$i], 0, 4) != '----'
                       && substr($lines[$i], 0, 5) != '=====') {
                    $i++;
                }
            }
        }
        return new midcom_services_rcs_history($revisions);
    }

    /**
     * execute a command
     *
     * @param string $command The command to execute
     * @param string $filename The file to operate on
     * @return string command result.
     */
    private function rcs_exec(string $command, string $filename) : string
    {
        if (!is_readable($filename)) {
            debug_add('file ' . $filename . ' is not readable, returning empty result', MIDCOM_LOG_INFO);
            return '';
        }
        $fh = popen($this->_config->get_bin_prefix() . $command . ' "' . $filename . '" 2>&1', "r");
        $ret = "";
        while ($reta = fgets($fh, 1024)) {
            $ret .= $reta;
        }
        pclose($fh);

        return $ret;
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

        try {
            $object = midcom::get()->dbfactory->get_object_by_guid($this->_guid);
        } catch (midcom_error $e) {
            debug_add("{$this->_guid} could not be resolved to object", MIDCOM_LOG_ERROR);
            return false;
        }
        $mapper = new midcom_helper_exporter_xml();
        $object = $mapper->data2object($new, $object);

        $object->set_rcs_message("Reverted to revision {$revision}");

        return $object->update();
    }
}
