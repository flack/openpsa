<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom\datamanager\helper\autocomplete;
use midcom_db_topic;

class autocompleteTest extends openpsa_testcase
{
    public function test_get_querystring()
    {
        $data = [
            'component' => 'midcom',
            'class' => midcom_db_topic::class,
            'searchfields' => ['name'],
            'term' => 'test',
            'auto_wildcards' => 'end'
        ];
        $helper = new autocomplete($data);
        $this->assertEquals('test%', $helper->get_querystring());
    }

    public function test_get_results()
    {
        $topic = $this->create_object(midcom_db_topic::class, [
            'name' => uniqid('test'),
            'title' => 'Test'
        ]);

        $data = [
            'component' => 'midcom',
            'class' => \midcom_db_topic::class,
            'searchfields' => ['guid'],
            'term' => $topic->guid,
            'id_field' => 'id',
            'result_headers' => [[
                'name' => 'title'
            ], [
                'name' => 'name'
            ]]
        ];
        $helper = new autocomplete($data);
        $expected = [[
            'id' => $topic->id,
            'label' => $topic->name,
            'value' => $topic->name,
            'description' => $topic->title . ', ' . $topic->name
        ]];
        $this->assertEquals($expected, $helper->get_results());
    }

    public function test_get_results_invoice_customer()
    {
        $customer = $this->create_object(\org_openpsa_contacts_group_dba::class, [
            'official' => uniqid('Customer ')
        ]);
        $contact = $this->create_object(\org_openpsa_contacts_person_dba::class, [
            'firstname' => 'Firstname',
            'lastname' => uniqid('Lastname')
        ]);
        $invoice = $this->create_object(\org_openpsa_invoices_invoice_dba::class, [
            'customer' => $customer->id,
            'customerContact' => $contact->id
        ]);
        $invoice->number = $invoice->generate_invoice_number();
        $this->sudo($invoice->update(...));

        $this->create_user(true);
        $config = autocomplete::get_widget_config('invoice');
        $config['term'] = (string) $invoice->number;
        $helper = new autocomplete($config);
        $results = $helper->get_results();

        $this->assertCount(1, $results);
        $this->assertEquals($invoice->id, $results[0]['id']);
        $this->assertEquals($invoice->get_label(), $results[0]['label']);
        $this->assertEquals($contact->name . ', ' . $customer->official, $results[0]['description']);
    }
}
