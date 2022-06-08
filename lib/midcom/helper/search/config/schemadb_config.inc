'config' => [
 'name'        => 'config',
 'description' => 'Default Configuration Schema', /* This is a topic */
 'fields'      => [
  'results_per_page' => [
   'description' => 'results_per_page',
   'datatype' => 'text',
   'location' => 'config',
   'config_domain' => 'midcom.helper.search',
   'config_key' => 'results_per_page',
   'widget' => 'select',
   'widget_select_choices' => [
    '' => 'default setting',
    '5' => '5',
    '10' => '10',
    '15' => '15',
    '20' => '20',
    '30' => '30',
    '50' => '50',
   ],
  ],
  'search_help_message' => [
   'description' => 'search_help_message',
   'datatype' => 'text',
   'location' => 'config',
   'config_domain' => 'midcom.helper.search',
   'config_key' => 'search_help_message',
   'widget' => 'select',
   'widget_select_choices' => [
    '' => 'default setting',
    'lucene' => 'Lucene'
   ],
  ],
]
],