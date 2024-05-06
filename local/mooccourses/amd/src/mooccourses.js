define([
    'local_mooccourses/responsive.bootstrap',
    'core/str',
    'core/modal_factory',
    'core/modal_events',
    'core/ajax',
    'jquery',
    'jqueryui'
], function(dataTable, Str, ModalFactory, ModalEvents, Ajax, $) {

      var mooccourses;
    return mooccourses = {
        init: function(args) {
            
        },
         soldcoursesDatatable: function(args){
            // alert(came);
            console.log(args);

            $('#viewsoldcourses').dataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "aaSorting": [],
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25,50,100, -1], [5,10,25, 50,100, "All"]],
                "aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 0 ] }],
                language: {
		    emptyTable: "No Mooc Courses available in table",
                    search: "_INPUT_",
                    searchPlaceholder: "Search",
                    "paginate": {
                        "next": ">",
                        "previous": "<"
                    }
                } ,
            "sAjaxSource": M.cfg.wwwroot + "/local/mooccourses/manual_tempcourse_processing.php?param="+args,
            });
            
           // console.log(args);
           
        },
               deleteConfirm: function(args) {
          //console.log(args);
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_mooccourses'
            },
            {
                key: 'deleteconfirm',
                component: 'local_mooccourses',
                param :args
            },
            {
                key: 'deleteallconfirm',
                component: 'local_mooccourses'
            },
            {
                key: 'delete'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText(s[3]);
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        console.log(args);
                        var params = {};
                        params.courseid = args.courseid;
                        params.contextid = args.contextid;
                    
                        var promise = Ajax.call([{
                            methodname: 'local_mooccourse_delete_mooccourse',
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
        }
    };
});
