<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_reflector_reflectorTest extends openpsa_testcase
{
    /**
     * @dataProvider providerGet_class_label
     */
    public function testGet_class_label($classname, $label)
    {
        $reflector = new midcom_helper_reflector($classname);
        $this->assertEquals($label, $reflector->get_class_label());
    }

    public function providerGet_class_label()
    {
        return array(
            1 => array('org_openpsa_projects_project', 'Projects Project'),
            2 => array('midcom_db_article', 'Article'),
            3 => array('midcom_db_person', 'Person'),
            4 => array('org_openpsa_contacts_person_dba', 'Contacts Person'),
        );
    }

    /**
     * @dataProvider providerGet_label_property
     */
    public function testGet_label_property($classname, $property)
    {
        $reflector = new midcom_helper_reflector($classname);
        $this->assertEquals($property, $reflector->get_label_property());
    }

    public function providerGet_label_property()
    {
        return array(
            1 => array('org_openpsa_projects_project', 'title'),
            2 => array('midcom_db_article', 'title'),
            3 => array('midgard_topic', 'extra'),
            4 => array('midcom_db_snippet', 'name'),
            5 => array('midcom_db_member', 'guid'),
            6 => array('midcom_db_person', array('rname', 'id')),
            7 => array('org_openpsa_contacts_person_dba', 'rname'),
        );
    }

    /**
     * @dataProvider providerGet_object_label
     */
    public function testGet_object_label($classname, $data, $label)
    {
        $object = new $classname;
        foreach ($data as $field => $value) {
            $object->$field = $value;
        }
        $reflector = new midcom_helper_reflector($object);
        $this->assertEquals($label, $reflector->get_object_label($object));
    }

    public function providerGet_object_label()
    {
        return array(
            1 => array('org_openpsa_projects_project', array('title' => 'Project Title'), 'Project Title'),
            2 => array('org_openpsa_sales_salesproject_dba', array('title' => 'Test Article'), 'Test Article'),
            3 => array('midgard_topic', array('extra' => 'Test Topic'), 'Test Topic'),
            4 => array('midcom_db_snippet', array('name' => 'Test Snippet'), 'Test Snippet'),
            5 => array('org_openpsa_role', array(), ''),
            6 => array('midcom_db_person', array('firstname' => 'Firstname', 'lastname' => 'Lastname'), 'Lastname, Firstname'),
            7 => array('org_openpsa_contacts_person_dba', array('rname' => 'rname, test'), 'rname, test'),
        );
    }

    /**
     * @dataProvider providerGet_object_title
     */
    public function testGet_object_title($classname, $data, $label)
    {
        $object = new $classname;
        foreach ($data as $field => $value) {
            $object->$field = $value;
        }
        $reflector = new midcom_helper_reflector($object);
        $this->assertEquals($label, $reflector->get_object_title($object));
    }

    public function providerGet_object_title()
    {
        return array(
            1 => array('org_openpsa_projects_project', array('title' => 'Project Title'), 'Project Title'),
            2 => array('org_openpsa_sales_salesproject_dba', array('title' => 'Test Article'), 'Test Article'),
            3 => array('midgard_topic', array('extra' => 'Test Topic'), 'Test Topic'),
            4 => array('org_openpsa_role', array(), ''),
        );
    }

    /**
     * @dataProvider providerGet_title_property
     */
    public function testGet_title_property($classname, $property)
    {
        $object = new $classname;
        $reflector = new midcom_helper_reflector($classname);
        $this->assertEquals($property, $reflector->get_title_property($object));
    }

    public function providerGet_title_property()
    {
        return array(
            1 => array('org_openpsa_projects_project', 'title'),
            2 => array('midcom_db_article', 'title'),
            3 => array('midgard_topic', 'extra'),
            4 => array('midcom_db_member', ''),
            6 => array('org_openpsa_contacts_person_dba', 'lastname'),
        );
    }

    /**
     * @dataProvider providerGet_name_property
     */
    public function testGet_name_property($classname, $property)
    {
        $object = new $classname;
        $reflector = new midcom_helper_reflector($classname);
        $this->assertEquals($property, $reflector->get_name_property($object));
    }

    public function providerGet_name_property()
    {
        return array(
            1 => array('midcom_db_article', 'name'),
            2 => array('midgard_topic', 'name'),
            3 => array('midcom_db_snippet', 'name'),
            4 => array('org_openpsa_calendar_event_dba', 'extra'),
            5 => array('org_openpsa_contacts_person_dba', ''),
        );
    }

    /**
     * @dataProvider providerGet_create_icon
     */
    public function testGet_create_icon($classname, $icon)
    {
        $reflector = new midcom_helper_reflector($classname);
        $this->assertEquals($icon, $reflector->get_create_icon($classname));
    }

    public function providerGet_create_icon()
    {
        return array(
            1 => array('midcom_db_article', 'new-text.png'),
            2 => array('midgard_topic', 'new-dir.png'),
            3 => array('midcom_db_snippet', 'new-text.png'),
            4 => array('org_openpsa_organization', 'stock_people-new.png'),
            5 => array('org_openpsa_calendar_event_dba', 'stock_event_new.png'),
            6 => array('org_openpsa_contacts_person_dba', 'stock_person-new.png'),
        );
    }

    /**
     * @dataProvider providerGet_object_icon
     */
    public function testGet_object_icon($classname, $icon)
    {
        $reflector = new midcom_helper_reflector($classname);
        $object = new $classname;
        $this->assertEquals(MIDCOM_STATIC_URL . $icon, $reflector->get_object_icon($object, true));
    }

    public function providerGet_object_icon()
    {
        return array(
            1 => array('midcom_db_article', '/stock-icons/16x16/document.png'),
            2 => array('midgard_topic', '/stock-icons/16x16/stock_folder.png'),
            3 => array('midcom_db_snippet', '/stock-icons/16x16/script.png'),
            4 => array('org_openpsa_organization', '/stock-icons/16x16/stock_people.png'),
            5 => array('org_openpsa_calendar_event_dba', '/stock-icons/16x16/stock_event.png'),
            6 => array('org_openpsa_contacts_person_dba', '/stock-icons/16x16/stock_person.png'),
            7 => array('midcom_db_element', '/stock-icons/16x16/text-x-generic-template.png'),
        );
    }

    /**
     * @dataProvider providerGet_search_properties
     */
    public function testGet_search_properties($classname, $properties)
    {
        $reflector = new midcom_helper_reflector($classname);
        $search_properties = $reflector->get_search_properties();
        sort($search_properties);
        sort($properties);
        $this->assertEquals($properties, $search_properties);
    }

    public function providerGet_search_properties()
    {
        return array(
            1 => array('midcom_db_article', array('name', 'title')),
            2 => array('midgard_topic', array('name', 'title', 'extra')),
            3 => array('midcom_db_snippet', array('name')),
            4 => array('org_openpsa_organization', array('official', 'name')),
            5 => array('org_openpsa_calendar_event_dba', array('title')),
            6 => array('org_openpsa_person', array('lastname', 'title', 'username', 'firstname', 'email')),
        );
    }

    /**
     * @dataProvider providerClass_rewrite
     */
    public function testClass_rewrite($classname, $result)
    {
        $this->assertEquals($result, midcom_helper_reflector::class_rewrite($classname));
    }

    public function providerClass_rewrite()
    {
        return array(
            1 => array('org_openpsa_calendar_event_dba', 'org_openpsa_event'),
            3 => array('midgard_snippet', 'midgard_snippet'),
        );
    }

    /**
     * @dataProvider providerIs_same_class
     */
    public function testIs_same_class($classname1, $classname2, $result)
    {
        $this->assertEquals($result, midcom_helper_reflector::is_same_class($classname1, $classname2));
    }

    public function providerIs_same_class()
    {
        return array(
            1 => array('org_openpsa_calendar_event_dba', 'org_openpsa_event', true),
            3 => array('midgard_snippet', 'org_openpsa_invoices_billing_data_dba', false),
        );
    }

    public function testGet_object()
    {
        $object = $this->create_object('midcom_db_person');
        $object->delete();
        $object2 = midcom_helper_reflector::get_object($object->guid, 'midgard_person');
        $this->assertEquals($object->guid, $object2->guid);
    }


    /**
     * @dataProvider providerResolve_baseclass
     */
    public function testResolve_baseclass($classname1, $result)
    {
        $this->assertEquals($result, midcom_helper_reflector::resolve_baseclass($classname1));
    }

    public function providerResolve_baseclass()
    {
        return array(
            1 => array('org_openpsa_calendar_event_dba', 'org_openpsa_event'),
            2 => array('org_openpsa_calendar_event_member_dba', 'org_openpsa_eventmember'),
            3 => array('org_openpsa_contacts_person_dba', 'org_openpsa_person'),
        );
    }

    /**
     * @dataProvider providerGet_link_properties
     */
    public function testGet_link_properties($classname, $properties)
    {
        $reflector = new midcom_helper_reflector($classname);
        $this->assertEquals($properties, $reflector->get_link_properties());
    }

    public function providerGet_link_properties()
    {
        return array(
            1 => array('midcom_db_article', array(
                 'topic' => array(
                     'class' => 'midgard_topic',
                     'target' => 'id',
                     'parent' => true,
                     'up' => false,
                     'type' => MGD_TYPE_UINT,
                 ),
                 'up' => array(
                     'class' => 'midgard_article',
                     'target' => 'id',
                     'parent' => false,
                     'up' => true,
                     'type' => MGD_TYPE_UINT,
                 ),
            )),
            2 => array('midcom_db_snippet', array(
                 'snippetdir' => array(
                     'class' => 'midgard_snippetdir',
                     'target' => 'id',
                     'parent' => true,
                     'up' => false,
                     'type' => MGD_TYPE_UINT,
                 ),
             )),
             3 => array('org_openpsa_relatedto_dba', array(
                 'fromGuid' => array(
                     'class' => null,
                     'target' => 'guid',
                     'parent' => false,
                     'up' => false,
                     'type' => MGD_TYPE_GUID,
                 ),
                 'toGuid' => array(
                     'class' => null,
                     'target' => 'guid',
                     'parent' => false,
                     'up' => false,
                     'type' => MGD_TYPE_GUID,
                 ),
             )),
        );
    }
}
