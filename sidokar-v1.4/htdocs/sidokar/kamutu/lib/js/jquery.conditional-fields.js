/*
  jQuery Conditional Fields

 Version: 1.0.0
  Author: Arthur Shlain
    Repo: https://github.com/ArthurShlain/JQuery-Conditional-Fields
  Issues: https://github.com/ArthurShlain/JQuery-Conditional-Fields/issues
*/
(function($){

    $.fn.conditionalFields = function (action) {
        var $base = $(this);
        function toggle_field($field, trigger_value, val) {
            if ($.inArray(trigger_value, val) !== -1) {
                if ($field.css('display') === 'none')
                    $field.slideDown(500);
            }
            else {
                if ($field.css('display') !== 'none') {
                    $field.slideUp(500);
                }
            }
        }
        function update_fields() {
            var $fields = $base.find('[data-condition][data-condition-value]');
            var $field, condition, condition_values, $trigger, trigger_value;
            $.each($fields, function () {
                $field = $(this);
                condition = $field.attr('data-condition');
                condition_values = $field.attr('data-condition-value').toString().split(';');
                $trigger = $('[name="' + condition + '"]');
                if($trigger.is('[type="radio"]')){
                    $trigger = $('[name="' + condition + '"]:checked');
                }
                if ($trigger.length) {
                    if($trigger.length === 1){
                        trigger_value = $trigger.val().toString();
                        if($trigger.is('[type="checkbox"]')){
                            trigger_value = $trigger.prop( "checked" ) ? '1' : '0';
                        }
                        toggle_field($field, trigger_value, condition_values);
                    }
                    else {
                        var or = '0';
                        var and = '1';
                        $.each($trigger, function (id, trigger_instance) {
                            trigger_value = $(trigger_instance).val().toString();
                            if($trigger.is('[type="checkbox"]')){
                                trigger_value = $trigger.prop( "checked" ) ? '1' : '0';
                            }
                            if ($.inArray(trigger_value, condition_values) !== -1) {
                                or = '1';
                            }
                            else {
                                and = '0';
                            }
                        });
                        if($field.hasClass('condition-logical-or')){
                            trigger_value = or;
                        }
                        else {
                            trigger_value = and;
                        }
                        toggle_field($field, trigger_value, ['1']);
                    }
                }
                else {
                    if ($field.css('display') !== 'none'){
                        $field.slideUp(500);
                    }
                }
            });
        }
        function init_events(){
            $base.on('change', '.condition-trigger', function () {
                update_fields();
            });
            $base.on('click', '.condition-trigger-delayed', function () {
                var delay = $(this).attr('data-delay');
                setTimeout(function () {
                    update_fields();
                }, delay);
            });
        }
        if(action === 'init'){
            init_events();
            update_fields();
        }
        if(action === 'update'){
            update_fields();
        }
    }

}(jQuery));
