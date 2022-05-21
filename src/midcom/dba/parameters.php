<?php
/**
 * @package midcom.dba
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\dba;

use midcom_connection;
use midgard_parameter;
use midcom;
use midcom\events\dbaevent;

/**
 * midcom parameter support
 *
 * @package midcom.dba
 */
trait parameters
{
    private static $parameter_cache = [];
    private static $parameter_all = [];

    /**
     * Return a parameter from the database.
     *
     * No event handlers are called here yet.
     *
     * @return ?string The parameter value or false otherwise (remember typesafe comparisons to protect against '' strings).
     */
    public function get_parameter(string $domain, string $name)
    {
        if (!$this->guid) {
            debug_add('Cannot retrieve information on a non-persistent object.', MIDCOM_LOG_INFO);
            return false;
        }

        if (isset(self::$parameter_cache[$this->guid][$domain])) {
            // We have this domain in cache already thanks to some parameter listing
            if (!isset(self::$parameter_cache[$this->guid][$domain][$name])) {
                return null;
            }
            return self::$parameter_cache[$this->guid][$domain][$name];
        }

        // Not in cache, query from MgdSchema API directly
        return $this->__object->get_parameter($domain, $name);
    }

    /**
     * List the parameters of an object. This will either list the parameters of
     * a single domain or the complete set of parameters, depending on the value
     * of $domain.
     *
     * It delegates the actual execution to two separate helper functions.
     *
     * No event handlers are called here yet.
     *
     * In case of a complete query, the result will be an associative array indexed
     * by the domain name and containing another array with parameter name/value pairs.
     * For example:
     *
     * <pre>
     * Array
     * (
     *     [Asgard] => Array
     *     (
     *         [lang] => en_US
     *         [act] => view
     *         [actloc] => tree
     *     )
     *     [AsgardTreeHost] => Array
     *     (
     *         [selected] => host0
     *     )
     * )
     * </pre>
     *
     * If you query only a single domain, the result will be a single associative
     * array containing the parameter name/value pairs. For example:
     *
     * <pre>
     * Array
     * (
     *     [lang] => en_US
     *     [act] => view
     *     [actloc] => tree
     * )
     * </pre>
     *
     * In both cases an empty Array will indicate that no parameter was found, while
     * false will indicate a failure while querying the database.
     *
     * @param string $domain The parameter domain to query, this may be null to indicate a full listing.
     */
    public function list_parameters(string $domain = null) : array
    {
        if (!$this->guid) {
            debug_add('Cannot retrieve information on a non-persistent object.', MIDCOM_LOG_INFO);
            return [];
        }

        if ($domain !== null) {
            return $this->list_parameters_domain($domain);
        }

        return $this->list_parameters_all();
    }

    /**
     * List the parameters of a single domain of an object.
     *
     * No event handlers are called here yet.
     *
     * @see list_parameters()
     */
    private function list_parameters_domain(string $domain) : array
    {
        if (!isset(self::$parameter_cache[$this->guid])) {
            self::$parameter_cache[$this->guid] = [];
        }

        if (isset(self::$parameter_cache[$this->guid][$domain])) {
            return self::$parameter_cache[$this->guid][$domain];
        }

        self::$parameter_cache[$this->guid][$domain] = [];

        $mc = midgard_parameter::new_collector('parentguid', $this->guid);
        $mc->set_key_property('name');
        $mc->add_value_property('value');
        $mc->add_constraint('domain', '=', $domain);
        $mc->execute();

        foreach ($mc->list_keys() as $name => $values) {
            self::$parameter_cache[$this->guid][$domain][$name] = $mc->get_subkey($name, 'value');
        }

        return self::$parameter_cache[$this->guid][$domain];
    }

    /**
     * List all parameters of an object.
     *
     * No event handlers are called here yet.
     *
     * @see list_parameters()
     */
    private function list_parameters_all() : array
    {
        if (!isset(self::$parameter_cache[$this->guid])) {
            self::$parameter_cache[$this->guid] = [];
        }

        if (!isset(self::$parameter_all[$this->guid])) {
            $mc = midgard_parameter::new_collector('parentguid', $this->guid);
            $mc->set_key_property('guid');
            $mc->add_value_property('domain');
            $mc->add_value_property('name');
            $mc->add_value_property('value');
            $mc->execute();

            foreach ($mc->list_keys() as $guid => $values) {
                $name = $mc->get_subkey($guid, 'name');
                $domain = $mc->get_subkey($guid, 'domain');

                if (!isset(self::$parameter_cache[$this->guid][$domain])) {
                    self::$parameter_cache[$this->guid][$domain] = [];
                }

                self::$parameter_cache[$this->guid][$domain][$name] = $mc->get_subkey($guid, 'value');
            }

            // Flag that we have queried all domains for this object
            self::$parameter_all[$this->guid] = true;
        }
        // Clean up empty arrays
        return array_filter(self::$parameter_cache[$this->guid], 'count');
    }

    /**
     * Set a parameter to the value specified.
     *
     * This is either a create or an update operation depending on whether there was
     * already a parameter of that domain/name present, or not.
     *
     * The user needs both update and parameter manipulation permission on the parent object for updates.
     *
     * @param string $value The Parameter value. If this is empty, the corresponding parameter is deleted.
     */
    public function set_parameter(string $domain, string $name, $value) : bool
    {
        if (!$this->guid) {
            debug_add('Cannot set parameters on a non-persistent object.', MIDCOM_LOG_WARN);
            return false;
        }
        if (empty($domain) || empty($name)) {
            debug_add('Parameter domain and name must be non-empty strings', MIDCOM_LOG_WARN);
            return false;
        }

        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:parameters')) {
            debug_add("Failed to set parameters, midgard:update or midgard:parameters on " . static::class . " {$this->guid} not granted for the current user.",
                  MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }

        // Set via MgdSchema API directly
        if (!$this->__object->set_parameter($domain, $name, (string) $value)) {
            return false;
        }

        if (isset(self::$parameter_cache[$this->guid][$domain])) {
            self::$parameter_cache[$this->guid][$domain][$name] = $value;
        }

        midcom::get()->dispatcher->dispatch(new dbaevent($this), dbaevent::PARAMETER);

        return true;
    }

    /**
     * Delete a parameter.
     *
     * Current implementation note: Deletion is not yet implemented in MgdSchema.
     * Therefore we set the parameters to an empty string for now, which should
     * have almost the same effect for most cases and thus is good enough for now.
     * Note, that empty string parameters are filtered in the getter methods until
     * this matter is resolved.
     *
     * The user needs both update and parameter manipulation permission on the parent object for updates.
     */
    public function delete_parameter(string $domain, string $name) : bool
    {
        if (!$this->guid) {
            debug_add('Cannot delete parameters on a non-persistent object.', MIDCOM_LOG_WARN);
            return false;
        }
        if (empty($domain) || empty($name)) {
            debug_add('Parameter domain and name must be non-empty strings', MIDCOM_LOG_WARN);
            return false;
        }

        if (   !$this->can_do('midgard:update')
            || !$this->can_do('midgard:parameters')) {
            debug_add("Failed to delete parameters, midgard:update or midgard:parameters on " . static::class . " {$this->guid} not granted for the current user.",
                  MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }

        // Invalidate run-time cache
        unset(self::$parameter_cache[$this->guid][$domain]);

        // Unset via MgdSchema API directly
        $result = $this->__object->set_parameter($domain, $name, '');

        midcom::get()->dispatcher->dispatch(new dbaevent($this), dbaevent::PARAMETER);

        return $result;
    }
}
