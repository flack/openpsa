<?php
/**
 * @package openpsa.createphp
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\createphp;

/**
 * MidCOM CreatePHP integration
 *
 * @package openpsa.createphp
 */
class createphp
{
    public static function add_head_elements()
    {
        $head = \midcom::get('head');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.position.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.button.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.dialog.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.effects.core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.effects.highlight.min.js');

        $prefix = MIDCOM_STATIC_URL . '/openpsa.createphp/';

        $head->add_jsfile($prefix . 'deps/modernizr.custom.80485.js"');
        $head->add_jsfile($prefix . 'deps/underscore-min.js');
        $head->add_jsfile($prefix . 'deps/backbone-min.js');
        $head->add_jsfile($prefix . 'deps/vie-min.js');
        $head->add_jsfile($prefix . 'deps/hallo-min.js');
        $head->add_jsfile($prefix . 'create-min.js');

        $head->add_stylesheet($prefix . 'deps/font-awesome/css/font-awesome.css');
        $head->add_stylesheet($prefix . 'themes/create-ui/css/create-ui.css');
        $head->add_stylesheet($prefix . 'themes/midgard-notifications/midgardnotif.css');
    }
}
?>