/**
 * Add a create new group modal to the page.
 *
 * @module     core_group/AjaxForms
 * @class      AjaxForms
 * @package    core_group
 * @copyright  2017 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'local_users/jquery.dataTables',
    'core/str',
    'core/modal_factory',
    'core/modal_events',
    'core/ajax',
    'jquery',
    'jqueryui'
], function(dataTable, Str, ModalFactory, ModalEvents, Ajax, $) {
    var courseallocation;
    return {
        init: function() {
            $('.allocate_button').prop( "disabled", true );
            $('.allocation_course_type_btn').prop( "disabled", true );
        },
        teamsearch: function(params) {
            var searchtype = params.searchtype;
            var search = params.searchvalue;
            if(typeof(search) == 'undefined'){
                return false;
            }
            var user = $("input[name='allocateuser']:checked").val();
            if(searchtype === 'users'){
                var target = '.departmentusers';
                var selectedcontent = $('#nominate_userslist').val();
                
                $.ajax({
                    type: "POST",
                    url: M.cfg.wwwroot + "/local/users/team_actions/courseallocation_ajax.php?action=searchdata&learningtype="+searchtype+'&search='+search,
                    // data: "action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                    success: function(data) {
                        $(target).html(data);
                    }
                });
            }else{
                var target = '.departmentcourses';
                var selecteduser = $('#nominate_userslist').val();
                var selectedcontent = $('#nominate_courseslist').val();
                var learningtype = $('#learning_type').val();
                var learningtype_search = $('input[name="search_learningtypes"]').val();
                // console.log(searchtype);
                if(searchtype != learningtype){
                    searchtype = learningtype;
                }
                if(user == undefined || user == null){
                    var data = '<div class="alert alert-danger">Select a user to proceed.</div>'
                    // Str.get_string("select_user_toproceed", "local_users")
                    $(target).html(data);
                }else{
                    $.ajax({
                        type: "POST",
                        url: M.cfg.wwwroot + "/local/users/team_actions/courseallocation_ajax.php?action=searchdata&learningtype="+searchtype+'&search='+search+'&user='+selecteduser,
                        // data: "action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                        success: function(data) {
                            $(target).html(data);
                        }
                    });
                }
            }
        },
        select_type: function(params) {
            var user = params.user;
            var learningtype = params.learningtype;
            var pluginname = params.pluginname;

            $('input[name="search_learningtypes"]').val('');
            $('#learning_type').val(params.learningtype);
            
            if(user != null && typeof(user) != undefined){
                $('#nominate_userslist').val(user);
            }

            var selected_user = $('input[name="allocateuser"]:checked').val();

            // var selectedcontent = $('#nominate_courseslist').val();
            // var user = $("input[name='allocateuser']:checked").val();

            if(user == undefined && selected_user == null){

                ModalFactory.create({
                    title: Str.get_string('warning'),
                    type: ModalFactory.types.DEFAULT,
                    body: Str.get_string('team_select_user', 'local_users')
                }).done(function(modal) {
                    // Do what you want with your new modal.
                    modal.show();
                    // modal.setLarge();
                    // modal.getRoot().addClass('openLMStransition');
                    // modal.getRoot().animate({"right":"0%"}, 500);
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    modal.getRoot().on(ModalEvents.cancel, function() {
                        // setTimeout(function(){
                            modal.destroy();
                        // }, 1000);
                    });
                     //return modal;
                });
                // $( "#coursenominate_confirm").html("Please select Users.");
                // dlg1 = $( "#coursenominate_confirm").dialog({
                //         height: "auto",
                //         width: "auto",
                //         modal: true,
                //         closeOnEscape: false,
                //         buttons: {
                //             Ok: function () {
                //                 dlg1.dialog("close");
                //             }
                //         }
                // });
                return false;
            }else{
                user = selected_user;
            }

            switch(learningtype){
                case 1:
                    type = pluginname;
                break;
                case 2:
                    type = pluginname;
                break;
                case 3:
                    type = pluginname;
                break;
                case 4:
                    type = pluginname;
                break;
                default:
                    type = pluginname;
                break;
            }
            $('.allocation_course_type').html(type);
            $.ajax({
                    type: "GET",
                    datatype: "json",
                    url: M.cfg.wwwroot+"/local/users/team_actions/courseallocation_ajax.php?action=departmentmodules&learningtype=" + learningtype+'&user='+user,
                success: function(data) {
                    // data = JSON.parse(data);
                    $(".departmentcourses").html(data);
                }
            });
        },
        select_list: function(params) {
            var user = params.user;
            var courseid = params.courses;
            var learningtype = params.learningtype;
            var coursecheckedstatus = params.element.checked;

            var current_courses = $('#nominate_courseslist').val();
            var selected_courses = $('input[name="allocatecourse"]:checked').val();
            $('input[name="search_learningtypes"]').val('');
            
            var allocate = false;

            switch(learningtype){
                case 1:
                    if(courseid > 1){
                        if(coursecheckedstatus == true){
                            allocate = true;
                        }
                    }
                break;
                case 2:
                    if(courseid){
                        if(coursecheckedstatus == true){
                            allocate = true;
                        }
                    }
                break;
                case 3:
                    if(courseid > 1){
                        if(coursecheckedstatus == true){
                            allocate = true;
                        }
                    }
                break;
                case 4:
                    if(courseid > 1){
                        if(coursecheckedstatus == true){
                            allocate = true;
                        }
                    }
                break;
                default:
                    allocate = false;
                break;
            }
            
            if(allocate == true){
                $('.allocate_button').prop( "disabled", false);
                $('.allocation_course_type_btn').prop( "disabled", false);
            }else{
                $('.allocate_button').prop( "disabled", true);
                $('.allocation_course_type_btn').prop( "disabled", true);
            }

            switch(learningtype){
                case 1:
                    
                break;
                case 2:
                    
                break;
                case 3:
                    
                break;
                case 4:
                    
                break;
                default:
                    
                break;
            }
        },
        allocator: function() {
            // alert('test');
            // var user = params.user;
            // var learningtype = params.learningtype;
            
            var allocateuser = $('#nominate_userslist').val();
            var learningtype = $('#learning_type').val();
            var allocatecourse = [];
            $('input[name="search_learningtypes"]').val('');
            // var selected_courses = $('input[name="allocatecourse[]"]:checked').val();
             var selected_courses = $('input[name="allocatecourse[]"]:checked').each(function () {
                var courseid_selected = $(this).val();
                allocatecourse.push(courseid_selected);
             });
            
            if(!allocateuser.length){
                ModalFactory.create({
                    title: Str.get_string('warning'),
                    type: ModalFactory.types.DEFAULT,
                    body: Str.get_string('team_select_user', 'local_users')
                }).done(function(modal) {
                    // Do what you want with your new modal.
                    modal.show();
                    // modal.setLarge();
                    // modal.getRoot().addClass('openLMStransition');
                    // modal.getRoot().animate({"right":"0%"}, 500);
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    modal.getRoot().on(ModalEvents.cancel, function() {
                        // setTimeout(function(){
                            modal.destroy();
                        // }, 1000);
                    });
                });
                return false;
            }
            if(!allocatecourse.length){


                ModalFactory.create({
                    title: Str.get_string('warning'),
                    type: ModalFactory.types.DEFAULT,
                    body: Str.get_string('team_select_course_s', 'local_users')
                }).done(function(modal) {
                    // Do what you want with your new modal.
                    modal.show();
                    // modal.setLarge();
                    // modal.getRoot().addClass('openLMStransition');
                    // modal.getRoot().animate({"right":"0%"}, 500);
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    modal.getRoot().on(ModalEvents.cancel, function() {
                        // setTimeout(function(){
                            modal.destroy();
                        // }, 1000);
                    });
                });
                return false;
            }

            ModalFactory.create({
                title: Str.get_string('team_confirm_selected_allocation', 'local_users'),
                type: ModalFactory.types.SAVE_CANCEL,
                body: Str.get_string('allocate_confirm_allocate', 'local_users')
            }).done(function(modal) {
                // Do what you want with your new modal.
                modal.show();
                // modal.setLarge();
                // modal.getRoot().addClass('openLMStransition');
                // modal.getRoot().animate({"right":"0%"}, 500);
                modal.getRoot().on(ModalEvents.hidden, function() {
                    modal.destroy();
                });
                modal.getRoot().on(ModalEvents.save, function() {
                    $.ajax({
                            type: "post",
                            url: M.cfg.wwwroot + "/local/users/team_actions/courseallocation_ajax.php?action=courseallocate&learningtype="+learningtype+"&user="+allocateuser+"&allocatecourse="+allocatecourse,
                            success: function (data) {
                                // console.log(data);
                                // dlg3.dialog("close");
                                $('#allocation_notifications').html('<div class="alert alert-info" role="alert"><button type="button" class="close" data-dismiss="alert">×</button>Selected learning types has been allocated.</div>');
                                modal.hide();
                                // setTimeout(function(){
                                    modal.destroy();
                                    console.log(data);
                                    window.location.href = window.location.href;
                                // }, 5000);
                            }
                        });
                });
                modal.getRoot().on(ModalEvents.cancel, function() {
                    setTimeout(function(){
                        modal.destroy();
                    }, 1000);
                });
                 //return modal;
            });
        }

    };
});