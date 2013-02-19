<?php
if (!midcom::get('auth')->user)
{
    _midcom_stop_request();
}

// String output mode
$x = 'false';
$y = 'false';

// Interface for getting the toolbar position
if (   !isset($_REQUEST['position_x'])
    || !isset($_REQUEST['position_y']))
{
    switch (midcom::get('config')->get('toolbars_position_storagemode'))
    {
        case 'parameter':
            $person = new midcom_db_person(midcom::get('auth')->user);
            $x = $person->get_parameter('midcom.services.toolbars', 'position_x');
            $y = $person->get_parameter('midcom.services.toolbars', 'position_y');
            break;

        case 'cookie':
            if (isset($_COOKIE['midcom_services_toolbars_position']))
            {
                $pos = $_COOKIE['midcom_services_toolbars_position'];
                $pos = explode('_', $pos);
                $x = $pos[0];
                $y = $pos[1];
            }
            break;

        case 'session':
            $session = new midcom_services_session('midcom.services.toolbars');
            $x = $session->get('position_x');
            $y = $session->get('position_y');
            break;
    }

    echo "{$x},{$y}";
    _midcom_stop_request();
}

// Interface for storing the toolbar position
switch (midcom::get('config')->get('toolbars_position_storagemode'))
{
    case 'parameter':
        $person = new midcom_db_person(midcom::get('auth')->user);
        $person->set_parameter('midcom.services.toolbars', 'position_x', $_REQUEST['position_x']);
        $person->set_parameter('midcom.services.toolbars', 'position_y', $_REQUEST['position_y']);
        break;

    case 'cookie':
        _midcom_setcookie('midcom_services_toolbars_position', $_REQUEST['position_x'] . '_' . $_REQUEST['position_y'], time() + 30 * 24 * 3600, midcom_connection::get_url('self'));
        break;

    case 'session':
        $session = new midcom_services_session('midcom.services.toolbars');
        $session->set('position_x', $_REQUEST['position_x']);
        $session->set('position_y', $_REQUEST['position_y']);
        break;
}
?>