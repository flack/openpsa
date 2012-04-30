<script type="text/javascript">
/*
 * we need to determine the correct widget_id prefix here, loading from the parent
 * frame breaks when multiple choosers with creation support exist
 */
var widget_id = window.parent.jQuery('iframe[src^="' + window.location.pathname + '"]:visible').attr("id");
widget_id = widget_id.replace(/_creation_dialog_content/, '');

if ($('#container h1').length > 0)
{
	var title = $('#container h1').detach();
	if ($('#org_openpsa_toolbar').length > 0)
	{
	    $('#org_openpsa_toolbar').offset({top: 0});
	}
	window.parent.jQuery('#' + widget_id + '_creation_dialog').dialog('option', 'title', title.text());
}
</script>
<?php $data['controller']->display_form (); ?>