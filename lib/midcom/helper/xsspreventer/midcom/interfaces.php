<?php
/**
 * Startup loads main class, which is used for all operations.
 *
 * @package midcom.helper.xsspreventer
 */
class midcom_helper_xsspreventer_interface extends midcom_baseclasses_components_interface
{

    function __construct()
    {
        parent::__construct();

        $this->_component = 'midcom.helper.xsspreventer';
    }

    function _on_initialize()
    {
        return true;
    }
}


?>