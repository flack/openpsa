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
    public $argv = [];
    private $argv_original = [];

    private $current_object = null;

    private $url = '';

    // Run-time cache of objects by URL
    private $objects = [];

    public function tokenize($url)
    {
        static $tokenized = [];
        $original_url = $url;
        if (isset($tokenized[$original_url])) {
            return $tokenized[$original_url];
        }

        $tokenized[$original_url] = [];
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
        $this->argv = $argv;
        $this->argv_original = $argv;
        $this->current_object = null;
        $this->url = '';
    }

    /**
     * Return current object pointed to by the parse URL
     */
    public function get_current_object()
    {
        if (!$this->current_object) {
            $this->current_object = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);

            // TODO: Remove
            $this->check_style_inheritance($this->current_object);
        }
        return $this->current_object;
    }

    /**
     * Return next object in URL path
     */
    public function get_object()
    {
        if (count($this->argv) == 0) {
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
            array_shift($this->argv);

            // Set as current object
            $this->url = $object_url;
            $this->current_object = $this->objects[$object_url];
            return $this->objects[$object_url];
        }

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('name', '=', $this->argv[0]);
        $qb->add_constraint('up', '=', $this->get_current_object()->id);

        if ($qb->count() == 0) {
            //last load returned ACCESS DENIED, no sense to dig deeper
            if ($qb->denied > 0) {
                midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                return false;
            }
            // No topics matching path, check for attachments
            $att_qb =  midcom_db_attachment::new_query_builder();
            $att_qb->add_constraint('name', '=', $this->argv[0]);
            $att_qb->add_constraint('parentguid', '=', $this->get_current_object()->guid);
            if ($att_qb->count() == 0) {
                // allow for handler switches to work
                return false;
            }

            // Remove this component from path
            array_shift($this->argv);

            // Set as current object
            $this->url = $object_url;
            $this->current_object = $att_qb->get_result(0);
            $this->objects[$object_url] = $this->current_object;
            return $this->objects[$object_url];
        }

        // Set to current topic
        $this->current_object = $qb->get_result(0);
        $this->objects[$object_url] = $this->current_object;

        // TODO: Remove
        $this->check_style_inheritance($this->current_object);

        // Remove this component from path
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
     * key and value which hold that have been parsed. $this->argv[0] will be dropped.
     *
     * On failure it returns false.
     *
     * @param string $namespace The namespace for which to search a variable
     * @return array            The key and value pair of the URL parameter, or false on failure.
     */
    public function get_variable($namespace)
    {
        if (count($this->argv) == 0) {
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

        return [
            $key => $value,
        ];
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
