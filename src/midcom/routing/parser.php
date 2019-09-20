<?php
/**
 * @package midcom.routing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\routing;

use midcom_connection;
use midcom_core_context;
use midcom_db_topic;

/**
 * URL name parser that uses the topic structure
 *
 * @package midcom.routing
 */
class parser
{
    public $argv = [];

    /**
     * @var midcom_db_topic
     */
    private $current;

    private $url = '';

    /**
     * Objects by URL
     *
     * @var midcom_db_topic[]
     */
    private $objects = [];

    /**
     * @var midcom_core_context
     */
    private $context;

    public function __construct(midcom_core_context $context)
    {
        $this->context = $context;
        $url = $this->context->get_key(MIDCOM_CONTEXT_URI);

        $prefix_length = strlen(midcom_connection::get_url('prefix'));
        if (   $prefix_length > 1
            && substr($url, 0, $prefix_length) == midcom_connection::get_url('prefix')) {
            $url = substr($url, $prefix_length);
        }
        $url = trim($url, '/');
        if ($url != '') {
            $argv = explode('/', $url);
            foreach ($argv as $val) {
                if ($val !== '') {
                    $this->argv[] = $val;
                }
            }
        }
    }

    /**
     * @return string|boolean
     */
    public function find_urlmethod()
    {
        $match = $this->walk_variables('midcom');
        if (!empty($match)) {
            $url = "/$match";
            if (!empty($this->argv)) {
                $url .= '/' . implode('/', $this->argv);
            }

            return $url;
        }
        return false;
    }

    /**
     * Try to fetch a URL variable.
     *
     * Try to decode an <namespace>-<key>-<value> pair at the current URL
     * position. Namespace must be a valid MidCOM Path, Key must match the RegEx
     * [a-zA-Z0-9]* and value must not contain a "/".
     *
     * On success it returns the command it found. $this->argv[0] will be dropped.
     *
     * @param string $namespace The namespace for which to search a variable
     * @return string|boolean The command that was found, or false if there is no match
     */
    private function walk_variables($namespace)
    {
        if (   empty($this->argv)
            || strpos($this->argv[0], $namespace . '-') !== 0) {
            return false;
        }

        $tmp = substr($this->argv[0], strlen($namespace) + 1);

        $value = substr(strstr($tmp, "-"), 1);
        $key = substr($tmp, 0, strpos($tmp, "-"));

        // Remove this component from path
        $match = array_shift($this->argv);

        if ($key == 'substyle') {
            $this->context->set_key(MIDCOM_CONTEXT_SUBSTYLE, $value);
            debug_add("Substyle '$value' selected");
            return $this->walk_variables($namespace);
        }

        return $match;
    }

    /**
     * @throws \midcom_error
     */
    public function find_topic() : midcom_db_topic
    {
        $this->current = $this->context->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        if (!$this->current) {
            throw new \midcom_error('Root node missing.');
        }
        $this->walk_topics();

        // Initialize context
        $this->context->set_key(MIDCOM_CONTEXT_ANCHORPREFIX, midcom_connection::get_url('self') . $this->url);
        $this->context->set_key(MIDCOM_CONTEXT_COMPONENT, $this->current->component);
        $this->context->set_key(MIDCOM_CONTEXT_CONTENTTOPIC, $this->current);
        $this->context->set_key(MIDCOM_CONTEXT_URLTOPICS, $this->objects);

        return $this->current;
    }

    /**
     * Load topics recursively from argv
     */
    private function walk_topics()
    {
        if (empty($this->argv)) {
            // No arguments left
            return;
        }

        $object_url = $this->argv[0] . '/';
        if (!empty($this->url)) {
            $object_url = $this->url . $object_url;
        }
        if (!array_key_exists($object_url, $this->objects)) {
            $qb = midcom_db_topic::new_query_builder();
            $qb->add_constraint('name', '=', $this->argv[0]);
            $qb->add_constraint('up', '=', $this->current->id);

            if ($qb->count() == 0) {
                // last load returned ACCESS DENIED, no sense to dig deeper
                if ($qb->denied > 0) {
                    throw new \midcom_error_forbidden;
                }
                // allow for handler switches to work
                return;
            }
            // Set to current topic
            $this->objects[$object_url] = $qb->get_result(0);
        }
        // Remove this component from path
        array_shift($this->argv);

        // Set as current object
        $this->url = $object_url;
        $this->current = $this->objects[$object_url];
        $this->walk_topics();
    }
}
