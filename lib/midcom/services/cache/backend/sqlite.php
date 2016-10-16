<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * SQLite backend.
 *
 * @package midcom.services
 */
class midcom_services_cache_backend_sqlite extends midcom_services_cache_backend
{
    /**
     * The full directory filename.
     *
     * @var string
     */
    private $_db = null;

    /**
     * Sanitized version of database table name
     */
    private $_table = '';

    /**
     * This handler completes the configuration.
     */
    public function _on_initialize()
    {
        // We need to serialize data
        $this->_auto_serialize = true;

        // Opening database connection
        $this->_db = new SQLiteDatabase("{$this->_cache_dir}/{$this->_name}.sqlite");

        $this->_table = str_replace(array('.', '-'), '_', $this->_name);

        // Check if we have a DB table corresponding to current cache name
        $result = $this->_db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$this->_table}'");
        $tables = $result->fetchAll();
        if (count($tables) == 0)
        {
            $this->_db->query("CREATE TABLE {$this->_table} (key VARCHAR(255), value TEXT);");
            $this->_db->query("CREATE INDEX {$this->_table}_key ON {$this->_table} (key);");
        }
    }

    function _open($write = false) {}

    function _close() {}

    function _get($key)
    {
        $key = sqlite_escape_string($key);
        $results = $this->_db->query("SELECT value FROM {$this->_table} WHERE key='{$key}'");
        $results = $results->fetchAll();
        if (count($results) == 0)
        {
            return false; // No hit.
        }

        return $results[0]['value'];
    }

    function _put($key, $data)
    {
        $key = sqlite_escape_string($key);
        $data = sqlite_escape_string($data);
        $this->_db->query("REPLACE INTO {$this->_table} (key, value) VALUES ('{$key}', '{$data}')");
    }

    function _remove($key)
    {
        $key = sqlite_escape_string($key);
        $this->_db->query("DELETE FROM {$this->_table} WHERE key='{$key}'");
    }

    function _remove_all()
    {
        $this->_db->query("DELETE FROM $this->_table WHERE 1");
    }

    function _exists($key)
    {
        $key = sqlite_escape_string($key);
        $results = $this->_db->query("SELECT count(*) AS exists FROM {$this->_table} WHERE key=\"{$key}\"");

        $results = $results->fetchAll();
        if (empty($results))
        {
            return false;
        }

        return ($results[0]['exists'] > 0);
    }
}
