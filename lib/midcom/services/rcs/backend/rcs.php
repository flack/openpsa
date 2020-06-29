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
class midcom_services_rcs_backend_rcs extends midcom_services_rcs_backend
{
    /**
     * Save a new revision
     */
    public function update($updatemessage = null)
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
        $this->exec($command);

        if (file_exists($rcsfilename)) {
            chmod($rcsfilename, 0770);
        }
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
        try {
            $this->exec('co -q -f -r' . escapeshellarg(trim($revision)) . " {$filepath} 2>/dev/null");
        } catch (midcom_error $e) {
            $e->log();
        } finally {
            if (!file_exists($filepath)) {
                return [];
            }
        }

        $data = file_get_contents($filepath);
        $this->run_command("rm -f {$filepath}");

        $mapper = new midcom_helper_exporter_xml();
        return $mapper->data2array($data);
    }

    protected function load_history() : array
    {
        $filename = $this->generate_filename() . ',v';
        if (!is_readable($filename)) {
            debug_add('file ' . $filename . ' is not readable, returning empty result', MIDCOM_LOG_INFO);
            return [];
        }
        $lines = $this->read_handle($this->config->get_bin_prefix() . 'rlog "' . $filename . '" 2>&1');
        $total = count($lines);
        $revisions = [];

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

    private function exec(string $command)
    {
        $this->run_command($this->config->get_bin_prefix() . $command);
    }
}
