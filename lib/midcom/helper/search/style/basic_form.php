<?php
$action = $data['router']->generate('result');
$advanced = $data['router']->generate('advanced');
$query = midcom_helper_xsspreventer::escape_attribute($data['query']);
?>
<form method='get' name='midcom_helper_search_form' action='&(action);' class='midcom.helper.search'>
<label for="midcom_helper_search_query">
<?php echo $data['l10n']->get('query');?>:
<input type='text' size='60' name='query' id='midcom_helper_search_query' value=&(query:h); />
</label>
<input type='hidden' name='type' value='basic' />
<input type='hidden' name='page' value='1' />
<input type='submit' name='submit' value='<?php echo $data['l10n']->get('search');?>' />
</form>
<p>
  <a href="&(advanced);&(data['params']);"><?php echo $data['l10n']->get('advanced search');?></a>
</p>