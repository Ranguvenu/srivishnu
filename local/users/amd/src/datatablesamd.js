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
    'local_users/responsive.bootstrap',
    'core/str',
    'core/modal_factory',
    'core/modal_events',
    'core/ajax',
    'jquery',
    'jqueryui'
], function(dataTable, Str, ModalFactory, ModalEvents, Ajax, $) {
    var users;
    return users = {
        init: function(args) {
            
        },
        syncErrorDatatable: function(args) {
            var oTable = $('#errors').dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                "paginate": {
                    "previous": "<",
                    "next": ">"
                    }
                },
                "aaSorting": [],
                "bProcessing": true,
                "bServerSide": true,
                "pageLength": 5,
                "sAjaxSource":M.cfg.wwwroot + "/local/users/sync/error_processing.php",      
            });
        },
        syncStatsDatatable: function(args){
            var test = $("#syncdata").dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                },
                "aaSorting": [],
                "lengthMenu": [[5, 10, 25,50,100, -1], [5,10,25, 50,100, "All"]]
            });
            test.coFilter({
                "sPlaceHolder": ".filterarea",
                "aoColumns": [6],
                "columntitles": {6:"Filter By Date"},
                "filtertype": {6: "date"}
            });
        },
        userTableDatatable: function(args){
            
            // console.log(args);
            $("#manage_users1").dataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "language": {
                    "emptyTable": "No Users available in table",
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                 "aaSorting": [],
                 "bServerSide": true,
                 "pageLength": 10,
                 "sAjaxSource": M.cfg.wwwroot + "/local/users/manual_user_processing.php?param="+args,
            });
        },
        profileTableDatatable: function(){
            $("#profile_course_content").dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                },
                "aaSorting": [],
                "pageLength": 5,
            });
            //$("#profile_course_content thead").css('display','none');
        },
        profileClassroomTableDatatable: function(){
            $("#profile_classroom_content").dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                },
                "aaSorting": [],
                "pageLength": 5,
            });
            //$("#profile_classroom_content thead").css('display','none');
        },
        profileLearningplanTableDatatable: function(){
            $("#profile_learningplan_content").dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                },
                "aaSorting": [],
                "pageLength": 5,
            });
            $("#profile_learningplan_content thead").css('display','none');
        },
        profileProgramTableDatatable: function(){
            $("#profile_program_content").dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                },
                "aaSorting": [],
                "pageLength": 5,
            });
            $("#profile_program_content thead").css('display','none');
        },
        profileOnlinetestTableDatatable: function(){
            $("#profile_onlinetest_content").dataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                },
                "aaSorting": [],
                "pageLength": 5,
            });
            $("#profile_onlinetest_content thead").css('display','none');
        },
    };
});