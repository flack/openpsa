<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: create.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Productlink database create product handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_productlink_create extends midcom_baseclasses_components_handler
{
    /**
     * The product which has been created
     *
     * @var org_openpsa_products_product_dba
     * @access private
     */
    var $_productlink = null;

    /**
     * The Controller of the productlink used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    var $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    var $_schemadb = null;

    /**
     * The schema to use for the new productklink.
     *
     * @var string
     * @access private
     */
    var $_schema = 'default';

    /**
     * The defaults to use for the new productlink.
     *
     * @var Array
     * @access private
     */
    var $_defaults = Array();

    /**
     * Simple default constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['indexmode'] =& $this->_indexmode;
        $this->_request_data['schema'] =& $this->_schema;
        $this->_request_data['schemadb'] =& $this->_schemadb;
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb_productlink'];

        $this->_defaults['productGroup'] = $this->_request_data['up'];
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     *
     * @access private
     */
    function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->defaults = $this->_defaults;
        $this->_controller->callback_object =& $this;
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function & dm2_create_callback (&$controller)
    {
        $this->_productlink = new org_openpsa_products_product_link_dba();

        if (isset($_POST['productGroup']))
        {
            $this->_request_data['up'] = (int) $_POST['productGroup'];
        }
        $this->_productlink->productGroup = $this->_request_data['up'];
        if (isset($_POST['product']))
        {
            $this->_request_data['product'] = (int) $_POST['product'];
        }
        $this->_productlink->product = $this->_request_data['product'];

        if (! $this->_productlink->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('We operated on this object:', $this->_productlink);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new productlink under product group #{$this->_request_data['up']}, cannot continue. Error: " . midcom_application::get_error_string());
            // This will exit.
        }

        return $this->_productlink;
    }

    /**
     * Displays an article edit view.
     *
     * Note, that the article for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation article
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_create($handler_id, $args, &$data)
    {
        //Check if args[0] is a product group code.
        if ((int)$args[0] == 0
            && strlen($args[0]) > 1)
        {
            $qb2 = org_openpsa_products_product_group_dba::new_query_builder();
            $qb2->add_constraint('code', '=', $args[0]);
            $qb2->add_order('code');
            $up_group = $qb2->execute();
            if (isset($up_group[0])
                && isset($up_group[0]->id))
            {
                //We just pick the first category here
                $qb = org_openpsa_products_product_group_dba::new_query_builder();
                $qb->add_constraint('up', '=', $up_group[0]->id);
                $qb->add_order('code','ASC');
                $qb->set_limit(1);
                $up_group = $qb->execute();
                if (isset($up_group[0])
                    && isset($up_group[0]->id))
                {
                    $this->_request_data['up'] = $up_group[0]->id;
                }
                else
                {
                    $this->_request_data['up'] = 0;
                }
            }
            else
            {
                $this->_request_data['up'] = 0;
            }
        }
        else
        {
            $this->_request_data['up'] = (int) $args[0];
        }

        if ($this->_request_data['up'] == 0)
        {
            $_MIDCOM->auth->require_user_do('midgard:create', null, 'org_openpsa_products_product_dba');
        }
        else
        {
            $parent = new org_openpsa_products_product_group_dba($this->_request_data['up']);
            if (   !$parent
                || !$parent->guid)
            {
                return false;
            }
            $parent->require_do('midgard:create');

            if ($parent->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_PRODUCT_GROUP_TYPE_SMART)
            {
                return false;
            }

            $data['parent'] = $parent;
        }

        $data['selected_schema'] = $args[1];
        if (!array_key_exists($data['selected_schema'], $data['schemadb_productlink']))
        {
            return false;
        }
        $this->_schema =& $data['selected_schema'];

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
/*                if ($this->_config->get('index_products'))
                {
                    // Index the product
                    $indexer = $_MIDCOM->get_service('indexer');
                    org_openpsa_products_viewer::index($this->_controller->datamanager, $indexer, $this->_topic);
                }*/

                $_MIDCOM->cache->invalidate($this->_productlink->guid);

                $_MIDCOM->relocate("productlink/{$this->_productlink->guid}/");
                // This will exit.

            case 'cancel':
                if ($this->_request_data['up'] == 0)
                {
                    $_MIDCOM->relocate('');
                }
                else
                {
                    $_MIDCOM->relocate("{$this->_request_data['up']}/");
                }
                // This will exit.
        }

        $this->_prepare_request_data();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        if ($this->_productlink)
        {
            $_MIDCOM->set_26_request_metadata($this->_productlink->metadata->revised, $this->_productlink->guid);
        }
        $this->_request_data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description));
        $_MIDCOM->set_pagetitle($this->_request_data['view_title']);

        $this->_update_breadcrumb_line();

        return true;
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_create($handler_id, &$data)
    {
        midcom_show_style('productlink_create');
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    function _update_breadcrumb_line()
    {
        $tmp = array();

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "productlink/create/",
            MIDCOM_NAV_NAME => $this->_request_data['view_title'],
        );

        if (isset($this->_request_data['parent']))
        {
            $group = $this->_request_data['parent'];
            $root_group = $this->_config->get('root_group');

            if (!$group)
            {
                return false;
            }

            $parent = $group;

            while ($parent)
            {
                $group = $parent;

                if (   $group->guid === $root_group
                    || !$group->guid)
                {
                    break;
                }

                if ($group->code)
                {
                    $url = "{$group->code}/";
                }
                else
                {
                    $url = "{$group->guid}/";
                }


                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => $url,
                    MIDCOM_NAV_NAME => $group->title,
                );
                $parent = $group->get_parent();
            }
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', array_reverse($tmp));
    }
}
?>