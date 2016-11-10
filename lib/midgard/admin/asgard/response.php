<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Renderer for asgard views
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_response extends midcom_response
{
    private $_handler;

    private $_callback;

    public function __construct(midcom_baseclasses_components_handler $handler, $callback)
    {
        $this->_handler = $handler;
        $this->_callback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function send()
    {
        $context = midcom_core_context::get();
        $data =& $context->get_custom_key('request_data');
        midcom::get()->style->enter_context($context->id);

        if (isset($data['view_title']))
        {
            midcom::get()->head->set_pagetitle($data['view_title']);
        }

        if (!isset($_GET['ajax']))
        {
            midcom_show_style('midgard_admin_asgard_header');
            midcom_show_style('midgard_admin_asgard_middle');
        }

        $this->_handler->{$this->_callback}($data['handler_id'], $data);

        if (!isset($_GET['ajax']))
        {
            midcom_show_style('midgard_admin_asgard_footer');
        }

        midcom::get()->finish();
    }
}
