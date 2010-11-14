'config' => array (
 'name'        => 'config',
 'description' => 'Default Configuration Schema', /* This is a topic */
 'locktimeout' => 0, /* Don't lock this, we display the edit view always */
 'fields'      => array (

  'expiration_days' => array (
   'description' => 'expiration_days',
   'datatype' => 'text',
   'location' => 'config',
   'config_domain' => 'net.nehmer.buddylist',
   'config_key' => 'expiration_days',
  ),

 )
),
