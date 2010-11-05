<?php
/**
 *
 *
 * @package no.bergfald.rcs
 * @author Tarjei Huse (tarjei - at -bergfald.no)
 */

/**
 * @package no.bergfald.rcs
 */
class no_bergfald_rcs_interface extends midcom_baseclasses_components_interface
{

    function __construct()
    {
        parent::__construct();

        $this->_component = 'no.bergfald.rcs';
        $this->_autoload_files = array();
        $this->_autoload_libraries = array
        (
            'midcom.helper.xml',
        );
    }


}
?>