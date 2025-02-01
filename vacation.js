window.rcmail && rcmail.addEventListener('init', function(evt) {
    rcmail.register_command('plugin.vacation-save', function() {
          rcmail.gui_objects.vacform.submit();
    }, true);

    $('input:not(:hidden)').first().focus();
});

window.tinymce && tinymce.init({
    selector: '#vacmessage',
    height: 350,
    theme: 'modern',
    plugins: 'print preview fullpage searchreplace autolink directionality visualblocks visualchars fullscreen image link media template table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists textcolor wordcount contextmenu colorpicker',
    toolbar1: 'formatselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat',
    menubar: 'edit insert view format table tools',
    image_advtab: true,
    templates: [],
    content_css: [
      '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
      '//www.tinymce.com/css/codepen.min.css'
    ],
    setup: function(ed){
      ed.on('init', trigger_enable_checkbox_change);
    },
})

function trigger_enable_checkbox_change() {
    if ($('#vacenabled').prop('checked')) {
        $('[id^=vac]').not('#vacenabled').parents('#vacation-form .propform tr').show();
    } else {
        $('[id^=vac]').not('#vacenabled').parents('#vacation-form .propform tr').hide();
    }
}

$(document).ready(function(){
    $('[id^=vac]').not('#vacenabled').parents('#vacation-form .propform tr').hide();
    $('#vacenabled').change(trigger_enable_checkbox_change);
    $('#vacstartdate').datepicker({dateFormat: "yy-mm-dd"});
    $('#vacenddate').datepicker({dateFormat: "yy-mm-dd"});
});
