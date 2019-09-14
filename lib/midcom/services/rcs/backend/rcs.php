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
     */
    private $_history;

    /**
     * @var midcom_services_rcs_config
     */
    private $_config;

    public function __construct($object, midcom_services_rcs_config $config)
    {
        $this->_config = $config;
        $this->_guid = $object->guid;
    }

    private function _generate_rcs_filename($guid) : string
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
        if (midcom::get()->auth->user) {
            $update_string = midcom::get()->auth->user->id . "|{$_SERVER['REMOTE_ADDR']}";
        } else {
            $update_string = "NOBODY|{$_SERVER['REMOTE_ADDR']}";
        }

        $update_string .= "|{$updatemessage}";

        $result = $this->rcs_update($object, $update_string);

        // The methods return basically what the RCS unix level command returns, so nonzero value is error and zero is ok...
        return $result == 0;
    }

    /**
     * Update object to RCS
     * Should be called just before $object->update(), if the type parameter is omitted
     * the function will use GUID to determine the type, this makes an extra DB query.
     *
     * @param object $object to be updated.
     * @param string $message
     * @return int :
     *      0 on success
     *      3 on missing object->guid
     *      nonzero on error in one of the commands.
     */
    public function rcs_update($object, $message)
    {
        if (empty($object->guid)) {
            debug_add("Missing GUID, returning error");
            return 3;
        }

        $filename = $this->_generate_rcs_filename($object->guid);
        $rcsfilename = "{$filename},v";

        if (!file_exists($rcsfilename)) {
            // The methods return basically what the RCS unix level command returns, so nonzero value is error and zero is ok...
            return $this->rcs_create($object, $message);
        }

        $this->exec('co -q -f -l ' . escapeshellarg($filename));

        $data = $this->rcs_object2data($object);

        $this->rcs_writefile($object->guid, $data);
        $status = $this->exec('ci -q -m' . escapeshellarg($message) . " {$filename}");

        chmod($rcsfilename, 0770);

        return $status;
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

        $data = $this->rcs_readfile($this->_guid);

        $mapper = new midcom_helper_exporter_xml();
        $revision = $mapper->data2array($data);

        $this->exec("rm -f {$filepath}", false);

        return $revision;
    }

    /**
     * Check if a revision exists
     *
     * @param string $version
     * @return boolean true if exists
     */
    public function version_exists($version) : bool
    {
        $history = $this->list_history();
        return array_key_exists($version, $history);
    }

    /**
     * Get the previous versionID
     *
     * @param string $version
     * @return string versionid before this one or empty string.
     */
    public function get_prev_version($version)
    {
        $versions = $this->list_history_numeric();
        $position = array_search($version, $versions);

        if ($position === false || $position == count($versions) - 1) {
            return '';
        }
        return $versions[$position + 1];
    }

    /**
     * Get the next versionID
     *
     * @param string $version
     * @return string versionid before this one or empty string.
     */
    public function get_next_version($version)
    {
        $versions = $this->list_history_numeric();
        $position = array_search($version, $versions);

        if ($position === false || $position == 0) {
            return '';
        }
        return $versions[$position - 1];
    }

    /**
     * Return a list of the revisions as a key => value pair where
     * the key is the index of the revision and the value is the revision id.
     * Order: revision 0 is the newest.
     *
     * @return array
     */
    public function list_history_numeric() : array
    {
        $revs = $this->list_history();
        return array_keys($revs);
    }

    /**
     * Lists the number of changes that has been done to the object
     *
     * @return array list of changeids
     */
    public function list_history() : array
    {
        if (empty($this->_guid)) {
            return [];
        }

        if ($this->_history === null) {
            $filepath = $this->_generate_rcs_filename($this->_guid);
            $this->_history = $this->rcs_gethistory($filepath);
        }

        return $this->_history;
    }

    /* it is debatable to move this into the object when it resides nicely in a library... */

    private function rcs_parse_history_entry($entry) : array
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
        $history['revision'] = substr($entry[0], 9);

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
     * @return array list of revisions and revision comment.
     */
    private function rcs_gethistory($what) : array
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
        return $revisions;
    }

    /**
     * execute a command
     *
     * @param string $command The command to execute
     * @param string $filename The file to operate on
     * @return string command result.
     */
    private function rcs_exec($command, $filename) : string
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

    /**
     * Writes $data to file $guid, does not return anything.
     */
    private function rcs_writefile($guid, $data)
    {
        if (   !is_writable($this->_config->get_rcs_root())
            || empty($guid)) {
            return false;
        }
        $filename = $this->_generate_rcs_filename($guid);
        file_put_contents($filename, $data);
    }

    /**
     * Reads data from file $guid and returns it.
     *
     * @param string $guid
     * @return string xml representation of guid
     */
    private function rcs_readfile($guid)
    {
        $filename = $this->_generate_rcs_filename($guid);
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return '';
    }

    /**
     * Make xml out of an object.
     *
     * @param midcom_core_dbaobject $object
     * @return string xmldata
     */
    private function rcs_object2data(midcom_core_dbaobject $object) : string
    {
        $mapper = new midcom_helper_exporter_xml();
        return $mapper->object2data($object);
    }

    /**
     * Add object to RCS
     *
     * @param object $object object to be saved
     * @param string $description changelog comment.-
     * @return int :
     *      0 on success
     *      3 on missing object->guid
     *      nonzero on error in one of the commands.
     */
    private function rcs_create(midcom_core_dbaobject $object, $description)
    {
        $data = $this->rcs_object2data($object);

        if (empty($object->guid)) {
            return 3;
        }
        $this->rcs_writefile($object->guid, $data);
        $filepath = $this->_generate_rcs_filename($object->guid);
        $description = escapeshellarg($description);

        $status = $this->exec('ci -q -i -t-' . $description . ' -m' . $description . " {$filepath}");

        $filename = $filepath . ",v";
        if (file_exists($filename)) {
            chmod($filename, 0770);
        }
        return $status;
    }

    private function exec($command, $use_rcs_bindir = true)
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
     * Get the comment of one revision.
     *
     * @param string $revision id
     * @return string comment
     */
    public function get_comment($revision)
    {
        $this->list_history();
        return $this->_history[$revision];
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
