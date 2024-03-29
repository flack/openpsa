<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cached revision history for the object
 *
 * @package midcom.services.rcs
 */
class midcom_services_rcs_history
{
    private array $data;

    public function __construct(array $history)
    {
        $i = count($history);
        foreach ($history as &$entry) {
            $entry['version'] = (string) $i--;
        }
        $this->data = $history;
    }

    /**
     * Returns all versions (newest first)
     */
    public function all() : array
    {
        return $this->data;
    }

    /**
     * Check if a revision exists
     */
    public function version_exists(string $version) : bool
    {
        return array_key_exists($version, $this->data);
    }

    /**
     * Get the previous revision metadata
     */
    public function get_previous(string $version) : ?array
    {
        $versions = array_keys($this->data);
        $position = array_search($version, $versions, true);

        if (in_array($position, [false, count($versions) - 1], true)) {
            return null;
        }
        return $this->data[$versions[$position + 1]];
    }

    /**
     * Get the next revision metadata
     */
    public function get_next(string $version) : ?array
    {
        $versions = array_keys($this->data);
        $position = array_search($version, $versions, true);

        if (in_array($position, [false, 0], true)) {
            return null;
        }
        return $this->data[$versions[$position - 1]];
    }

    /**
     * Get the metadata of one revision.
     */
    public function get(string $revision) : ?array
    {
        return $this->data[$revision] ?? null;
    }

    /**
     * Get the metadata of the first (oldest) revision.
     */
    public function get_first() : ?array
    {
        return end($this->data) ?: null;
    }

    /**
     * Get the metadata of the last (newest) revision.
     */
    public function get_last() : ?array
    {
        return reset($this->data) ?: null;
    }
}
