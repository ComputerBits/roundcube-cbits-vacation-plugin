window.rcmail && rcmail.addEventListener('init', function(evt) {
    rcmail.register_command('plugin.cbits_vacation.save', function() {
          rcmail.gui_objects.vacform.submit();
    }, true);

    $('input:not(:hidden)').first().focus();
});

// function trigger_enable_checkbox_change() {
//     if ($('#vacenabled').prop('checked')) {
//         $('[id^=vac]').not('#vacenabled').parents('#vacation-form .propform tr').show();
//     } else {
//         $('[id^=vac]').not('#vacenabled').parents('#vacation-form .propform tr').hide();
//     }
// }

// window.rcmail && rcmail.addEventListener('editor-load', function() {
//     trigger_enable_checkbox_change()
// })

$(document).ready(function(){
    new rcube_text_editor({}, 'vacation-message');
    // $('[id^=vac]').not('#vacenabled').parents('#vacation-form .propform tr').hide();
    // $('#vacenabled').change(trigger_enable_checkbox_change);
    // $('#vacstartdate').datepicker({dateFormat: "yy-mm-dd"});
    // $('#vacenddate').datepicker({dateFormat: "yy-mm-dd"});
});
