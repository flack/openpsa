<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class wraps the _POST and _REQUEST and argv arrays and should be used
 * to fetch variables.
 *
 * @package midcom
 */
class midcom_request
{
    protected $vars = array();
    public function __construct($request_array = array(), $argv = array())
    {
        $this->vars = $request_array;
        $this->argv = $argv;
    }
    /**
     * Get a certain variable from the request variables
     * @return mixed the variable requested, or the default value (false if not set)
     * @param $var the name of the variable requested
     * @param $default the default value if the variable doesn't exist.
     */
    public function get($var, $default = false)
    {
        if (isset($this->vars[$var])) {
            return $this->vars[$var];
        }
        return $default;
    }

    /**
     * Set variable in request variable list.
     * @param $name name of variable
     * @param $value value to set
     */
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
    }
    /**
     *  Get a trimmed version of a variable.
     *  @return mixed the request variable, but trimmed.
     *  @param $name the variable name
     *  @param $default what to return if the variable does not exist.
     */
    public function getTrim($name, $default = "")
    {
        return trim($this->get($name, $default));
    }
}


/**
 * This one is thrown if the url does not map to anything
 *
 * @package midcom
 */
class midcom_url_notfound_exception extends Exception {    }

/**
 * this class is responsible for building the urlparser filters that will handle the
 * different parts of the parsing process.
 *
 *
 * @package midcom
 */
class midcom_urlparserfactory
{

    /**
     * Builds the list of filters that should be applied. Note that the filters are
     * applied in sequence and that the next filter will get information from the one
     * before.
     *
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Runs through all the different urlparsers building a definition of
     * which command to run.
     *
     * While most frameworks these days link parts of the urls directly to the
     * command name, we let the midcom be linked to the database instead. This makes
     * it easier for others to build the site without worrying about how to configure the command
     * interpreter.
     * @return midcom_url_paramcollector
     */
    function execute ($argv)
    {
        $urlstack = new midcom_url_urlstack($argv);
        $last_parser = new midcom_url_nullparser;
        foreach ($this->config as $parser_class) {
            $parser = new $parser_class($urlstack, $parser);
            if ($parser->end()) break;
            $last_parser = $parser;
        }
        return $parser->get_param_collector();
    }
}

/**
 * Implements a simple representation of the url as a stack
 *
 * @package midcom
 */
class midcom_url_urlstack
{
    protected $argv;
    public function __construct($argv)
    {
        $this->argv = $argv;
    }

    public function done()
    {
        return empty($this->argv);
    }

    public function get()
    {
        return $this->argv[0];
    }

    public function pop()
    {
        array_shift($this->argv);
    }
}
/**
 * This class is used to collect information to be used when executing the
 * request
 *
 * @package midcom
 */
class midcom_url_paramcollector {
    /**
     * The style to use for this request.
     */
    private $style = null;

    /**
     * If the style may be overridden. gets set to true if
     * we got midcom-substyle...
     * Public as I didn't want to implement getters and setters.
     */
    public $style_can_override = true;

    public $command = null;
    /**
     * A general container to pass configuration into
     */
    public $config = array();

    /**
     * An array of midcom_url_containers
     */
    public $url_objects = array();

    /**
     * Sets the current style of the execution environement.
     */
    public function set_style($style)
    {
        if ($this->style_can_override)
        {
            $this->style = $style;
        }
    }
    /**
     * Sets the content topic fo the midcom.
     */
    public function set_content_topic($topic) {
        $this->content_topic = $topic;
    }

    public function get_style() {
        return $this->style;
    }

    public function get_command() {
        return $this->command;
    }

    public function set_command($cmd) {
        $this->command = $cmd;
    }
    /**
     * Collects config options that are not covered by the other functions
     * @param $name name of option
     * @param $value value of option
     */
    public function add_config($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * Getter for config options
     * @param $name name of option
     * @param $default default value of option,
     */
    public function get_config($name, $default = false)
    {
        return (isset($this->config[$name])) ? $this->config[$name] : $default;
    }
}

/**
 * A parser for a part of a url
 * The parser will get in the url where its work starts and will work greedily on that
 * until it gets nothing and returns.
 *
 * @package midcom
 */
abstract class midcom_url_parser {
    /**
     * @var object midcom_url_paramcollector
     */
    public $param_collector = null;
    public function __construct($urlstack, $parser)
    {
        $this->param_collector = $parser->param_collector;
    }
    protected $end = false;
    public function end() {
        return $this->end;
    }

    public function get_param_collector() {
        if ($this->param_collector == null)
        {
            $this->param_collector= new midcom_url_paramcollector();
        }
        return $this->param_collector;
    }
}
/**
 * This class does not parse anything and used as a placeholder for the last_parser
 * variable above.
 *
 * @package midcom
 */
class midcom_url_nullparser  extends midcom_url_parser
{
    public function __construct() {}
}

/**
 * @package midcom
 */
class midcom_url_topicgetter {
    public function get_topic($topic_name, $up) {
        // todo!
    }
}
/**
 * This class parses any part of the url that is deemed to be related to topics
 *
 * @package midcom
 */
class midcom_url_topic extends midcom_url_parser
{
    public $topics = array();
    public function __construct($urlstack, $parser) {
        parent::__construct($urlstack,$parser);
        $this->db = $db ? $db : new midcom_url_topicgetter();
        $topic = true;
        // continue while we got words and they mean something.
        while (($next_word = $urlstack->get()) !== false && $topic )
        {
            $topic = $this->check_next_word($next_word);
            $this->topics[] = $topic;
        }
        $this->get_param_collector()->set_content_topic($this->topics[-1]);
        $this->get_param_collector()->set_command($this->topics[-1]);
    }

    public function check_next_word($word) {
        $up = ($this->topics !== array()) ? $this->topics[-1] : 0;
        $topic = $this->db->get_topic($word, $up);
        if (!$topic) {
            $this->end = true;
            return false;
        }
        return $topic;
    }
}
/**
 * This class parses prefixes like
 * /serveattachmentguid
 * /midcom-substyle
 * etc
 *
 * @package midcom
 */
class midcom_url_midcom extends midcom_url_parser
{
    public function __construct($urlstack, $parser) {
        if (substr($urlstack->get(), 0,7) == 'midcom-') {
            $this->parse_variable($urlstack->get());
        }
    }

    public function parse_variable($var) {
        list ($midcom, $command, $info ) = explode('-', $var, 3);
        $this->get_param_collector()->add_config('url_info', $info);
        switch ( $command ){
            case "permalink":
                $this->get_param_collector()->set_command('midcom_services_permalink_resolver');
                $this->done = true;
            case "log":
                throw new Exception('Not supported yet');
            case "serveattachmentguid":
                $this->get_param_collector()->set_command('midcom_services_attachmentserver');
                $this->done = true;
                break;
            case "substyle":
                $this->get_param_collector()->set_style($info);
                $this->get_param_collector()->style_can_override = false;
                break;
            case 'cache':
                $this->get_param_collector()->set_command('midcom_services_cache_invalidate');
                $this->done = true;
                break;
            case "exec":
                break;
            default:
                throw new Exception("Unknown MidCOM URL Property ignored: ");
        }
        $this->end = true;
    }
}
?>