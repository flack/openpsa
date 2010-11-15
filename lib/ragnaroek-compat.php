<?php
/**
 * Pure-PHP implementation of Midgard1 APIs required by OpenPSA that are not present in Midgard2.
 */
$GLOBALS['midgard_filters'] = array
(
    'h' => 'htmlentities',
    'u' => 'rawurlencode',
    'f' => 'nl2br',
);

/**
 * Register PHP function as string formatter to the Midgard formatting engine.
 * @see http://www.midgard-project.org/documentation/reference-other-mgd_register_filter/
 */
function mgd_register_filter($name, $function)
{
    $GLOBALS['midgard_filters']["x{$name}"] = $function;
}

/**
 * Return a string as formatted by a Midgard formatter
 * @see http://www.midgard-project.org/documentation/reference-other-mgd_format/
 */
function mgd_format($content, $name)
{
    if (!isset($GLOBALS['midgard_filters'][$name]))
    {
        return $content;
    }

    ob_start();
    call_user_func($GLOBALS['midgard_filters'][$name], $content);
    return ob_get_clean();
}

/**
 * Invalidate Midgard's element cache
 *
 * @todo Caching the elements found by mgd_element() might be a good idea
 */
function mgd_cache_invalidate()
{
}

function mgd_template($var)
{
    return mgd_element($var);
}

/**
 * Include an element
 */
function mgd_element($name)
{
    static $style = null;

    if (is_array($name))
    {
        $element = $name[1];
    }
    else
    {
        $element = $name;
    }
    // Sensible fallback if we don't have a style or ROOT element
    $root_fallback = '<html><head><?php $_MIDCOM->print_head_elements(); ?><title><?php echo $_MIDCOM->get_context_data(MIDCOM_CONTEXT_PAGETITLE); ?></title></head><body class="<?php echo $_MIDCOM->metadata->get_page_class(); ?>"><(content)><?php $_MIDCOM->uimessages->show(); $_MIDCOM->toolbars->show(); $_MIDCOM->finish(); ?></body></html>';

    switch ($element)
    {
        case 'title':
            return 'OpenPSA';
        case 'content':
            return '<(content)>';
        default:
            $element_file = OPENPSA2_THEME_ROOT . $_MIDGARD['theme'] . '/style' . $_MIDGARD['page_style'] . "/{$element}.php";

            if (!file_exists($element_file))
            {
                if ($element == 'ROOT')
                {
                    return $root_fallback;
                }
                return '';
            }
            $value = file_get_contents($element_file);
            return preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", 'mgd_element', $value);
    }
}

function mgd_is_element_loaded($element)
{
    return file_exists(OPENPSA2_THEME_ROOT . $_MIDGARD['theme'] . '/style/' . $_MIDGARD['page_style'] . "/{$element}.php");
}

/**
 * Show a variable
 */
function mgd_variable($variable)
{
    $variable_parts = explode(':', $variable[1]);

    $variable = $variable_parts[0];
    // TODO: Formatter support

    if (strpos($variable, '.') !== false)
    {
        $parts = explode('.', $variable);
        return "<?php echo \${$parts[0]}->{$parts[1]}; ?>";
    }
    return "<?php echo \${$variable_parts[0]}; ?>";
}

/**
 * Preparse a string to handle element inclusion and variable
 *
 * @see mgd_preparse
 */
function mgd_preparse($code)
{
    // Get style elements
    $code = preg_replace_callback("/<\\(([a-zA-Z0-9 _-]+)\\)>/", 'mgd_element', $code);
    // Echo variables
    $code = preg_replace_callback("%&\(([^)]*)\);%i", 'mgd_variable', $code);
    return $code;
}

/**
 * Set up necessary parts of the _MIDGARD superglobal
 */
function openpsa_prepare_superglobal()
{
    $_MIDGARD = array
    (
        'argv' => array(),

        'user' => 0,
        'admin' => false,
        'root' => false,

        'auth' => false,
        'cookieauth' => false,

        // General host setup
        'page' => 0,
        'debug' => false,

        'host' => null,
        'style' => 0,
        'author' => 0,
        'config' => array
        (
            'prefix' => '',
            'quota' => false,
            'unique_host_name' => 'openpsa',
            'auth_cookie_id' => 1,
        ),

        'schema' => array
        (
            'types' => array(),
        ),

        'theme' => 'OpenPsa2',
        'page_style' => '',
    );

    // Get the classes from PHP5 reflection
    $re = new ReflectionExtension('midgard2');
    $classes = $re->getClasses();
    foreach ($classes as $refclass)
    {
        $parent_class = $refclass->getParentClass();
        if (!$parent_class)
        {
            continue;
        }
        if ($parent_class->getName() == 'midgard_object')
        {
            $_MIDGARD['schema']['types'][$refclass->getName()] = '';
        }
    }

    $_MIDGARD_CONNECTION =& midgard_connection::get_instance();
}

function openpsa_prepare_topics()
{
    $openpsa_topics = array
    (
        'Calendar' => 'org.openpsa.calendar',
        'Contacts' => 'org.openpsa.contacts',
        'Documents' => 'org.openpsa.documents',
        'Expenses' => 'org.openpsa.expenses',
        'Invoices' => 'org.openpsa.invoices',
        'Products' => 'org.openpsa.products',
        'Projects' => 'org.openpsa.projects',
        'Sales' => 'org.openpsa.sales',
        'Wiki' => 'net.nemein.wiki',
    );
    $qb = new midgard_query_builder('midgard_topic');
    $qb->add_constraint('name', '=', 'openpsa');
    $qb->add_constraint('up', '=', 0);
    $topics = $qb->execute();
    if ($topics)
    {
        return $topics[0]->guid;
    }

    // Create a new root topic for OpenPSA
    $root_topic = new midgard_topic();
    $root_topic->name = 'openpsa';
    $root_topic->component = 'org.openpsa.mypage';
    $root_topic->extra = 'OpenPSA';
    if (!$root_topic->create())
    {
        throw new Exception('Failed to create root topic for OpenPSA: ' . midgard_connection::get_instance()->get_error_string());
    }

    foreach ($openpsa_topics as $title => $component)
    {
        $topic = new midgard_topic();
        $topic->name = strtolower($title);
        $topic->component = $component;
        $topic->extra = $title;
        $topic->up = $root_topic->id;
        $topic->create();
    }

    return $root_topic->guid;
}
?>
