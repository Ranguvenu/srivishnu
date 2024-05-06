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
    'local_program/dataTables.checkboxes',
    'core/str',
    'core/modal_factory',
    'core/modal_events',
    'core/ajax',
    'jquery',
    'jqueryui'
], function(dataTable, Str, ModalFactory, ModalEvents, Ajax, $) {
    var curriculum;
    return curriculum = {
        init: function(args) {
            this.AssignUsers(args);
         },
        curriculumDatatable: function(args) {
            params = [];
            params.action = 'viewcurriculums';
            params.curriculumstatus = args.curriculumstatus;
            var oTable = $('#viewcurriculums').dataTable({
                'bInfo': false,
                'processing': true,
                'serverSide': true,
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data": params
                },
                "bInfo" : false,
                "bLengthChange": false,
                "language": {
                    "paginate": {
                        "next": ">",
                        "previous": "<"
                    },
                    'processing': '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>'
                },
                "pageLength": 6
            });
        },
        SessionDatatable: function(args) {
            params = [];
            // console.log(args);
            params.action = 'viewcurriculumsessions';
            params.curriculumid = args.curriculumid;
            params.semesterid = args.semesterid;
            params.bclcid = args.bclcid;
            params.ccses_action = args.ccses_action;
            params.programid = args.programid;
            params.yearid = args.yearid;
            params.courseid = args.courseid;
            if(args.action != ''){
                params.tab = args.action;
            } else {
                params.tab = false;
            }

            var oTable = $('#viewcurriculumsessions').dataTable({
                'processing': true,
                'serverSide': true,
                "language": {
                    "paginate": {
                    "next": ">",
                    "previous": "<"
                    },
                    "processing": '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>',
                    "search": "",
                    "searchPlaceholder": "Search"
                },
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data": params
                },
                "responsive": true,
                "pageLength": 5,
                "bLengthChange": false,
                "bInfo" : false,
            });
            this.sessionenrol(args);
        },
        sessionenrol: function(args) {
            $(document).on('click', '.sessionenrol', function() {
                var sessionargs = $(this).data();
                // console.log(sessionargs);
                return Str.get_strings([{
                    key: 'confirmation',
                    component: 'local_program'
                },
                {
                    key: 'confirmschedulesession',
                    component: 'local_program'
                },
                {
                    key: 'confirmreschedulesession',
                    component: 'local_program'
                },
                {
                    key: 'confirmcancelsession',
                    component: 'local_program'
                },
                {
                    key: 'yes'
                }
                ]).then(function(s) {
                    console.log(s);
                    var body = s[1];
                    if (sessionargs.enrol == 2) {
                        body = s[2];
                    } else if (sessionargs.enrol == 3) {
                        body = s[3];
                    }
                    ModalFactory.create({
                        title: s[0],
                        type: ModalFactory.types.SAVE_CANCEL,
                        body: body
                    }).done(function(modal) {
                        this.modal = modal;
                        modal.setSaveButtonText(s[4]);
                        modal.getRoot().on(ModalEvents.save, function(e) {
                            e.preventDefault();
                            args.confirm = true;
                            var promise = Ajax.call([{
                                methodname: 'local_program_session_enrolments',
                                args: sessionargs
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
            });
        },
        CoursesDatatable: function(args) {
            params = [];
            params.action = 'viewcurriculumcourses';
            params.curriculumid = $('#viewcurriculumcourses').data('curriculumid');
            var oTable = $('#viewcurriculumcourses').dataTable({
                'processing': true,
                //'serverSide': true,
                "language": {
                    "paginate": {
                    "next": ">",
                    "previous": "<"
                    },
                    "processing": '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>',
                    "search": "",
                    "searchPlaceholder": "Search"
                },
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data":params
                },
                "responsive": true,
                "pageLength": 10,
            });
        },
        UsersDatatable: function(args) {
            params = [];
            params.action = 'viewcurriculumusers';
            params.curriculumid = $('#viewcurriculumusers').data('curriculumid');
            params.yearid = $('#viewcurriculumusers').data('yearid');
            var oTable = $('#viewcurriculumusers').dataTable({
                'processing': true,
                'serverSide': true,
                "language": {
                    "emptyTable":'No Users Enrolled to this program',
                    "paginate": {
                    "next": ">",
                    "previous": "<"
                    },
                    "processing": '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>',
                    "search": "",
                    "searchPlaceholder": "Search"
                },
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data":params
                },
                "responsive": true,
                "pageLength": 10,
            });
        },
        ProgramDatatable: function(args) {
            params = [];
            params.action = 'viewprogramenrolsers';
            params.curriculumid = $('#viewprogramenrolsers').data('curriculumid');
            params.yearid = $('#viewprogramenrolsers').data('yearid');
            var oTable = $('#viewprogramenrolsers').dataTable({
                'processing': true,
                'serverSide': true,
                "language": {
                    "emptyTable":'No Users Enrolled to this program',
                    "paginate": {
                    "next": ">",
                    "previous": "<"
                    },
                    "processing": '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>',
                    "search": "",
                    "searchPlaceholder": "Search"
                },
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data":params
                },
                "responsive": true,
                "pageLength": 10,
            });
        },
        viewinfoConfirm: function(args) {
              //Var returned_data is ONLY available inside this fn!
            ModalFactory.create({
                title: args.title,
                body: args.body,
                footer:'<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>'
              }).done(function(modal) {
                // Do what you want with your new modal.
                modal.show();
                    modal.getRoot().on(ModalEvents.hidden, function() {
                    // modal.getRoot().animate({"right":"-85%"}, 500);
                            // setTimeout(function(){
                            modal.destroy();
                        // }, 1000);
                    }.bind(this));
                    $('[data-action="cancel"]').on('click', function() {
                        modal.hide();
                        modal.destroy();

                    });
                 //return modal;
              });
        },
        deleteConfirm: function(args) {
            // console.log(args);
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_program'
            },
            {
                key: 'deleteconfirm',
                component: 'local_program',
                param: args.curriculumname,
            },
            {
                key: 'deleteallconfirm',
                component: 'local_program'
            },
            {
                key: 'delete'
            },
            {
                key: 'deletecourseconfirm',
                component: 'local_program'
            },
            {
                key: 'cannotdeleteall',
                component: 'local_program'
            },
            {
                key: 'cannotdeletesession',
                component: 'local_program'
            },
            {
                key: 'cannotdeletesemester',
                component: 'local_program'
            },
            {
                key: 'cannotdeletesemesteryear',
                component: 'local_program'
            },
            {
                key: 'confirmunassignfaculty',
                component: 'local_program'
            },
            {
                key: 'confirmunassignuser',
                component: 'local_program'
            },
            {
                key: 'confirmcannotunassignuser',
                component: 'local_program'
            }
            ]).then(function(s) {
                if (args.action == "deletecurriculum") {
                    s[1] = s[1];
                    var confirm = ModalFactory.types.SAVE_CANCEL;
                 } else if (args.action == "deletecurriculumcourse") {
                    s[1] = s[4];
                    var confirm = ModalFactory.types.SAVE_CANCEL;
                 } else if (args.action == "cannotdeletecurriculum") {
                    s[1] = s[5];
                    var confirm = ModalFactory.types.DEFAULT;
                 } else if (args.action == "cannotdeletesession") {
                    if(args.sessioncount == 1){
                        s[1] = Str.get_string('fixedsessionsvalidation', 'local_program');
                        s[0] = "Alert!";
                    }else{
                        s[1] = s[6];
                        s[0] = "Alert!";
                    }
                    var confirm = ModalFactory.types.CANCEL;
                 } else if (args.action == "cannotdeletesemester") {
                    s[1] = s[7];
                    var confirm = ModalFactory.types.DEFAULT;
                 } else if (args.action == "cannotdeletesemesteryear") {
                    s[1] = s[8];
                    var confirm = ModalFactory.types.DEFAULT;
                 } else if (args.action == "unassignfaculty") {
                    s[1] = s[9];
                    var confirm = ModalFactory.types.SAVE_CANCEL;
                 } else if (args.action == "unassignuser") {
                    s[1] = s[10];
                    s[3] = 'Unassign'; 
                    var confirm = ModalFactory.types.SAVE_CANCEL;
                 } else if (args.action == "cannotunassignuser") {
                    s[1] = s[11];
                    s[0] = "Alert!";
                    var confirm = ModalFactory.types.CANCEL;
                 } else if (args.action == "deleteclassroom") {
                    if(args['count'] > 0){
                        var attendancecount = args['count'];
                        var confirm = ModalFactory.types.CANCEL;
                        s[1] = Str.get_string('offlineclassroomdelvalidation', 'local_program');
                        // s[0] = Str.get_string('cannotdeletefaculty', 'local_faculties');
                        s[0] = "Alert!";
                    }else{
                        s[1] = Str.get_string('deleteclassroomconfirm', 'local_program', args.classname);
                        var confirm = ModalFactory.types.SAVE_CANCEL;
                    }
                 } else {
                    s[1] = s[2];
                    var confirm = ModalFactory.types.SAVE_CANCEL;
                 }
                ModalFactory.create({
                    title: s[0],
                    type: confirm,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                    if(args.action == "deleteclassroom" && args.count == 0){
                        modal.setSaveButtonText(s[3]);
                    }
                    if(args.action != "deleteclassroom" && args.action != "cannotdeletecurriculum" && args.action != "cannotdeletesession" && args.action != "cannotdeletesemester" && args.action != "cannotdeletesemesteryear" && args.action != "cannotunassignuser"){
                        modal.setSaveButtonText(s[3]);
                    }
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        args.confirm = true;

                        var promise = Ajax.call([{
                            methodname: 'local_program_' + args.action,
                            args: args
                        }]);
                        promise[0].done(function(resp) {
                            if(args.action == "deleteprogram"){
                                window.location.href = M.cfg.wwwroot + '/local/program/index.php';
                            } else if(args.action == "deletesession" || args.action == "unassignfaculty"){
                                document.location.reload();
                            } else if(args.action == "unassignuser"){
                                window.location.href = M.cfg.wwwroot + '/local/program/users.php?ccid='+args.curriculumid+'&prgid='+args.programid+'&yearid='+args.yearid;
                            } else {
                                window.location.href = M.cfg.wwwroot + '/local/program/view.php?ccid='+args.curriculumid+'&prgid='+args.programid;
                            }
                        }).fail(function(ex) {
                            // do something with the exception
                             console.log(ex);
                        });
                    }.bind(this));
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
        AssignUsers: function(args) {
            $('.usersselect').click(function() {
                var type = $(this).data('id');

                if (type === 'removeselect') {
                    $('input#remove').prop('disabled', false);
                    $('input#add').prop('disabled', true);
                } else if (type === 'addselect') {
                    $('input#remove').prop('disabled', true);
                    $('input#add').prop('disabled', false);
                }

                if ($(this).hasClass('select_all')) {
                    $('#' + type + ' option').prop('selected', true);
                } else if ($(this).hasClass('remove_all')) {
                    $('#' + type ).val('').trigger("change");
                }
            });
        },
        curriculumStatus: function(args) {
            return Str.get_strings([
            {
                key: 'confirmation',
                component: 'local_program'
            },
            {
                key: args.actionstatusmsg,
                component: 'local_program'
            },
            {
                key: 'yes'
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText(s[2]);
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        var promise = Ajax.call([{
                            methodname: 'local_curriculum_' + args.action,
                            args: args
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
    ManagecurriculumStatus: function(args) {
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_program'
            },
            {
                key: args.actionstatusmsg,
                component: 'local_program',
                param: args.curriculumname,
            },
            {
                key: 'deleteallconfirm',
                component: 'local_program'
            },
            {
                key: 'yes'
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
                        var promise = Ajax.call([{
                            methodname: 'local_program_managecurriculumStatus',
                            args: args
                        }]);
                        promise[0].done(function(resp) {
                            window.location.href = M.cfg.wwwroot + '/local/program/view.php?ccid='+args.curriculumid;
                        }).fail(function(ex) {
                            // do something with the exception
                             console.log(ex);
                        });
                    }.bind(this));
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
        load: function () {
        },
        unassignCourses: function(args){
            return Str.get_strings([{
                    key: 'confirm'
                },
                {
                    key: 'unassign_courses_confirm',
                    component: 'local_program',
                    param : args
                },
                {
                    key: 'unassign',
                    component:'local_program',
                },
                {
                    key: 'cannotunassign_courses_confirm',
                    component:'local_program',
                }]).then(function(s) {
                    if (args.action == "unassign_course") {
                        s[1] = s[1];
                        var confirm = ModalFactory.types.SAVE_CANCEL;
                    } else if (args.action == "cannotunassign_course") {
                        s[1] = s[3];
                        var confirm = ModalFactory.types.DEFAULT;
                    } else {
                         s[1] = s[1];
                        var confirm = ModalFactory.types.SAVE_CANCEL;
                    }
                    ModalFactory.create({
                        title: s[0],
                        type: confirm,
                        body: s[1]
                    }).done(function(modal) {
                        this.modal = modal;
                        if (args.action != "cannotunassign_course") {
                            modal.setSaveButtonText(s[2]);
                        }
                        modal.getRoot().on(ModalEvents.save, function(e) {
                            e.preventDefault();
                            params = {};
                            params.programid = args.programid;
                            params.curriculumid = args.curriculumid;
                            params.semesterid = args.semesterid;
                            params.yearid = args.yearid;
                            params.courseid = args.courseid;
                            var promise = Ajax.call([{
                                methodname: 'local_program_' + args.action,
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
                modal.show();
            }.bind(this));
        },
        ProgramsDatatable: function(args) {

            params = [];
            params.action = 'viewcurriculumprograms';
            /*var columnval='all';
            if(args.type==1){
                columnval=0;
            }*/
            params.type = args.type;
            params.options = args.options;
            var rows_selected = [];
            var table = $('#viewcurriculumprograms').DataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
                "language": {
                    "emptyTable": "No Programs available in table",
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                "aaSorting": [],
                "bServerSide": true,
                "pageLength": 10, 
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data": params
                }
            });
          //  if(args.type==1){
                // Handle click on checkbox
              //  $('#viewcurriculumprograms tbody').on('click', '.programcheckbox', function(e){
               //    var $row = $(this).closest('tr');
                    //console.log($row);
                   // Get row data
//var data = table.row($row).data();
               //  /   //console.log(data);
                   // Get row ID
                 //  var rowId = data[0];

                   // Determine whether row ID is in the list of selected row IDs
                  // var index = $.inArray(rowId, rows_selected);

                   // If checkbox is checked and row ID is not in list of selected row IDs
                  // if(this.checked && index === -1){
                  //    rows_selected.push(rowId);

                   // Otherwise, if checkbox is not checked and row ID is in list of selected row IDs
                 //  } else if (!this.checked && index !== -1){
                 //     rows_selected.splice(index, 1);
                  // }

                 //  if(this.checked){
                  //    $row.addClass('selected');
                 //  } else {
                 ///     $row.removeClass('selected');
                 //  }

                   // Update state of "Select all" control
                   // curriculum.updateDataTableSelectAllCtrl(table);

                   // Prevent click event from propagating to parent
                 //  e.stopPropagation();
               // });

                // Handle click on table cells with checkboxes
               // $('#viewcurriculumprograms').on('click', 'tbody td .programcheckbox, thead th .programcheckboxhead', function(e){
               //    $(this).parent().find('input[type="checkbox"]').trigger('click');
               // });

                // Handle click on "Select all" control
                //$('thead input[name="select_all"]', table.table().container()).on('click', function(e){
                 //  if(this.checked){
                 //     $('#viewcurriculumprograms tbody input[type="checkbox"]:not(:checked)').trigger('click');
                 ///  } else {
                 //     $('#viewcurriculumprograms tbody input[type="checkbox"]:checked').trigger('click');
                 //  }

                   // Prevent click event from propagating to parent
                 //  e.stopPropagation();
               // });

                // Handle table draw event
              //  table.on('draw', function(){
                   // Update state of "Select all" control
               //     curriculum.updateDataTableSelectAllCtrl(table);
               // });
                // Handle form submission event
                /*$('#frm-viewcurriculumprograms').on('submit', function(e){
                   var form = this;

                   // Iterate over all selected checkboxes
                   var check_rows=0;
                   $.each(rows_selected, function(index, rowId){

                    check_rows=rowId;
                      // Create a hidden element
                      $(form).append(
                          $('<input>')
                             .attr('type', 'hidden')
                             .attr('name', 'id[]')
                             .val(rowId)
                      );
                   });
                   // FOR DEMONSTRATION ONLY

                   // Output form data to a console
                   $('#viewcurriculumprograms-console').text($(form).serialize());
                   if (check_rows != 0) {
                        $("#frm-viewcurriculumprograms").load(M.cfg.wwwroot+"/local/program/index.php?formdata="+ $(form).serialize()+"");
                   }
                   // Remove added elements
                   $('input[name="id\[\]"]', form).remove();

                   // Prevent actual form submission
                   e.preventDefault();
                });*/
            //}
        },
        updateDataTableSelectAllCtrl:function(table){
                var $table             = table.table().node();
                var $chkbox_all        = $('tbody input[type="checkbox"]', $table);
                var $chkbox_checked    = $('tbody input[type="checkbox"]:checked', $table);
                var chkbox_select_all  = $('thead input[name="select_all"]', $table).get(0);

                // If none of the checkboxes are checked
                if($chkbox_checked.length === 0){
                   chkbox_select_all.checked = false;
                   if('indeterminate' in chkbox_select_all){
                      chkbox_select_all.indeterminate = false;
                   }

                // If all of the checkboxes are checked
                } else if ($chkbox_checked.length === $chkbox_all.length){
                   chkbox_select_all.checked = true;
                   if('indeterminate' in chkbox_select_all){
                      chkbox_select_all.indeterminate = false;
                   }

                // If some of the checkboxes are checked
                } else {
                   chkbox_select_all.checked = true;
                   if('indeterminate' in chkbox_select_all){
                      chkbox_select_all.indeterminate = true;
                   }
                }
        },
        FacultysDatatable: function(args) {
            params = [];
            params.action = 'viewcoursefaculty';
            params.yearid = $('#viewcoursefaculty').data('yearid');
            params.semesterid = $('#viewcoursefaculty').data('semesterid');
            params.courseid = $('#viewcoursefaculty').data('courseid');
            var oTable = $('#viewcoursefaculty').dataTable({
                'processing': true,
                'serverSide': true,
                "language": {
                    'emptyTable': 'No Faculty Available to this course',
                    "paginate": {
                    "next": ">",
                    "previous": "<"
                    },
                    "processing": '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>',
                    "search": "",
                    "searchPlaceholder": "Search"
                },
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data":params
                },
                "responsive": true,
                "pageLength": 5,
                "bLengthChange": false,
                "bInfo" : false,
            });
        },
        masterprogramchildpopup : function(args) {
            $.ajax({
                type: "POST",
                url:   M.cfg.wwwroot + '/local/program/ajax.php',
                data: { curriculumid: args.id, action:'masterprogramchildpopup',
                    sesskey: M.cfg.sesskey
                },
                success: function(returndata) {
                    //Var returned_data is ONLY available inside this fn!
                    ModalFactory.create({
                        title: args.title,
                        body: returndata
                      }).done(function(modal) {
                        // Do what you want with your new modal.

                        modal.show();
                        curriculum.chlidprogramsDatatable(args);
                         modal.setLarge();
                         modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            // modal.getRoot().animate({"right":"-85%"}, 500);
                                    // setTimeout(function(){
                                    modal.destroy();
                                // }, 1000);
                            }.bind(this));
                            $(".close").click(function(){
                                modal.hide();
                                modal.destroy();
                            });
                         //return modal;
                      });
                }
            });
        },
        chlidprogramsDatatable: function(args) {
            params = [];
            params.action = 'masterprogramchildpopup';
            params.curriculumid = args.id;
            params.stable = 0;
            var oTable = $('#chlidprograms').dataTable({
                'processing': true,
                'serverSide': true,
                "language": {
                    "paginate": {
                    "next": ">",
                    "previous": "<"
                    },
                    "processing": '<img src='+M.cfg.wwwroot + '/local/ajax-loader.svg>',
                    "search": "",
                    "searchPlaceholder": "Search"
                },
                'ajax': {
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/local/program/ajax.php',
                    "data":params
                },
                "responsive": true,
                "pageLength": 5,
                "bLengthChange": false,
                "bInfo" : false,
            });
        },
          
        programSuspend: function(args) {
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_program'
            },
            {
                key: 'suspendconfirm',
                component: 'local_program',
                param :args
            },
            {
                key: 'suspendallconfirm',
                component: 'local_program'
            },
            {
                key: 'confirm'
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
                        params.id = args.id;
                        params.contextid = args.contextid;
                    
                        var promise = Ajax.call([{
                            methodname: 'local_program_suspend_program',
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
        checkProgramStatus : function(args) {
            $.ajax({
                type: "POST",
                url:   M.cfg.wwwroot + '/local/program/ajax.php',
                data: { programid: args.pid, curriculumid: args.ccid, costcenter: args.costcenter, action:'programstatusvalidation',
                    sesskey: M.cfg.sesskey
                },
                success: function(returndata) {
                    //Var returned_data is ONLY available inside this fn!
                    if(returndata.finalstatus == 'true'){
                        ModalFactory.create({
                        title: "Confirm",
                        type: ModalFactory.types.SAVE_CANCEL,
                        body: Str.get_string('publishprogramconfirmationmessage', 'local_program', args.programname),
                        }).done(function(modal) {
                        this.modal = modal;
                        modal.setSaveButtonText('Confirm');
                        modal.getRoot().on(ModalEvents.save, function(e) {
                            modal.hide();
                            modal.destroy();
                            e.preventDefault();
                            args.confirm = true;
                            $.ajax({
                            type: "POST",
                            url:   M.cfg.wwwroot + '/local/program/ajax.php',
                            data: { programid: args.pid, curriculumid: args.ccid, costcenter: args.costcenter, action:'publishprogram',
                                sesskey: M.cfg.sesskey
                            },
                            success: function(response){
                                require(['core/notification'], function(notification) {
                                    notification.addNotification({
                                        message: "Program published Successfully",
                                        type: "success"
                                    });
                                });
                                document.body.scrollTop = 0;
                                document.documentElement.scrollTop = 0;
                                window.setTimeout(function(){document.location.reload()},5000);
                              //   ModalFactory.create({
                              //   // type: ModalFactory.types.SAVE,
                              //   title: 'Success!',
                              //   body: response
                              //   }).done(function(modal) {
                              //       modal.show();
                              //       modal.getRoot().on(ModalEvents.hidden, function() {
                              //       // modal.getRoot().animate({"right":"-85%"}, 500);
                              //                setTimeout(function(){
                              //               modal.destroy();
                              //            }, 4000);
                              //       }.bind(this));
                              //       $(".close").click(function(){
                              //           modal.hide();
                              //           modal.destroy();
                              //       });
                              //   document.location.reload();
                              //   /*$("#affiliateprograms_icon"+args.pid+"").show();
                              //   $("#programpublish_icon"+args.pid+"").hide();*/
                              //   // Do what you want with your new modal.
                              // });
                            }
                            });
                            /*var params = {};
                            params.id = args.id;
                            params.contextid = args.contextid;
                            var promise = Ajax.call([{
                                methodname: 'local_faculties_delete_faculty',
                                args: params
                            }]);
                            promise[0].done(function(resp) {
                                window.location.href = window.location.href;
                            }).fail(function(ex) {
                                // do something with the exception
                                 console.log(ex);
                            });*/
                        }.bind(this));
                    modal.show();
                    }.bind(this));
                    }else{
                    ModalFactory.create({
                        // title: args.title,

                        type: ModalFactory.types.CANCEL,
                        title: 'Alert!',
                        body: returndata.message
                      }).done(function(modal) {
                        // Do what you want with your new modal.

                            modal.show();
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            // modal.getRoot().animate({"right":"-85%"}, 500);
                                    // setTimeout(function(){
                                    modal.destroy();
                                // }, 1000);
                            }.bind(this));
                            $(".close").click(function(){
                                modal.hide();
                                modal.destroy();
                            });
                         //return modal;
                      });
                    }
                }
            });
        },

    };
});
