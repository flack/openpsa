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
    private org_openpsa_invoices_invoice_dba $invoice;

    private midcom_services_i18n_l10n $l10n;

    private midcom_services_i18n_l10n $l10n_midcom;

    public function __construct(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.invoices');
        $this->l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
        $this->invoice = $invoice;
    }

    public function get_current_status() : string
    {
        return match ($this->invoice->get_status()) {
            'unsent' => $this->l10n->get('unsent'),
            'open' => sprintf($this->l10n->get('due on %s'), $this->l10n->get_formatter()->date($this->invoice->due)),
            'overdue' => '<span class="bad">' . sprintf($this->l10n->get('overdue since %s'), $this->l10n->get_formatter()->date($this->invoice->due)) . '</span>',
            'paid' => sprintf($this->l10n->get('paid on %s'), $this->l10n->get_formatter()->date($this->invoice->paid)),
            'canceled' => sprintf($this->l10n->get('invoice canceled on %s'), $this->l10n->get_formatter()->date($this->invoice->paid))
        };
    }

    public function get_status_class() : string
    {
        return $this->invoice->get_status();
    }

    public function get_button() : string
    {
        $tooltip = midcom::get()->i18n->get_string('add journal entry', 'org.openpsa.relatedto');
        $save_label = $this->l10n_midcom->get('save');
        $cancel_label = $this->l10n_midcom->get('cancel');
        return '<a id="add-journal-entry" data-guid="' . $this->invoice->guid . '" data-dialog-submit-label="' . $save_label . '" data-dialog-cancel-label="' . $cancel_label . '" title="' . $tooltip . "\">\n" .
            '<i class="fa fa-plus" title="' . $tooltip . "\"></i></a>\n";
    }

    public function get_history() : array
    {
        $entries = array_merge($this->get_status_entries(), $this->get_journal_entries());

        usort($entries, function (array $a, array $b) {
            if ($a['timestamp'] == $b['timestamp']) {
                return $b['order'] <=> $a['order'];
            }
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $entries;
    }

    private function get_status_entries() : array
    {
        $entries = [];
        if ($this->invoice->cancelationInvoice) {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            $cancelation_invoice = new org_openpsa_invoices_invoice_dba($this->invoice->cancelationInvoice);
            $cancelation_invoice_link = $prefix . 'invoice/' . $cancelation_invoice->guid . '/';
            $cancelation_invoice_link = "<a href=\"" . $cancelation_invoice_link . "\">" . $this->l10n->get('invoice') . " " . $cancelation_invoice->get_label() . "</a>";
            $entries[] = [
                'timestamp' => $cancelation_invoice->metadata->created,
                'message' => sprintf($this->l10n->get('invoice got canceled by %s'), $cancelation_invoice_link),
                'order' => 4
            ];
        } elseif ($this->invoice->paid) {
            $entries[] = [
                'timestamp' => $this->invoice->paid,
                'message' => sprintf($this->l10n->get('marked invoice %s paid'), ''),
                'order' => 3
            ];
        }
        if (   $this->invoice->due
            && (   (   $this->invoice->due < time()
                    && $this->invoice->paid == 0)
                || $this->invoice->due < $this->invoice->paid)) {
            $entries[] = [
                'timestamp' => $this->invoice->due,
                'message' => $this->l10n->get('overdue'),
                'order' => 2
            ];
        }

        if ($this->invoice->sent) {
            if ($mail_time = $this->invoice->get_parameter('org.openpsa.invoices', 'sent_by_mail')) {
                $entries[] = [
                    'timestamp' => $mail_time,
                    'message' => sprintf($this->l10n->get('marked invoice %s sent per mail'), ''),
                    'order' => 1
                ];
            } else {
                $entries[] = [
                    'timestamp' => $this->invoice->sent,
                    'message' => sprintf($this->l10n->get('marked invoice %s sent'), ''),
                    'order' => 1
                ];
            }

            $reminders = $this->invoice->get_parameter('org.openpsa.invoices', 'sent_payment_reminder');
            if ($reminders) {
                $reminder_times = json_decode($reminders, true);
                foreach ($reminder_times as $reminder_time) {
                    $entries[] = [
                        'timestamp' => $reminder_time,
                        'message' => sprintf($this->l10n->get('marked invoice %s payment reminder sent'), ''),
                        'order' => 1
                    ];
                }
            }
        }
        $entries[] = [
            'timestamp' => $this->invoice->metadata->created,
            'message' => sprintf($this->l10n->get('invoice %s created'), ''),
            'order' => 0
        ];
        return $entries;
    }

    private function get_journal_entries() : array
    {
        $entries = [];

        $mc = org_openpsa_relatedto_journal_entry_dba::new_collector('linkGuid', $this->invoice->guid);
        $rows = $mc->get_rows(['title', 'metadata.created']);

        foreach ($rows as $row) {
            $entries[] = [
                'timestamp' => strtotime((string) $row['created']),
                'message' => $row['title'],
                'order' => 0
            ];
        }
        return $entries;
    }
}
