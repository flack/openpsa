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
    public array $argv = [];

    private ?midcom_db_topic $current = null;

    private string $url = '';

    /**
     * Objects by URL
     *
     * @var midcom_db_topic[]
     */
    private array $objects = [];

    private midcom_core_context $context;

    public function __construct(midcom_core_context $context)
    {
        $this->context = $context;
        $url = $this->context->get_key(MIDCOM_CONTEXT_URI);

        $prefix_length = strlen(midcom_connection::get_url('prefix'));
        if (   $prefix_length > 1
            && str_starts_with($url, midcom_connection::get_url('prefix'))) {
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
     * Try to fetch a URL variable.
     *
     * Try to decode an midcom-<key>-<value> pair at the current URL
     * position.
     *
     * On success it returns the command it found.
     */
    public function find_urlmethod() : ?string
    {
        if (   empty($this->argv)
            || !str_starts_with($this->argv[0], 'midcom-')) {
            return null;
        }
        return '/' . implode('/', $this->argv);
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
