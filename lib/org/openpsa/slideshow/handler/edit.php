<?php
/**
 * @package org.openpsa.slideshow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Edit handler
 *
 * @package org.openpsa.slideshow
 */
class org_openpsa_slideshow_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * @var string
     */
    private $_operation;

    /**
     * Response wrapper
     *
     * @var midcom_response_json
     */
    private $_response;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    /**
     * Handler for recreating derived images
     */
    public function _handler_recreate_folder_thumbnails()
    {
        $mc = midcom_db_topic::new_collector('up', $this->_topic->id);
        if ($subfolder_guids = $mc->get_values('guid')) {
            $qb = midcom_db_attachment::new_query_builder();
            $qb->add_constraint('parentguid', 'IN', $subfolder_guids);
            $qb->add_constraint('name', '=', org_openpsa_slideshow_image_dba::FOLDER_THUMBNAIL);
            foreach ($qb->execute() as $thumbnail) {
                $thumbnail->delete();
            }
        }
        return new midcom_response_relocate('');
    }

    /**
     * Handler for recreating derived images
     */
    public function _handler_recreate()
    {
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_order('position');
        $images = $qb->execute();
        $failed = 0;
        foreach ($images as $image) {
            if (   !$image->generate_image('thumbnail', $this->_config->get('thumbnail_filter'))
                || !$image->generate_image('image', $this->_config->get('image_filter'))) {
                $failed++;
            }
        }
        $successful = count($images) - $failed;
        if ($failed == 0) {
            $message = sprintf($this->_l10n->get('generated derived images for %s entries'), $successful);
            $type = 'info';
        } else {
            $message = sprintf($this->_l10n->get('generated derived images for %s entries, %s errors occurred'), $successful, $failed);
            $type = 'error';
        }
        midcom::get()->uimessages->add($this->_l10n->get($this->_component), $message, $type);
        return new midcom_response_relocate($this->router->generate('edit'));
    }

    /**
     * Handler for edit page
     */
    public function _handler_edit(array &$data)
    {
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_order('position');
        $data['images'] = $qb->execute();

        $head = midcom::get()->head;
        $head->enable_jquery_ui([
            'mouse', 'sortable',
            'progressbar', 'button', 'dialog'
        ]);

        midcom::get()->uimessages->add_head_elements();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/edit.js');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/' . $this->_component . '/edit.css');

        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => "",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('view'),
                MIDCOM_TOOLBAR_GLYPHICON => 'search',
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('recreate'),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recreate derived images'),
                MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
            ]
        ];
        $this->_view_toolbar->add_items($buttons);

        return $this->show('edit');
    }

    /**
     * Handler editing AJAX requests
     */
    public function _handler_edit_ajax(Request $request)
    {
        $this->_validate_request($request->request);

        $this->_response = new midcom_response_json;
        $this->_response->title = $this->_l10n->get($this->_component);

        $function = '_process_' . $this->_operation;
        try {
            $this->$function($request->request);
            $this->_response->success = true;
        } catch (midcom_error $e) {
            $this->_response->success = false;
            $this->_response->error = $e->getMessage();
        }

        return $this->_response;
    }

    private function _process_create(ParameterBag $post)
    {
        $image = new org_openpsa_slideshow_image_dba();
        $image->topic = $this->_topic->id;
        $image->title = $post->get('title');
        $image->description = $post->get('description');
        $image->position = $post->getInt('position');

        if (!$image->create()) {
            throw new midcom_error('Failed to create image: ' . midcom_connection::get_error_string());
        }
        $this->_response->position = $image->position;
        $this->_response->guid = $image->guid;
        if (isset($_FILES['image'])) {
            $this->_upload_image($_FILES['image'], $post->get('title', ''), $image);
        }
    }

    private function _process_update(ParameterBag $post)
    {
        $image = new org_openpsa_slideshow_image_dba($post->get('guid'));
        if ($image->topic !== $this->_topic->id) {
            throw new midcom_error_forbidden('Image does not belong to this topic');
        }
        $image->title = $post->get('title');
        $image->description = $post->get('description');
        $image->position = $post->getInt('position');

        if (!$image->update()) {
            throw new midcom_error('Failed to update image: ' . midcom_connection::get_error_string());
        }
    }

    private function _process_batch_update(ParameterBag $post)
    {
        $items = json_decode($post->get('items'));
        foreach ($items as $item) {
            $image = new org_openpsa_slideshow_image_dba($item->guid);
            if ($image->topic !== $this->_topic->id) {
                throw new midcom_error_forbidden('Image does not belong to this topic');
            }
            $image->title = $item->title;
            $image->description = $item->description;
            $image->position = $item->position;

            if (!$image->update()) {
                throw new midcom_error('Failed to update image: ' . midcom_connection::get_error_string());
            }
        }
    }

    private function _process_delete(ParameterBag $post)
    {
        $guids = explode('|', $post->get('guids'));
        if (empty($guids)) {
            return;
        }
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('guid', 'IN', $guids);
        foreach ($qb->execute() as $image) {
            if (!$image->delete()) {
                throw new midcom_error('Failed to delete image: ' . midcom_connection::get_error_string());
            }
        }
    }

    private function _validate_request(ParameterBag $post)
    {
        $this->_operation = $post->get('operation');

        switch ($this->_operation) {
            case 'batch_update':
                if (!$post->has('items')) {
                    throw new midcom_error('Invalid request');
                }
                break;
            case 'update':
                if (!$post->has('guid')) {
                    throw new midcom_error('Invalid request');
                }
                //Fall-through
            case 'create':
                if (!$post->has('title') || !$post->has('description') || !$post->has('position')) {
                    throw new midcom_error('Invalid request');
                }
                break;
            case 'delete':
                if (!$post->has('guids')) {
                    throw new midcom_error('Invalid request');
                }
                break;
            default:
                throw new midcom_error('Invalid request');
        }
    }

    private function _upload_image(array $file, string $title, org_openpsa_slideshow_image_dba $image)
    {
        $attachment = new midcom_db_attachment();
        $attachment->name = midcom_db_attachment::safe_filename($file['name']);
        $attachment->title = $title;
        $attachment->mimetype = $file['type'];
        $attachment->parentguid = $image->guid;
        if (   !$attachment->create()
            || !$attachment->copy_from_file($file['tmp_name'])) {
            throw new midcom_error('Failed to create attachment: ' . midcom_connection::get_error_string());
        }
        // apply filter for original image
        $filter_chain = $this->_config->get('original_filter');
        if (!empty($filter_chain)) {
            $imagefilter = new midcom_helper_imagefilter($attachment);
            $imagefilter->process_chain($filter_chain);
        }
        $this->_response->filename = $attachment->name;

        $image->attachment = $attachment->id;
        $image->generate_image('thumbnail', $this->_config->get('thumbnail_filter'));
        $image->generate_image('image', $this->_config->get('image_filter'));
        $image->update();
    }
}
