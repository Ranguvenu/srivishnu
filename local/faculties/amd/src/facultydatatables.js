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
    'local_faculties/jquery.dataTables',
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
        facultyDatatable: function(args) {
            $('#faculty-index').dataTable({
              "searching": true,
                "responsive": true,
                "processing": true,
                "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
                "language": {
                    "emptyTable": "No Faculties available in table",
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                 "aaSorting": [],
                 "pageLength": 10,          
            });
        },
        facultyDelete: function(args) {
            //console.log(args);
            return Str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'suspendconfirm',
                component: 'local_faculties',
                param :args
            },
            {
                key: 'suspendallconfirm',
                component: 'local_faculties'
            },
            {
                key: 'confirm'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL, //added revathi
                    body: args.actionstatusmsg
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText(s[3]);
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        console.log(args);
                        var params = {};
                        params.id = args.id;
                        // params.contextid = args.contextid;
                    
                        var promise = Ajax.call([{
                            methodname: 'local_faculties_delete_faculty',
                            args: params
                        }]);
                        promise[0].done(function(resp) {
                            window.location.href = window.location.href;
                        }).fail(function(ex) {
                            // do something with the exception
                             console.log(ex);
                        });
                    }.bind(this));
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
    };
});