<?php

/**
 * @package org.openpsa.sales
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * PDF Manager
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_sales_pdf
{
    /**
     * @var org_openpsa_sales_salesproject_dba
     */
    private $salesproject;

    public function __construct(org_openpsa_sales_salesproject_dba $salesproject)
    {
        $this->salesproject = $salesproject;
    }

    /**
     * @return midcom_db_attachment|NULL
     */
    public function get_attachment($autocreate = false)
    {
        $pdf_files = org_openpsa_helpers::get_dm2_attachments($this->salesproject,"pdf_file");
        if (!empty($pdf_files)) {
            return reset($pdf_files);
        }
            return null;
    }

    public function get_button_options()
    {
       if ($attachment = $this->get_attachment()) {

            $message = "There is already some attachment, do you want to overwrite it?";
            midcom\workflow\dialog::add_head_elements();
            $l10n_midcom = midcom::get()->i18n->get_l10n();
            $l10n = midcom::get()->i18n->get_l10n('org.openpsa.sales');

            return array(
                'data-dialog' => 'confirm',
                'data-dialog-heading' => $l10n->get('create_pdf'),
                'data-dialog-text' => $l10n->get($message),
                'data-dialog-confirm-label' => $l10n_midcom->get('confirm'),
                'data-dialog-cancel-label' => $l10n_midcom->get('cancel')
            );
         }

         return array();
    }

    public function render_and_attach()
    {
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('sales_pdfbuilder_class');
        if (!class_exists($client_class)) {
            throw new midcom_error('Could not find PDF renderer ' . $client_class);
        }

        // renders the pdf and attaches it to the salesproject
        $pdf_builder = new $client_class($this->salesproject);
        $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
        $filename = $generator->from_string($this->salesproject->title) . '.pdf';

        // tmp filename
        $tmp_file = midcom::get()->config->get('midcom_tempdir') . "/" . $filename;

        // render pdf to tmp filename
        $pdf_builder->render($tmp_file);

        $attachment = $this->get_attachment();
        if ($attachment) {
            $attachment->name = $filename;
            $attachment->title = $this->salesproject->title;
            $attachment->mimetype = "application/pdf";
            $attachment->update();
        } else {
            $attachment = $this->salesproject->create_attachment($filename, $this->salesproject->get_label(), "application/pdf");
            if (   !$attachment
                || !$this->salesproject->set_parameter("midcom.helper.datamanager2.type.blobs", "guids_pdf_file", $attachment->guid . ":" . $attachment->guid)) {
                throw new midcom_error("Failed to create sales attachment for pdf");
            }
        }

        if (!$attachment->copy_from_file($tmp_file)) {
            throw new midcom_error("Failed to copy pdf from " . $tmp_file . " to attachment");
        }

        if (!$attachment->set_parameter('org.openpsa.sales', 'auto_generated', md5_file($tmp_file))) {
            throw new midcom_error("Failed to create attachment parameters, last midgard error was: " . midcom_connection::get_error_string());
        }
        // only save potential salesproject changes when everything worked (also refreshes revised timestamp)
        $this->salesproject->update();
        return $attachment;
    }
}
