<?php
/**
 * @package org.openpsa.slideshow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Edit handler
 *
 * @package org.openpsa.slideshow
 */
class org_openpsa_slideshow_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * Response wrapper
     *
     * @var midcom_response_json
     */
    private $_response;

    public function _on_initialize()
    {
        midcom::get('auth')->require_valid_user();
    }

    /**
     * Handler for recreating derived images
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_recreate($handler_id, array $args, array &$data)
    {
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_order('position');
        $images = $qb->execute();
        $failed = 0;
        foreach ($images as $image)
        {
            if (   !$image->generate_image('thumbnail', $this->_config->get('thumbnail_filter'))
                || !$image->generate_image('image', $this->_config->get('image_filter')))
            {
                $failed++;
            }
        }
        $successful = sizeof($images) - $failed;
        if ($failed == 0)
        {
            $message = sprintf($this->_l10n->get('generated derived images for %s entries'), $successful);
            $type = 'ok';
        }
        else
        {
            $message = sprintf($this->_l10n->get('generated derived images for %s entries, %s errors occurred'), $successful, $failed);
            $type = 'error';
        }
        midcom::get('uimessages')->add($this->_l10n->get($this->_component), $message, $type);
        return new midcom_response_relocate('edit/');
    }

    /**
     * Handler method for edit page
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_order('position');
        $data['images'] = $qb->execute();

        $head = midcom::get('head');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.droppable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.sortable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.progressbar.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.services.uimessages/jquery.midcom_services_uimessages.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.timers.src.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.effect.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.effect-pulsate.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/edit.js');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/' . $this->_component . '/edit.css');
        $head->add_jquery_ui_theme(array('progressbar'));

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "recreate/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recreate derived images'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
            )
        );
    }

    /**
     * Show edit page
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('edit');
    }

    /**
     * Handler editing AJAX requests
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_edit_ajax($handler_id, array $args, array &$data)
    {
        $this->_validate_request();

        $this->_response = new midcom_response_json;
        $this->_response->title = $this->_l10n->get($this->_component);

        $function = '_process_' . $this->_operation;
        try
        {
            $this->$function();
            $this->_response->success = true;
        }
        catch (midcom_error $e)
        {
            $this->_response->success = false;
            $this->_response->error = $e->getMessage();
        }

        return $this->_response;
    }

    private function _process_create()
    {
        $image = new org_openpsa_slideshow_image_dba();
        $image->topic = $this->_topic->id;
        $image->title = $_POST['title'];
        $image->description = $_POST['description'];
        $image->position = $_POST['position'];

        if (!$image->create())
        {
            throw new midcom_error('Failed to create image: ' . midcom_connection::get_error_string());
        }
        $this->_response->position = $image->position;
        $this->_response->guid = $image->guid;
        if (isset($_FILES['image']))
        {
            $this->_upload_image($_FILES['image'], $image);
        }
    }

    private function _process_update()
    {
        $image = new org_openpsa_slideshow_image_dba($_POST['guid']);
        if ($image->topic !== $this->_topic->id)
        {
            throw new midcom_error_forbidden('Image does not belong to this topic');
        }
        $image->title = $_POST['title'];
        $image->description = $_POST['description'];
        $image->position = $_POST['position'];

        if (!$image->update())
        {
            throw new midcom_error('Failed to update image: ' . midcom_connection::get_error_string());
        }
    }

    private function _process_delete()
    {
        $guids = explode('|', $_POST['guids']);
        if (empty($guids))
        {
            return;
        }
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('guid', 'IN', $guids);
        $images = $qb->execute();
        foreach ($images as $image)
        {
            if (!$image->delete())
            {
                throw new midcom_error('Failed to delete image: ' . midcom_connection::get_error_string());
            }
        }
    }

    private function _validate_request()
    {
        if (empty($_POST['operation']))
        {
            throw new midcom_error('Invalid request');
        }
        $this->_operation = $_POST['operation'];

        switch ($this->_operation)
        {
            case 'update':
                if (!isset($_POST['guid']))
                {
                    throw new midcom_error('Invalid request');
                }
                //Fall-through
            case 'create':
                if (   !isset($_POST['title'])
                    || !isset($_POST['description'])
                    || !isset($_POST['position']))
                {
                    throw new midcom_error('Invalid request');
                }
                break;
            case 'delete':
                 if (empty($_POST['guids']))
                 {
                     throw new midcom_error('Invalid request');
                 }
                 break;
            default:
                throw new midcom_error('Invalid request');
        }
    }

    private function _upload_image(array $file, org_openpsa_slideshow_image_dba $image)
    {
        $attachment = new midcom_db_attachment();
        $attachment->name = midcom_db_attachment::safe_filename($file['name']);
        $attachment->title = $_POST['title'];
        $attachment->mimetype = $file['type'];
        $attachment->parentguid = $image->guid;
        if (   !$attachment->create()
            || !$attachment->copy_from_file($file['tmp_name']))
        {
            throw new midcom_error('Failed to create attachment: ' . midcom_connection::get_error_string());
        }
        $this->_response->filename = $attachment->name;

        $image->attachment = $attachment->id;
        $image->generate_image('thumbnail', $this->_config->get('thumbnail_filter'));
        $image->generate_image('image', $this->_config->get('image_filter'));
        $image->update();
    }
}
?>