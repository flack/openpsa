<?php
/**
 * RSS and Atom feed generator library
 * @link http://www.bitfolge.de/rsscreator-en.html
 * @license LGPL license
 * @package de.bitfolge.feedcreator
 */

/**
 * Startup loads main class, which is used for all operations.
 *
 * @package de.bitfolge.feedcreator
 */
class de_bitfolge_feedcreator_interface extends midcom_baseclasses_components_interface
{

    function __construct()
    {
        parent::__construct();

        $this->_component = 'de.bitfolge.feedcreator';
        $this->_autoload_files = Array('feedcreator.php');
    }
}
?>