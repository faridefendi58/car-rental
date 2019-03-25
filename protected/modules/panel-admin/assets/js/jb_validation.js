/**
 * JagungBakar Ajax Form Validation
 */
var jb_validation = {
    validate: function(form){
        form.find('.has-error').each(function () {
            $(this).removeClass('has-error');
            $(this).find('span.help-block').remove();
        });

        var has_error = 0;
        if (form.find('.required').length > 0) {
            form.find('.required').each(function () {
                if ($(this).val().length == 0) {
                    var input_parent = $(this).parent();
                    if ($(this).parent().hasClass('input-group')) {
                        var input_parent = $(this).parent().parent();
                    }
                    var label_name = input_parent.find('label');
                    if (label_name.length > 0) {
                        //label_name.find('span').remove();
                        label_name = label_name.html();
                        label_name = label_name.replace(/<(?:.*|\n)*?>/gm, '');
                    } else {
                        label_name = "Kolom ini";
                    }

                    var msg = "<span class=\"help-block\">"+ label_name +" tidak boleh dikosongi.</span>";
                    input_parent.addClass('has-error').append(msg);
                    has_error = has_error + 1;
                }
            });
        } else {alert('cuk');}

        return (has_error == 0)? true : false;
    }
}