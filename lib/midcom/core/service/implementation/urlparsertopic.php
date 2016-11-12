<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * URL name parser that uses the topic structure
 *
 * @package midcom
 */
class midcom_core_service_implementation_urlparsertopic implements midcom_core_service_urlparser
{
    public $argc = 0;
    public $argv = array();
    private $argv_original = array();

    private $root_topic = null;
    private $current_object = null;

    private $url = '';

    // Run-time cache of objects by URL
    private $objects = array();

    public function __construct()
    {
        $this->root_topic = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
        $this->current_object = $this->root_topic;

        // TODO: Remove
        $this->check_style_inheritance($this->root_topic);
    }

    public function tokenize($url)
    {
        static $tokenized = array();
        $original_url = $url;
        if (isset($tokenized[$original_url])) {
            return $tokenized[$original_url];
        }

        $tokenized[$original_url] = array();
        if (strlen(midcom_connection::get_url('prefix')) > 1) {
            // FIXME: Replace only the first instance, there might be others matching the same string
            $url = str_replace(midcom_connection::get_url('prefix') . "/", '/', $url);
        }
        $url = trim($url, '/');
        if ($url != '') {
            $argv_tmp = explode('/', $url);
            $tokenized[$original_url] = array_filter($argv_tmp);
        }

        return $tokenized[$original_url];
    }

    /**
     * Check topic style inheritance rules for style loader
     *
     * @todo refactor style loader so this isn't needed
     */
    private function check_style_inheritance($topic)
    {
        if ($topic->styleInherit && $topic->style) {
            $GLOBALS['midcom_style_inherited'] = $topic->style;
        }
    }

    /**
     * Set the URL path to be parsed
     */
    public function parse($argv)
    {
        $this->argc = count($argv);
        $this->argv = $argv;
        $this->argv_original = $argv;

        $this->current_object = $this->root_topic;
        $this->url = '';
    }

    /**
     * Return current object pointed to by the parse URL
     */
    public function get_current_object()
    {
        return $this->current_object;
    }

    /**
     * Return next object in URL path
     */
    public function get_object()
    {
        if ($this->argc == 0) {
            // No arguments left
            return false;
        }

        if (empty($this->url)) {
            $object_url = "{$this->argv[0]}/";
        } else {
            $object_url = "{$this->url}/{$this->argv[0]}/";
        }
        if (array_key_exists($object_url, $this->objects)) {
            // Remove this component from path
            $this->argc -= 1;
            array_shift($this->argv);

            // Set as current object
            $this->url = $object_url;
            $this->current_object = $this->objects[$object_url];
            return $this->objects[$object_url];
        }

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('name', '=', $this->argv[0]);
        $qb->add_constraint('up', '=', $this->current_object->id);

        if ($qb->count() == 0) {
            //last load returned ACCESS DENIED, no sense to dig deeper
            if ($qb->denied > 0) {
                midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                return false;
            }
            // No topics matching path, check for attachments
            $att_qb =  midcom_db_attachment::new_query_builder();
            $att_qb->add_constraint('name', '=', $this->argv[0]);
            $att_qb->add_constraint('parentguid', '=', $this->current_object->guid);
            if ($att_qb->count() == 0) {
                // allow for handler switches to work
                return false;
            }

            $atts = $att_qb->execute();

            // Remove this component from path
            $this->argc -= 1;
            array_shift($this->argv);

            // Set as current object
            $this->url = $object_url;
            $this->current_object = $atts[0];
            $this->objects[$object_url] = $this->current_object;
            return $this->objects[$object_url];
        }

        $topics = $qb->execute();

        // Set to current topic
        $this->current_object = $topics[0];
        $this->objects[$object_url] = $this->current_object;
        if (   midcom::get()->config->get('symlinks')
            && !empty($this->current_object->symlink)) {
            try {
                $topic = midcom_db_topic::get_cached($this->current_object->symlink);
                $this->current_object = $topic;
            } catch (midcom_error $e) {
                debug_add("Could not get target for symlinked topic #{$this->current_object->id}: " .
                    $e->getMessage(), MIDCOM_LOG_ERROR);
            }
        }

        // TODO: Remove
        $this->check_style_inheritance($this->current_object);

        // Remove this component from path
        $this->argc -= 1;
        array_shift($this->argv);

        $this->url .= $this->objects[$object_url]->name . '/';
        return $this->objects[$object_url];
    }

    /**
     * Try to fetch a URL variable.
     *
     * Try to decode an <namespace>-<key>-<value> pair at the current URL
     * position. Namespace must be a valid MidCOM Path, Key must match the RegEx
     * [a-zA-Z0-9]* and value must not contain a "/".
     *
     * On success it returns an associative array containing the
     * key and value which hold that have been parsed. $this->argv[0] will be dropped
     * and $this->argc will be reduced by one.
     *
     * On failure it returns false.
     *
     * @param string $namespace The namespace for which to search a variable
     * @return array            The key and value pair of the URL parameter, or false on failure.
     */
    public function get_variable($namespace)
    {
        if ($this->argc == 0) {
            return false;
        }

        if (strpos($this->argv[0], $namespace . '-') !== 0) {
            return false;
        }

        $tmp = substr($this->argv[0], strlen($namespace) + 1);

        $value = substr(strstr($tmp, "-"), 1);
        $key = substr($tmp, 0, strpos($tmp, "-"));

        // Remove this component from path
        array_shift($this->argv);
        array_shift($this->argv_original);
        $this->argc -= 1;

        return array(
            $key => $value,
        );
    }

    /**
     * Return full URL that was given to the parser
     */
    public function get_url()
    {
        return midcom_connection::get_url('self') . "{$this->url}";
    }

    /**
     * Return parsed objects
     */
    public function get_objects()
    {
        return $this->objects;
    }
}
