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
    'core/str',
    'core/modal_factory',
    'core/modal_events',
    'core/ajax',
    'jquery',
    'jqueryui'
], function(Str, ModalFactory, ModalEvents, Ajax, $) {
    var team_approvals;
    return {
        init: function() {

        },
        requestsearch: function(params) {
            // console.log(params);
            var learningtype = params.learningtype;

            var changed_learningtype = $('input[name="approval_learning_type"]').val();
            if(changed_learningtype){
                learningtype = changed_learningtype;
            }
            var search = params.searchvalue;
            if(typeof(search) == 'undefined' || search == null){
                return false;
            }
            if(learningtype == 'elearning'){
                var target = '#team_requests_list';
                
                $.ajax({
                    type: "POST",
                    url: M.cfg.wwwroot + "/local/users/team_actions/team_approvals_ajax.php?action=searchdata&learningtype="+learningtype+'&search='+search,
                    // data: "action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                    success: function(data) {
                        $(target).html(data);
                    }
                });
            }else{//learningtype = classroom
                var target = '#team_requests_list';

                $.ajax({
                    type: "POST",
                    url: M.cfg.wwwroot + "/local/users/team_actions/team_approvals_ajax.php?action=searchdata&learningtype="+learningtype+'&search='+search,
                    // data: "action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                    success: function(data) {
                        $(target).html(data);
                    }
                });
            }
        },
        select_learningtype: function(params) {
            var learningtype = params.learningtype;
            var pluginname = params.pluginname;

            $('input[name="approval_learning_type"]').val(learningtype);
            $('input[name="search_requests"]').val('');
            $('.team_learningtype_dropdown').html(pluginname);
            var target = '#team_requests_list';
            $.ajax({
                    type: "POST",
                    url: M.cfg.wwwroot + "/local/users/team_actions/team_approvals_ajax.php?action=change_learningtype&learningtype="+learningtype,
                    // data: "action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                    success: function(data) {
                        $(target).html(data);
                    }
                });
        },
        select_request: function(params) {
            var learningtype = params.learningtype;
            var requestid = params.requestid;
            var coursecheckedstatus = params.element.checked;
            var allocate = false;

            if(requestid > 0){
                if(coursecheckedstatus == true){
                    allocate = true;
                }
            }

            if(allocate == true){
                $('.request_approval_btn').prop( "disabled", false);
            }else{
                $('.request_approval_btn').prop( "disabled", true);
            }

        },

        approve_request: function() {
            var learning_type = $('#approval_learning_type').val();
            var requeststoapprove = [];

            $('input[name="search_learningtypes"]').val('');
            $('input[name="team_requests[]"]:checked').each(function () {
                var requestid_selected = $(this).val();
                requeststoapprove.push(requestid_selected);
            });

            // console.log(requeststoapprove);
            if(!learning_type.length){
                ModalFactory.create({
                    title: Str.get_string('warning'),
                    type: ModalFactory.types.DEFAULT,
                    body: Str.get_string('select_learningtype', 'local_users')
                }).done(function(modal) {
                    modal.setSaveButtonText(Str.get_string('approve', 'local_users'));
                    modal.show();
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    modal.getRoot().on(ModalEvents.cancel, function() {
                        modal.destroy();
                    });
                });
                return false;
            }

            if(!requeststoapprove.length){
                ModalFactory.create({
                    title: Str.get_string('warning'),
                    type: ModalFactory.types.DEFAULT,
                    body: Str.get_string('select_requests', 'local_users')
                }).done(function(modal) {
                    modal.setSaveButtonText(Str.get_string('approve', 'local_users'));
                    modal.show();
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    modal.getRoot().on(ModalEvents.cancel, function() {
                        modal.destroy();
                    });
                });
                return false;
            }

            ModalFactory.create({
                title: Str.get_string('confirm'),
                type: ModalFactory.types.SAVE_CANCEL,
                body: Str.get_string('team_request_confirm', 'local_users')
            }).done(function(modal) {
                modal.setSaveButtonText(Str.get_string('approve', 'local_users'));
                modal.show();
                modal.getRoot().on(ModalEvents.hidden, function() {
                    modal.destroy();
                });
                modal.getRoot().on(ModalEvents.save, function() {
                    $.ajax({
                        type: "POST",
                        data: '',
                        url: M.cfg.wwwroot + "/local/users/team_actions/team_approvals_ajax.php?action=requestapproved&learningtype="+learning_type+"&requeststoapprove="+requeststoapprove,
                        success: function (data) {
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
