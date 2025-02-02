window.rcmail && rcmail.addEventListener('init', function(evt) {
    rcmail.register_command('plugin.cbits_vacation.save', function() {
          rcmail.gui_objects.vacform.submit();
    }, true);

    new rcube_text_editor({}, 'vacation-message');

    $('input[type=radio][name=active]').change(function () {
        update_hidden_fields(this.value)
    })

    update_hidden_fields($('input[type=radio][name=active]:checked').val())

    $('input:not(:hidden)').first().focus();
});


function update_hidden_fields(value) {
    switch(value) {
        case "off":
            $('#vacation-start_datetime-row').hide();
            $('#vacation-end_datetime-row').hide();
            $('#vacation-forwarding_address-row').hide();
            $('#vacation-message-row').hide();
            break;
        case "on-dates":
            $('#vacation-start_datetime-row').show();
            $('#vacation-end_datetime-row').show();
            $('#vacation-forwarding_address-row').show();
            $('#vacation-message-row').show();
            break;
        case "on-indef":
            $('#vacation-start_datetime-row').hide();
            $('#vacation-end_datetime-row').hide();
            $('#vacation-forwarding_address-row').show();
            $('#vacation-message-row').show();
            break;
        default:
    }
}