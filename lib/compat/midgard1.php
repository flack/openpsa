<?php
/**
 * Pure-PHP implementation of Midgard1 APIs required by OpenPSA that are not present in Midgard2.
 */
$GLOBALS['midgard_filters'] = array
(
    'h' => 'html',
    'H' => 'html',
    'p' => 'php',
    'u' => 'rawurlencode',
    'f' => 'nl2br',
    's' => 'unmodified',
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
 * @todo Caching the elements found by midcom_helper_misc::include_element() might be a good idea
 */
function mgd_cache_invalidate()
{
}

function mgd_template($var)
{
    return midcom_helper_misc::include_element($var);
}

/**
 * Include an element
 */
function mgd_element($name)
{
    return midcom_helper_misc::include_element($name);
}

/**
 * Show a variable
 */
function mgd_variable($variable)
{
    $variable_parts = explode(':', $variable[1]);
    $variable = '$' . $variable_parts[0];

    if (strpos($variable, '.') !== false)
    {
        $parts = explode('.', $variable);
        $variable = $parts[0] . '->' . $parts[1];
    }

    if (    isset($variable_parts[1])
         && array_key_exists($variable_parts[1], $GLOBALS['midgard_filters']))
    {
        switch ($variable_parts[1])
        {
           case 's':
               //display as-is
           case 'h':
           case 'H':
               //According to documentation, these two should do something, but actually they don't...
               $command = 'echo ' . $variable;
               break;
           case 'p':
               $command = 'eval(\'?>\' . ' . $variable . ')';
               break;
           default:
               $function = $GLOBALS['midgard_filters'][$variable_parts[1]];
               $command = $function . '(' . $variable . ')';
               break;
        }
    }
    else
    {
        $command = 'echo htmlentities(' . $variable . ')';
    }

    return "<?php $command; ?>";
}

/**
 * Preparse a string to handle element inclusion and variable
 *
 * @see mgd_preparse
 */
function mgd_preparse($code)
{
    return midcom_helper_misc::preparse($code);
}
?>
