<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_status extends org_openpsa_widgets_status
{
    /**
     *
     * @var org_openpsa_invoices_invoice_dba
     */
    private $invoice;

    /**
     *
     * @var midcom_services_i18n_l10n
     */
    private $l10n;

    /**
     *
     * @var midcom_services_i18n_l10n
     */
    private $l10n_midcom;

    /**
     *
     * @param org_openpsa_invoices_invoice_dba $invoice
     */
    public function __construct(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.invoices');
        $this->l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
        $this->invoice = $invoice;
    }

    /**
     *
     * @return string
     */
    public function get_current_status()
    {
        switch ($this->invoice->get_status()) {
            case 'unsent':
                return $this->l10n->get('unsent');

            case 'open':
                return sprintf($this->l10n->get('due on %s'), $this->l10n->get_formatter()->date($this->invoice->due));

            case 'overdue':
                return '<span class="bad">' . sprintf($this->l10n->get('overdue since %s'), $this->l10n->get_formatter()->date($this->invoice->due)) . '</span>';

            case 'paid':
                return sprintf($this->l10n->get('paid on %s'), $this->l10n->get_formatter()->date($this->invoice->paid));

            case 'canceled':
                return sprintf($this->l10n->get('invoice canceled on %s'), $this->l10n->get_formatter()->date($this->invoice->paid));
        }
    }

    public function get_status_class()
    {
        return $this->invoice->get_status();
    }

    public function get_button()
    {
        $tooltip = midcom::get()->i18n->get_l10n('org.openpsa.relatedto')->get('add journal entry');
        $save_label = $this->l10n_midcom->get('save');
        $cancel_label = $this->l10n_midcom->get('cancel');
        return '<a id="add-journal-entry" data-guid="' . $this->invoice->guid . '" data-dialog-submit-label="' . $save_label . '" data-dialog-cancel-label="' . $cancel_label . '" title="' . $tooltip . "\">\n" .
            '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/list-add.png" alt="' . $tooltip . "\"></a>\n";
    }

    /**
     * @return array
     */
    public function get_history()
    {
        $entries = array_merge($this->get_status_entries(), $this->get_journal_entries());

        usort($entries, function ($a, $b) {
            if ($a['timestamp'] == $b['timestamp']) {
                if ($a['order'] == $b['order']) {
                    return 0;
                }
                return ($a['order'] > $b['order']) ? -1 : 1;
            }
            return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
        });

        return $entries;
    }

    /**
     *
     * @return array
     */
    private function get_status_entries()
    {
        $entries = array();
        if ($this->invoice->cancelationInvoice) {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            $cancelation_invoice = new org_openpsa_invoices_invoice_dba($this->invoice->cancelationInvoice);
            $cancelation_invoice_link = $prefix . 'invoice/' . $cancelation_invoice->guid . '/';
            $cancelation_invoice_link = "<a href=\"" . $cancelation_invoice_link . "\">" . $this->l10n->get('invoice') . " " . $cancelation_invoice->get_label() . "</a>";
            $entries[] = array
            (
                'timestamp' => $cancelation_invoice->metadata->created,
                'message' => sprintf($this->l10n->get('invoice got canceled by %s'), $cancelation_invoice_link),
                'order' => 4
            );
        } elseif ($this->invoice->paid) {
            $entries[] = array
            (
                'timestamp' => $this->invoice->paid,
                'message' => sprintf($this->l10n->get('marked invoice %s paid'), ''),
                'order' => 3
            );
        }
        if (   $this->invoice->due
            && (   (   $this->invoice->due < time()
                    && $this->invoice->paid == 0)
                || $this->invoice->due < $this->invoice->paid)) {
            $entries[] = array
            (
                'timestamp' => $this->invoice->due,
                'message' => $this->l10n->get('overdue'),
                'order' => 2
            );
        }

        if ($this->invoice->sent) {
            if ($mail_time = $this->invoice->get_parameter('org.openpsa.invoices', 'sent_by_mail')) {
                $entries[] = array
                (
                    'timestamp' => $mail_time,
                    'message' => sprintf($this->l10n->get('marked invoice %s sent per mail'), ''),
                    'order' => 1
                );
            } else {
                $entries[] = array
                (
                    'timestamp' => $this->invoice->sent,
                    'message' => sprintf($this->l10n->get('marked invoice %s sent'), ''),
                    'order' => 1
                );
            }
        }
        $entries[] = array
        (
            'timestamp' => $this->invoice->metadata->created,
            'message' => sprintf($this->l10n->get('invoice %s created'), ''),
            'order' => 0
        );
        return $entries;
    }

    private function get_journal_entries()
    {
        $entries = array();

        $mc = org_openpsa_relatedto_journal_entry_dba::new_collector('linkGuid', $this->invoice->guid);
        $rows = $mc->get_rows(array('title', 'metadata.created'));

        foreach ($rows as $row) {
            $entries[] = array
            (
                'timestamp' => strtotime((string) $row['created']),
                'message' => $row['title']
            );
        }
        return $entries;
    }
}
