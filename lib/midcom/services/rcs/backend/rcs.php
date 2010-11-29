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

    private $_config;

    public function __construct(&$object, &$config)
    {
        $this->_config = $config;
        $this->_guid = $object->guid;
    }

    public function _generate_rcs_filename($object)
    {
        if (!isset($object->guid))
        {
            return null;
        }

        // Keep files organized to subfolders to keep filesystem sane
        $dirpath = $this->_config->get_rcs_root() . "/{$object->guid[0]}/{$object->guid[1]}";
        if (!file_exists($dirpath))
        {
            debug_add("Directory {$dirpath} does not exist, attempting to create", MIDCOM_LOG_WARN);
            if (!file_exists($this->_config->get_rcs_root() . "/{$object->guid[0]}"))
            {
                mkdir($this->_config->get_rcs_root() . "/{$object->guid[0]}");
            }
            mkdir($dirpath);
        }
        $filename = "{$dirpath}/{$object->guid}";

        return $filename;
    }

    /**
     * Save a new revision
     *
     * @param object object to be saved
     * @return boolean true on success.
     */
    public function update(&$object, $updatemessage = null)
    {
        // Store user identifier and IP address to the update string
        if ($_MIDCOM->auth->user)
        {
            $update_string = "{$_MIDCOM->auth->user->id}|{$_SERVER['REMOTE_ADDR']}";
        }
        else
        {
            $update_string = "NOBODY|{$_SERVER['REMOTE_ADDR']}";
        }

        // Generate update message if needed
        if (!$updatemessage)
        {
            if ($_MIDCOM->auth->user !== null)
            {
                $updatemessage = sprintf("Updated on %s by %s", strftime("%x %X"), $_MIDCOM->auth->user->name);
            }
            else
            {
                $updatemessage = sprintf("Updated on %s.", strftime("%x %X"));
            }
        }
        $update_string .= "|{$updatemessage}";

        $result = $this->rcs_update($object, $update_string);

        // The methods return basically what the RCS unix level command returns, so nonzero value is error and zero is ok...
        if ($result > 0 )
        {
            return false;
        }
        return true;
    }

    /**
     * This function takes an object and updates it to RCS, it should be
     * called just before $object->update(), if the type parameter is omitted
     * the function will use GUID to determine the type, this makes an
     * extra DB query.
     *
     * @param string root of rcs directory.
     * @param object object to be updated.
     * @return int :
     *      0 on success
     *      3 on missing object->guid
     *      nonzero on error in one of the commands.
     */
    public function rcs_update ($object, $message)
    {
        $status = null;

        $guid = $object->guid;

        if (!($guid <> ""))
        {
            debug_add("Missing GUID, returning error");
            return 3;
        }

        $filename = $this->_generate_rcs_filename($object);
        if (is_null($filename))
        {
            return 0;
        }

        $rcsfilename =  "{$filename},v";

        if (!file_exists($rcsfilename))
        {
            // The methods return basically what the RCS unix level command returns, so nonzero value is error and zero is ok...
            return $this->rcs_create($object, $message);
        }

        $command = 'co -q -f -l ' . escapeshellarg($filename);
        $status = $this->exec($command);

        $data = $this->rcs_object2data($object);

        $this->rcs_writefile($guid, $data);
        $command = 'ci -q -m' . escapeshellarg($message) . " {$filename}";
        $status = $this->exec($command);

        chmod ($rcsfilename, 0770);

        return $status;
    }

   /**
    * Get the object of a revision
    *
    * @param string revision identifier of revision wanted
    * @return array array representation of the object
    */
    public function get_revision( $revision)
    {
        $object = $_MIDCOM->dbfactory->get_object_by_guid($this->_guid);

        $filepath = $this->_generate_rcs_filename($object);
        $return = array();
        if (is_null($filepath))
        {
            return $return;
        }


        // , must become . to work. Therefore this:
        str_replace(',', '.', $revision );
        // this seems to cause problems:
        //settype ($revision, "float");

        $command = 'co -q -f -r' . escapeshellarg(trim($revision)) .  " {$filepath} 2>/dev/null";
        $this->exec($command);

        $data = $this->rcs_readfile($this->_guid);

        $mapper = new midcom_helper_xml_objectmapper();
        $revision = $mapper->data2array($data);

        $command = "rm -f {$filepath}";
        $this->exec($command);

        return $revision;
    }


    /**
     * Check if a revision exists
     *
     * @param string  version
     * @return booleann true if exists
     */
    public function version_exists($version)
    {
        $history = $this->list_history();
        return array_key_exists($version,$history);
    }

    /**
     * Get the previous versionID
     *
     * @param string version
     * @return string versionid before this one or empty string.
     */
    public function get_prev_version($version)
    {
        $versions = $this->list_history_numeric();

        if (   !in_array($version, $versions)
            || $version === end($versions))
        {
            return '';
        }

        $mode = end($versions);

        while( $mode
            && $mode !== $version)
        {
            $mode = prev($versions);

            if ($mode === $version)
            {
                return next($versions);
            }
        }

        return '';
    }

    /**
     * Mirror method for get_prev_version()
     *
     * @param string $version
     * @return mixed
     */
    public function get_previous_version($version)
    {
        return $this->get_prev_version($version);
    }

    /**
     * Get the next versionID
     *
     * @param string version
     * @return string versionid before this one or empty string.
     */
    public function get_next_version($version)
    {
        $versions = $this->list_history_numeric();

        if (   !in_array($version, $versions)
            || $version === current($versions))
        {
            return '';
        }

        $mode = current($versions);

        while( $mode
            && $mode !== $version)
        {
            $mode = next($versions);

            if ($mode === $version)
            {
                return prev($versions);
            }
        }

        return '';
    }

    /**
     * This function returns a list of the revisions as a
     * key => value par where the key is the index of the revision
     * and the value is the revision id.
     * Order: revision 0 is the newest.
     *
     * @return array
     */
    public function list_history_numeric()
    {
        $revs = $this->list_history();
        $i = 0;
        $revisions = array();
        foreach($revs as $id => $desc)
        {
            $revisions[$i] = $id;
            $i++;
        }
        return $revisions;
    }

    /**
     * Lists the number of changes that has been done to the object
     *
     * @return array list of changeids
     */
    public function list_history()
    {
        $object = $_MIDCOM->dbfactory->get_object_by_guid($this->_guid);
        $filepath = $this->_generate_rcs_filename($object);
        if (is_null($filepath))
        {
            return array();
        }

        return $this->rcs_gethistory($filepath);
    }

    /* it is debatable to move this into the object when it resides nicely in a libary... */

    private function rcs_parse_history_entry($entry)
    {
        // Create the empty history array
        $history = array
        (
            'revision' => null,
            'date'     => null,
            'lines'    => null,
            'user'     => null,
            'ip'       => null,
            'message'  => null,
        );

        // Revision number is in format
        // revision 1.11
        $history['revision'] = substr($entry[0], 9);

        // Entry metadata is in format
        // date: 2006/01/10 09:40:49;  author: www-data;  state: Exp;  lines: +2 -2
        // NOTE: Time here appears to be stored as UTC according to http://parand.com/docs/rcs.html
        $metadata_array = explode(';',$entry[1]);
        foreach ($metadata_array as $metadata)
        {
            $metadata = trim($metadata);
            if (substr($metadata, 0, 5) == 'date:')
            {
                $history['date'] = strtotime(substr($metadata, 6));
            }
            elseif (substr($metadata, 0, 6) == 'lines:')
            {
                $history['lines'] = substr($metadata, 7);
            }
        }

        // Entry message is in format
        // user:27b841929d1e04118d53dd0a45e4b93a|84.34.133.194|Updated on Tue 10.Jan 2006 by admin kw
        $message_array = explode('|', $entry[2]);
        if (count($message_array) == 1)
        {
            $history['message'] = $message_array[0];
        }
        else
        {
            if ($message_array[0] != 'Object')
            {
                $history['user'] = $message_array[0];
            }
            $history['ip']   = $message_array[1];
            $history['message'] = $message_array[2];
        }
        return $history;
    }

    /*
     * the functions below are mostly rcs functions moved into the class. Someday I'll get rid of the
     * old files....
     *
     */
    /**
     * Get a list of the object's history
     * @param string objectid (usually the guid)
     * @return array list of revisions and revision comment.
     */
    private function rcs_gethistory($what)
    {
        $history = $this->rcs_exec('rlog "' . $what . ',v"');
        $revisions = array();
        $lines = explode("\n", $history);

        for ($i = 0; $i < count($lines); $i++)
        {
            if (substr($lines[$i], 0, 9) == "revision ")
            {
                $history_entry[0] = $lines[$i];
                $history_entry[1] = $lines[$i+1];
                $history_entry[2] = $lines[$i+2];
                $history = $this->rcs_parse_history_entry($history_entry);

                $revisions[$history['revision']] = $history;

                $i += 3;

                while (   $i < count($lines)
                       && substr($lines[$i], 0, 4) != '----'
                       && substr($lines[$i], 0, 5) != '=====')
                {
                     $i++;
                }
            }
        }
        return $revisions;
    }

    /**
     * execute a command
     *
     * @param string command
     * @return string command result.
     */
    private function rcs_exec($command)
    {
        $fh = popen($command, "r");
        $ret = "";
        while ($reta = fgets($fh, 1024))
        {
            $ret .= $reta;
        }
        pclose($fh);
        return $ret;
    }

    /**
     * Writes $data to file $guid, does not return anything.
     */
    private function rcs_writefile ($guid, $data)
    {
        if (!is_writable($this->_config->get_rcs_root()))
        {
            return false;
        }
        $object = $_MIDCOM->dbfactory->get_object_by_guid($guid);
        $filename = $this->_generate_rcs_filename($object);
        if (is_null($filename))
        {
            return false;
        }
        $fp = fopen ($filename, "w");
        fwrite ($fp, $data);
        fclose ($fp);
    }

    /**
     * Reads data from file $guid and returns it.
     *
     * @param string guid
     * @return string xml representation of guid
     */
    private function rcs_readfile ($guid)
    {
        $object = $_MIDCOM->dbfactory->get_object_by_guid($guid);
        $filename = $this->_generate_rcs_filename($object);
        if (is_null($filename))
        {
            return '';
        }

        if (!file_exists($filename))
        {
            return '';
        }

        $fd = fopen ($filename, "r");
        $data = fread ($fd, filesize ($filename));
        fclose ($fd);
        return $data;
    }

    /**
     * Make xml out of an object.
     *
     * @param object
     * @return xmldata
     */
    private function rcs_object2data($object)
    {
        if (!is_object($object))
        {
            debug_add("Missing object needed as parameter.", MIDCOM_LOG_ERROR);
            return false;
        }
        $mapper = new midcom_helper_xml_objectmapper();
        $result = $mapper->object2data($object);
        if ($result)
        {
            return $result;
        }
        debug_add("Objectmapper returned false.");
        return false;
    }

    /**
     * This function takes an object and adds it to RCS, it should be
     * called just after $object->create(). Remember that you first need
     * to mgd_get the object since $object->create() returns only the id,
     * one way of doing this is:
     * @param object $object object to be saved
     * @param string $description changelog comment.-
     * @return int :
     *      0 on success
     *      3 on missing object->guid
     *      nonzero on error in one of the commands.
     */
    private function rcs_create($object, $description)
    {
        $output = null;
        $status = null;
        $guid = $object->guid;

        $type = get_class($object);

        $data = $this->rcs_object2data($object, $type);

        $this->rcs_writefile($guid, $data);
        $filepath = $this->_generate_rcs_filename($object);
        if (is_null($filepath))
        {
            return 3;
        }

        $command = 'ci -q -i -t-' . escapeshellarg($description) . " {$filepath}";
        $status = $this->exec($command);

        $filename = $filepath . ",v";

        if (file_exists($filename))
        {
            chmod ($filename, 0770);
        }
        return $status;
    }

    private function exec($command)
    {
        $status = null;
        $output = null;

        // Always append stderr redirect
        $command .= ' 2>&1';

        debug_add("Executing '{$command}'");

        try
        {
            @exec($command, $output, $status);
        }
        catch (Exception $e)
        {
            // TODO: Log?
        }

        if ($status === 0)
        {
            // Unix exit code 0 means all ok...
            return $status;
        }

        debug_add("Command '{$command}' returned with status {$status}, see debug log for output", MIDCOM_LOG_WARN);
        debug_print_r('Got output: ', $output);
        // any other exit codes means some sort of error
        return $status;
    }

    /**
     * Get a html diff between two versions.
     *
     * @param string latest_revision id of the latest revision
     * @param string oldest_revision id of the oldest revision
     * @return array array with the original value, the new value and a diff -u
     */
    public function get_diff($oldest_revision, $latest_revision, $renderer_style = 'inline')
    {
        $oldest = $this->get_revision($oldest_revision);
        $newest = $this->get_revision($latest_revision);

        $return = array();

        foreach ($oldest as $attribute => $oldest_value)
        {
            if (!array_key_exists($attribute, $newest))
            {
                continue;
                // This isn't in the newer version, skip
            }

            if (is_array($oldest_value))
            {
                continue;
                // Skip
            }

            $return[$attribute] = array
            (
                'old' => $oldest_value,
                'new' => $newest[$attribute]
            );

            if ($oldest_value != $newest[$attribute])
            {
                if (class_exists('Text_Diff'))
                {
                    $lines1 = explode ("\n", $oldest_value);
                    $lines2 = explode ("\n", $newest[$attribute]);

                    $diff = new Text_Diff($lines1, $lines2);

                    if ($renderer_style == 'unified')
                    {
                        $renderer = new Text_Diff_Renderer_unified();
                    }
                    else
                    {
                        $renderer = new Text_Diff_Renderer_inline();
                    }

                    if (!$diff->isEmpty())
                    {
                        // Run the diff
                        $return[$attribute]['diff'] = $renderer->render($diff);

                        if ($renderer_style == 'inline')
                        {
                            // Modify the output for nicer rendering
                            $return[$attribute]['diff'] = str_replace('<del>', "<span class=\"deleted\" title=\"removed in {$latest_revision}\">", $return[$attribute]['diff']);
                            $return[$attribute]['diff'] = str_replace('</del>', '</span>', $return[$attribute]['diff']);
                            $return[$attribute]['diff'] = str_replace('<ins>', "<span class=\"inserted\" title=\"added in {$latest_revision}\">", $return[$attribute]['diff']);
                            $return[$attribute]['diff'] = str_replace('</ins>', '</span>', $return[$attribute]['diff']);
                        }
                    }
                }
                else if (!is_null($GLOBALS['midcom_config']['utility_diff']))
                {
                    /* this doesn't work */
                    $command = $GLOBALS['midcom_config']['utility_diff'] . " -u <(echo \"{$oldest_value}\") <(echo \"{$newest[$attribute]}\")";

                    $output = array();
                    $result = shell_exec($command);
                    $return[$attribute]['diff'] = $command. "\n'".$result . "'";
                }
                else
                {
                    $return[$attribute]['diff'] = "THIS IS AN OUTRAGE!";
                }
            }
        }

        return $return;
    }

    /**
     * Get the comment of one revision.
     *
     * @param string revison id
     * @return string comment
     */
    public function get_comment($revision)
    {
        if (is_null($this->_history))
        {
            $this->_history = $this->list_history();
        }
        return $this->_history[$revision];
    }

    /**
     * Restore an object to a certain revision.
     *
     * @param string id of revision to restore object to.
     * @return boolean true on success.
     */
    public function restore_to_revision($revision)
    {
        $new = $this->get_revision($revision);

        $object = $_MIDCOM->dbfactory->get_object_by_guid($this->_guid);
        if (   !is_object($object)
            || !$object->guid)
        {
            debug_add("{$this->_guid} could not be resolved to object", MIDCOM_LOG_ERROR);
            return false;
        }
        $mapper = new midcom_helper_xml_objectmapper();
        $object = $mapper->data2object($new, $object);

        $object->set_rcs_message("Reverted to revision {$revision}");

        if ($object->update())
        {
            return true;
        }
        $this->error[]  = "Object {$this->_guid} not updated: " . midcom_connection::get_error_string();
        return false;
    }
}
?>