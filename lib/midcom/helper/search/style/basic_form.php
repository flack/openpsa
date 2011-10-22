<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$query = midcom_helper_xsspreventer::escape_attribute($data['query']);
?>
<form method='get' name='midcom_helper_search_form' action='&(prefix);result/' class='midcom.helper.search'>
<label for="midcom_helper_search_query">
<?php echo $data['l10n']->get('query');?>:
<input type='text' size='60' name='query' id='midcom_helper_search_query' value=&(query:h); />
</label>
<input type='hidden' name='type' value='basic' />
<input type='hidden' name='page' value='1' />
<input type='submit' name='submit' value='<?php echo $data['l10n']->get('search');?>' />
</form>
<p>
  <a href="&(prefix);advanced/&(data['params']);"><?php echo $data['l10n']->get('advanced search');?></a>
</p>