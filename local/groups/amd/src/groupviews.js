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
        groupsDatatable: function(args) {
           
             $('#viewgroups').dataTable({
                 "searching": true,
                        "responsive": true,
                        "processing": true,
                        "language": {
                            "emptyTable": "No Groups available in table",
                            "paginate": {
                                "previous": "<",
                                "next": ">"
                            },
                            "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                        },
                         "aaSorting": [],
                        
                         "pageLength": 10,
            "sAjaxSource": M.cfg.wwwroot + "/local/groups/manual_groups_processing.php?param="+args,
      
            });
        },
        groupsDelete: function(args) {
            //console.log(args);
            return Str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'suspendconfirm',
                component: 'local_groups',
                param :args
            },
            {
                key: 'suspendallconfirm',
                component: 'local_groups'
            },
            {
                key: 'confirm'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.CONFIRM,
                    body: args.actionstatusmsg
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText(s[3]);
                    modal.getRoot().on(ModalEvents.yes, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        console.log(args);
                        var params = {};
                        params.id = args.id;
                        // params.contextid = args.contextid;
                    
                        var promise = Ajax.call([{
                            methodname: 'local_groups_delete_groups',
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