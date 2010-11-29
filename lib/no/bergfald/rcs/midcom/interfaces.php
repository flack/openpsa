<?php
/**
 * @package no.bergfald.rcs
 * @author Tarjei Huse (tarjei - at -bergfald.no)
 */

/**
 * @package no.bergfald.rcs
 */
class no_bergfald_rcs_interface extends midcom_baseclasses_components_interface
{
    public function __construct()
    {
        $this->_autoload_libraries = array
        (
            'midcom.helper.xml',
        );
    }
}
?>