
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

function countup(start) {
    var holder = $('#org_openpsa_mypage_workingon_time'),
        last_update;

    function update() {
        var now = Math.round(new Date().getTime() / 1000) * 1000;
        if (now !== last_update) {
            let diff = (now - start) / 1000,
                diff_s = diff % 60,
                diff_m = Math.floor(diff / 60),
                diff_h = Math.floor(diff_m / 60),
                formatted = '';

            if (diff_h > 0) {
                formatted += diff_h + ':';
            }
            if (diff_m < 10) {
                diff_m = '0' + diff_m;
            }
            formatted += diff_m + ':';
            if (diff_s < 10) {
                diff_s = '0' + diff_s;
            }
            formatted += diff_s;

            holder.text(formatted);
            last_update = now;
        }
    }

    setInterval(update, 100);
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
