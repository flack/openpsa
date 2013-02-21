<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once MIDCOM_ROOT . '/compat/superglobal.php';
require_once MIDCOM_ROOT . '/compat/componentdata.php';

if (   extension_loaded('midgard2')
    && isset($_MIDGARD))
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

        'self' => '/',
        'prefix' => '',

        'host' => 0,
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
    );
}

/* ----- Include the site config ----- */
if (file_exists(MIDCOM_CONFIG_FILE_BEFORE))
{
    include MIDCOM_CONFIG_FILE_BEFORE;
}

/**
 * MidCOM superglobal
 *
 * @global midcom_compat_superglobal
 */
$_MIDCOM = new midcom_compat_superglobal;

/**
 * Component configuration array
 *
 * @global Array $GLOBALS['midcom_component_data']
 */
$GLOBALS['midcom_component_data'] = new midcom_compat_componentdata;
?>