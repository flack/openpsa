<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
</ol>
</div>

    </div>
    <aside>
        <div class="contacts area">
            <?php
            if ($customer = $data['salesproject']->get_customer()) {
                echo "<h2>" . $data['l10n']->get('customer') . "</h2>\n";
                echo $customer->render_link();
            }
            if ($data['salesproject']->contacts) {
                echo "<h2>" . midcom::get()->i18n->get_string('contacts', 'org.openpsa.projects') . "</h2>\n";
                foreach (array_keys($data['salesproject']->contacts) as $contact_id) {
                    $person_card = org_openpsa_widgets_contact::get($contact_id);
                    $person_card->show();
                }
            } ?>
        </div>
        <?php
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());

        //TODO: Configure whether to show in/both and reverse vs normal sorting ?
        midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['salesproject']->guid}/both/normal/");
        ?>
    </aside>
</div>

<script type="text/javascript">
$('.deliverable_list .deliverable > .icon').click(function() {
    var container = jQuery(this).parent();

    container.find('.information').toggle('fast', function() {
        if (container.hasClass('expanded')) {
            container.removeClass('expanded');
            container.addClass('collapsed');
        } else {
            container.addClass('expanded');
            container.removeClass('collapsed');
        }
        $(window).trigger('resize');
    });
});
$(document).ready(function() {
    $('.deliverable_list .deliverable-sort').sortable({
		stop: function(event, ui) {
			var container = ui.item.parent(),
				data = {}, guid;
			container.find('> li').each(function(index) {
				guid = $(this).attr('id').replace('deliverable_', '');
				data[guid] = index;
			});
			$.post("&(prefix);salesproject/deliverables/sort/", {list: data});
		}
    });
});
</script>