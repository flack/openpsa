<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */
if (extension_loaded('midgard2'))
{
    require_once MIDCOM_ROOT . '/compat/midgard1.php';
}
require_once MIDCOM_ROOT . '/compat/superglobal.php';
require_once MIDCOM_ROOT . '/compat/componentdata.php';

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