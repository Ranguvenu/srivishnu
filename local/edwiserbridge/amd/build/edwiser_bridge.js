define(['jquery', 'core/ajax', 'core/url'], function (jQuery, ajax, url) {
    return {
        init: function ($params) {
            $(document).ready(function(){

                /**
                 * functionality to avoid space in the site name
                 */
                $('input[name^="wp_name"]').on({
                    keydown: function(e) {
                        if (e.which === 32)
                            return false;
                    },
                    change: function() {
                        this.value = this.value.replace(/\s/g, "");
                    }
                });


                /**
                 * functionality to test connection
                 */
                $("[id$=_eb_test_connection]").click(function(event){
                    event.preventDefault();
                    $(document.body).css({'cursor' : 'wait'});
                    var id = $(this).prop("id");
                    id = id.replace("eb_test_connection", '');
                    id = id.replace("id_eb_buttons", '');
                    index = id.replace(/\_/g, '');
                    var url = $("#id_wp_url_" + index).val();
                    var token = $("#id_wp_token_" + index).val();
                    var parent = $(this).parent().parent();
                    parent = parent.parent();

                    //display none the error div.
                    parent.find("#eb_test_conne_response").css("display", "none");

                    var promises = ajax.call([
                        {methodname: 'eb_test_connection', args: {wp_url: url, wp_token: token}}
                    ]);

                    promises[0].done(function(response) {
                        parent.find("#eb_test_conne_response").html(response.msg);
                        parent.find("#eb_test_conne_response").css("display", "block");

                        if (response.status == 1) {
                            parent.find("#eb_test_conne_response").addClass("eb-success-msg");
                            parent.find("#eb_test_conne_response").removeClass("eb-error-msg");
                        } else {
                            parent.find("#eb_test_conne_response").removeClass("eb-success-msg");
                            parent.find("#eb_test_conne_response").addClass("eb-error-msg");
                        }
                        $(document.body).css({'cursor' : 'default'});
                    }).fail(function(ex) {
                       // do something with the exception
                       $(document.body).css({'cursor' : 'default'});
                    });
                });



                /**
                 * functionality to remove site from the sites list
                 */
                $("[id$=_eb_remove_site]").click(function(event){
                    event.preventDefault();
                    var id = $(this).prop("id");
                    id = id.replace("eb_remove_site", '');
                    id = id.replace("id_eb_buttons", '');
                    index = id.replace(/\_/g, '');

                    $("#id_wp_url_" + index).val("");
                    $("#id_wp_token_" + index).val("");
                    $("#id_wp_name_" + index).val("");

                    //Hiding elemnts
                    onRemoveHideElemnts(index);
                    $("input[name='wp_remove["+ index +"]']").val("yes");
                });


                //Hide the elements removed from the remove button.
                function onRemoveHideElemnts(index)
                {
                    $("#id_wp_name_" + index).closest('fieldset').css("display", "none");
                }


                //Hiding js elements which are already removed.
                if ($("input[name='wp_remove[0]']").length) {
                    var repeatQty = $("input[name='eb_connection_setting_repeats']").val();
                    for (var i = 0; i < repeatQty; i++) {
                        if ("yes" == $("input[name='wp_remove["+ i +"]']").val()) {
                            onRemoveHideElemnts(i);
                        }
                        // $("input[name='wp_remove["+ i +"]']").val("no");
                    }
                }


                /**
                 * functionlaity to get site synch values on the site change
                 */
                $("#id_wp_site_list").on("change", function(){
                    var promises = ajax.call([
                        {methodname: 'eb_get_site_data', args: {site_index: $(this).val()}}
                    ]);

                    promises[0].done(function(response) {
                        $('#id_course_enrollment').prop('checked', response.course_enrollment);
                        $('#id_course_un_enrollment').prop('checked', response.course_un_enrollment);
                        $('#id_user_creation').prop('checked', response.user_creation);
                        $('#id_user_deletion').prop('checked', response.user_deletion);
                    }).fail(function(ex) {
                    });
                });
            });
        }
    };

});