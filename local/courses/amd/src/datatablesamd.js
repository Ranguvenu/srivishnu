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
        coursesTableDatatable: function(args){
            // alert('args');
            $("#manage_courses").dataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "language": {
                    "emptyTable": "No Courses available in table",
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                 "aaSorting": [],
                 "bServerSide": true,
                 "pageLength": 10,
                 "sAjaxSource": M.cfg.wwwroot + "/local/courses/courses_processing.php?param="+args,
            });
        },
        coursesusers: function(){
            // alert('args');
            $("#coursesusers").dataTable({
                "searching": true,
                // "responsive": true,
                // "processing": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    // "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                 // "aaSorting": [],
                 // "bServerSide": true,
                "pageLength": 10
                 // "sAjaxSource": M.cfg.wwwroot + "/local/courses/courses_processing.php?param="+args,
            });
        },
    };
});