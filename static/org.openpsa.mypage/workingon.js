
function show_loading() {
    $('#org_openpsa_mypage_workingon_widget .org_openpsa_mypage_workingon').hide();
    $('#org_openpsa_mypage_workingon_loading').show();
}

function send_working_on(action) {
    var description = $("#working_description").serialize(),
        task = $("#working_task_selection").val().replace(/[\[|"|\]]/g, ''),
        invoiceable = $('#working_invoiceable').is(':checked'),
        send_url = MIDCOM_PAGE_PREFIX + "workingon/set/";

    $.ajax({
        type: "POST",
        url: send_url,
        data: description + "&task=" + task + "&invoiceable=" + invoiceable + '&action=' + action,
        success: function(msg) {
            $("#org_openpsa_mypage_workingon_widget").html(msg);
        },
        error: function() {
            location.href = location.href;
        },
        beforeSend: function() {
            show_loading();
        }
    });
}

var org_openpsa_workingon = {
    setup_widget: function() {
        $('#org_openpsa_mypage_workingon_start')
            .prop("disabled", true)
            .on('click', function() {
                send_working_on('start');
            });

        $('#org_openpsa_mypage_workingon_stop')
            .on('click', function() {
                send_working_on('stop');
            });

        if ($('#working_task_selection').val() === '') {
            $('#org_openpsa_mypage_workingon_stop').hide();
        } else {
            $('#org_openpsa_mypage_workingon_start').hide();
        }
    },
    select: function(event, ui) {
        midcom_helper_datamanager2_autocomplete.select(event, ui);
        $('#org_openpsa_mypage_workingon_start').prop("disabled", $('#working_task_selection').val() === '');
    }
}
