/**
 * Add a create new group modal to the page.
 *
 * @module     core_group/AjaxForms
 * @class      AjaxForms
 * @package    core_group
 * @copyright  2017 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/fragment',
        'core/ajax',
        'core/yui',
        'core/templates',
        'local_program/select2',
        'local_program/program'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y, Templates, select2, curriculum) {

    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    curriculumlastchildpopup = function(args) {
            $.ajax({
                type: "POST",
                url:   M.cfg.wwwroot + '/local/program/ajax.php',
                data: { curriculumid: args.id, action:'curriculumlastchildpopup',
                    sesskey: M.cfg.sesskey
                },
                success: function(returndata) {
                    //Var returned_data is ONLY available inside this fn!
                    ModalFactory.create({
                        title: Str.get_string('curriculum_info', 'local_program'),
                        body: returndata
                      }).done(function(modal) {
                        // Do what you want with your new modal.
                        modal.show();
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
                                window.location.href =  window.location.href;
                            });
                         //return modal;
                      });
                }
            });
    };
    var AjaxForms = function(args) {
        this.contextid = args.contextid;
        this.args = args;
        this.init(args);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    AjaxForms.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    AjaxForms.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    AjaxForms.prototype.init = function(args) {
        // Fetch the title string.
        var self = this;
            switch (args.callback) {
                case 'program_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'createcurriculum';
                        break;
                        default:
                            header_label = 'updatecurriculum';
                        break;
                    }
                break;
                case 'session_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'addsession';
                        break;
                        default:
                            header_label = 'updatesession';
                        break;
                    }
                break;
                /*case 'curriculum_managefaculty_form':
                    header_label = 'addfaculty';
                break;*/
                case 'curriculum_manageclassroom_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'addclassroom';
                        break;
                        default:
                            header_label = 'updateclassroom';
                        break;
                    }
                break;
                case 'course_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'addcourses';
                        break;
                        default:
                            header_label = 'updatecourses';
                        break;
                    }
                break;
                case 'program_manageprogram_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'addprogram';
                        break;
                        default:
                            header_label = 'updateprogram';
                        break;
                    }
                break;
                case 'curriculum_completion_form':
                    header_label = 'curriculum_completion_settings';
                break;
                case 'curriculum_managesemester_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'addsemester';
                        break;
                        default:
                            header_label = 'updatesemester';
                        break;
                    }
                    //header_label = 'addsemester';
                break;
                case 'curriculum_manageyear_form':
                  switch (args.id) {
                        case 0:
                            header_label = 'addyear';
                        break;
                        default:
                            header_label = 'updateyear';
                        break;
                    }
                break;
                case 'course_form':
                    header_label = 'addcourse';
                break;
                case 'curriculum_managefaculty_form':
                    header_label = 'addfaculty';
                break;
                case 'curriculum_managestudent_form':
                    header_label = 'addstudent';
                break;
                case 'curriculum_setyearcost_form':
                    header_label = 'setcost';
                break;
                case 'classroom_completion_form':
                    switch (args.id) {
                        case 0:
                            header_label = 'classroom_completion_settings';
                        break;
                        default:
                            header_label = 'updateclassroom_completion_settings';
                        break;
                    }
                break;
            }
        return Str.get_string(header_label, 'local_program').then(function(title) {
            // Create the modal.
            return ModalFactory.create({
                type: ModalFactory.types.DEFAULT,
                title: title,
                body: this.getBody(),
                footer: this.getFooter(),
            });
        }.bind(this)).then(function(modal) {
            // Keep a reference to the modal.
            this.modal = modal;

            // Forms are big, we want a big modal.
            this.modal.setLarge();

            this.modal.getRoot().addClass('openLMStransition local_curriculum');

            // We want to reset the form every time it is opened.
            this.modal.getRoot().on(ModalEvents.hidden, function() {
                // this.modal.getRoot().animate({"right":"-85%"}, 500);
                // setTimeout(function(){
                    modal.destroy();
                // }, 1000);
            }.bind(this));
            this.modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));
            this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                window.location.href =  window.location.href;
            });
            this.modal.getFooter().find('[data-action="skip"]').on('click', function() {
                self.args.form_status = self.args.form_status + 1;
                 // OL-1042 Add Target Audience to curriculums//
                 if (args.callback == 'program_form') {
                // OL-1042 Add Target Audience to curriculums//
                    // curriculumlastchildpopup(args);
                 }
                var data = self.getBody();
                data.then(function(html, js) {
                    if (html === false) {
                        self.handleFormSubmissionResponse(args);
                        $('#viewcurriculums').dataTable({
                            'language': {
                                'emptyTable': 'No Programs available in table',
                                'paginate': {
                                'previous': '<',
                                'next': '>'
                            },
                        },
                        'bInfo': false,
                        }).destroy();
                        curriculum.Datatable();
                    }
                });
                modal.setBody(data);
            });

            this.modal.getRoot().on('submit', 'form', function(form) {
                self.submitFormAjax(form, self.args);
            });
            this.modal.show();
            this.modal.getRoot().animate({"right":"0%"}, 500);
            $(".close").click(function(){
                window.location.href =  window.location.href;
            });
            return this.modal;
        }.bind(this));
    };

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    AjaxForms.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        this.args.jsonformdata = JSON.stringify(formdata);
        return Fragment.loadFragment(this.args.component, this.args.callback, this.contextid, this.args);
    };
    /**
     * @method getFooter
     * @private
     * @return {Promise}
     */
    AjaxForms.prototype.getFooter = function() {
       //Added by Yamini
        if(this.args.callback == "curriculum_managefaculty_form" || this.args.callback == "curriculum_managestudent_form"){
          $footer = '<button type="button" class="btn btn-primary" data-action="save">Assign</button>&nbsp;';
        }
        else{

        if(this.args.id){
             $footer = '<button type="button" class="btn btn-primary" data-action="save">Update</button>&nbsp;';
        }
        else{
        $footer = '<button type="button" class="btn btn-primary" data-action="save">Create</button>&nbsp;';
        }
        if (this.form_status == 0) {
            $style = 'style="display:none;"';
            $footer += '<button type="button" class="btn btn-secondary" data-action="skip" ' + $style + '>Skip</button>&nbsp;';
        }
       }
        $footer += '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    AjaxForms.prototype.handleFormSubmissionResponse = function(args) {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        if (args.form_status < 0 || args.form_status === false) {
            // window.location.reload(); // Commented by Harish to stop page reloading after creating semester//
            // Changes by Harish to show the same year once after creating semester starts here //
            if(args.pluginname == "program_program" || args.pluginname == "program_addyear" || args.pluginname == "program" || args.pluginname == "addfaculty" ||args.pluginname == "addstudent" || args.pluginname == "session" || args.pluginname == "classroom_completion_settings"){
                window.location.reload();
            }else{
                $.ajax({
                    method: 'POST',
                    url: M.cfg.wwwroot + '/local/program/ajax.php',
                    data: {
                        action: 'curriculumyearsemesters',
                        curriculumid: args.curriculumid,
                        yearid: args.yearid,
                    },
                    success:function(resp){
                        $('.yearstabscontent_container').html(resp);
                    }
                });
            }
            // Changes by Harish to show the same year once after creating semester ends here //
        }
        // curriculumlastchildpopup(args);
    };

    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    AjaxForms.prototype.handleFormSubmissionFailure = function(data) {
        // Oh noes! Epic fail :(
        // Ah wait - this is normal. We need to re-display the form with errors!
        this.modal.setBody(this.getBody(data));
    };

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    AjaxForms.prototype.submitFormAjax = function(e, args) {
        // We don't want to do a real form submission.
        e.preventDefault();
        var self = this;
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        var methodname = args.plugintype + '_' + args.pluginname + '_submit_instance';
        // Now we can continue...
        var params = {};
        params.contextid = this.contextid;
        params.jsonformdata = JSON.stringify(formData);
        params.form_status = args.form_status;

        var promise = Ajax.call([{
            methodname: methodname,
            args: params
        }]);
        promise[0].done(function(resp){
            self.args.form_status = resp.form_status;
            if (resp.form_status >= 0 && resp.form_status !== false) {
                self.args.form_status = resp.form_status;
                self.args.id = resp.id;
                self.handleFormSubmissionFailure();
            } else {
                self.args.id = resp.id;
                self.handleFormSubmissionResponse(self.args);
            }
            if (args.form_status > 0) {
                $('[data-action="skip"]').css('display', 'inline-block');
            }
        }).fail(function(ex){
            self.handleFormSubmissionFailure(formData);
        });
    };

    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    AjaxForms.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };



    return ajaxforms = /** @alias module:core_group/AjaxForms */ {
        // Public variables and functions.
        /**
         * Attach event listeners to initialise this module.
         *
         * @method init
         * @param {string} selector The CSS selector used to find nodes that will trigger this module.
         * @param {int} contextid The contextid for the course.
         * @return {Promise}
         */
          course_switch: function(args) {
               // $('#switch_course_'+args.courseid).click(function() {

                // var checked = $('#switch_course_'+args.courseid).is(':checked');
                if($('#switch_course_'+args.courseid).is(':checked')){
                   // var checkbox_value = '';
                   var switch_type = 1;
                   checkbox_value = $('#switch_course_'+args.courseid).val();

                }else{
                    // var checkbox_value = '';
                    var switch_type = 0;
                    checkbox_value = $('#switch_course_'+args.courseid).val();
                }
                $.ajax({
                type: 'POST',
                url: M.cfg.wwwroot + '/local/program/ajax.php?course='+checkbox_value+'&switch_type='+switch_type,
               // data: { checked : checked },
                data: {action:''},
                success: function(data) {
                    if(switch_type == 1){
                        $('#notifycompletion'+checkbox_value).show();
                    }else{
                        $('#notifycompletion'+checkbox_value).hide();
                    }
                },
                error: function() {
                },
                complete: function() {

                }
                });
           // });
        },
        init: function(args) {
            return new AjaxForms(args);
        },
        load: function () {
            $(document).on('change', '#id_costcenter', function() {
                var costcentervalue = $(this).find("option:selected").val();
                if (costcentervalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=roomlocation&costcenter="+costcentervalue,
                        success: function(data){
                            var template = '<option value = 0>--Select Location--</option>';

                              $.each(data.data, function( index, value) {
                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                              });
                            $("#id_location").html(template);
                        }
                    });
                }
            });

            $(document).on('change', '#id_location', function() {
                var location = $(this).find("option:selected").val();
                if (location !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=findroom&location="+location,
                        success: function(data){
                            var template = '<option value = 0>--Select Room--</option>';

                              $.each( data.data, function( index, value) {
                                   template +=  '<option value = ' + value.id + ' >' +value.name + '</option>';
                              });
                              // alert(template);
                            $("#id_room").html(template);
                        }
                    });
                }
            });
            $(document).on('change', '#id_costcenter', function() {
                var costcentervalue = $(this).find("option:selected").val();
                if (costcentervalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=faculties&costcenter="+costcentervalue,
                        success: function(data){
                          //  console.log(data);
                            var template = '<option value = 0>--Select Faculty--</option>';

                              $.each(data.data, function( index, value) {
                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                              });
                            $("#id_faculty").html(template);
                        }
                    });
                }
            });

            $(document).on('change', '#id_costcenter', function() {
                var costcentervalue = $(this).find("option:selected").val();
                if (costcentervalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/users/ajax.php?action=departmentlist&costcenter="+costcentervalue,
                        success: function(data){
          var template = '<option value= >--Select College--</option>';
          $.each(data.colleges, function( index, value) {
             template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
          });
          $("#id_open_collegeid").html(template);
          var udept = '<option value=0>--Select Department--</option>';
          $.each(data.departments, function( index, value) {
             udept +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
          });
          // console.log(udept);
          $("#id_departmentid").html(udept);
//                            var template = '<option value = 0>Select Faculty</option>';
//                              $.each(data.faculties, function( index, value) {
//                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
//                              });
//                            $("#id_facultyid").html(template);
//                            var nulldept = '<option value = 0>Select Department</option>';
//                            $("#id_departmentid").html(nulldept);
                            var nullcurriculum = '<option value = 0>Select Curriculum</option>';
                            $("#id_curriculumid").html(nullcurriculum);
                        }
                    });
                }
            });
            $(document).on('change', '#id_open_collegeid', function() {
                var department =  $(this).find("option:selected").val();
                var costcentervalue = $('#id_costcenter').val();
                if (department !== null && department != 0) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=deptcurriculums&costcenter="+costcentervalue+"&department="+department,
                        success: function(data){

                            var template2 = '<option value = 0>Select Curriculum</option>';
                              $.each(data.curriculums, function( index, value) {
                                   template2 +=  '<option value = ' + value.id + ' >' +value.name + '</option>';
                              });
                            $("#id_curriculumid").html(template2);
                        }

                    });
                }else{
                    var nulltemplate = '<option value = 0>Select Curriculum</option>';
                    $("#id_curriculumid").html(nulltemplate);
                }
            });

            $(document).on('change', '#id_departmentid', function() {
                var department =  $(this).find("option:selected").val();
                var costcentervalue = $('#id_costcenter').val();
                if (department !== null && department != 0) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=deptcurriculums&costcenter="+costcentervalue+"&department="+department,
                        success: function(data){

                            var template2 = '<option value = 0>Select Curriculum</option>';
                              $.each(data.curriculums, function( index, value) {
                                   template2 +=  '<option value = ' + value.id + ' >' +value.name + '</option>';
                              });
                            $("#id_curriculumid").html(template2);
                        }

                    });
                }else{
                    var nulltemplate = '<option value = 0>Select Curriculum</option>';
                    $("#id_curriculumid").html(nulltemplate);
                }
            });
           /*$(document).on('change', '#id_room', function() {
                var roomvalue = $(this).find("option:selected").val();
                if (roomvalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=displaymaxvalue&room="+roomvalue,
                        success: function(data){
                             $.each(data.data, function( index, value) {
                                  $("#id_maxcapacity").val(value.capacity);
                              });                           
                        }
                    });
                }
            });*///Commenting room capacity
           $(document).on('click', '#id_institute_type_1', function() {
                var institute_type = $("input[name='institute_type']:checked").val();
                var programid= $('input[name=programid]').val();
                if (institute_type !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=displaylocations&locationvalue="+institute_type+"&programid="+programid,
                        success: function(data){
                            var template = '<option value>--Select Location--</option>';
                            var template1 = '<option value>--Select Room--</option>';
                            $.each(data.data, function( index, value) {

                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                              });
                            $("#id_instituteid").html(template);                   
                            $("#id_room").html(template1);                   
                        }
                    });
                }
            });
            $(document).on('click', '#id_institute_type_2', function() {
                var institute_type = $("input[name='institute_type']:checked").val();
                var programid= $('input[name=programid]').val();
                if (institute_type !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=displaylocations&locationvalue="+institute_type+"&programid="+programid,
                        success: function(data){
                             var template = '<option value>--Select Location--</option>';
                             var template1 = '<option value>--Select Room--</option>';
                              $.each(data.data, function( index, value) {

                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                              });
                            $("#id_instituteid").html(template);                   
                            $("#id_room").html(template1);                   
                        }
                    });
                }
            });
            $(document).on('click', '#id_instituteid', function() {
                var rooms = $(this).find("option:selected").val();
                //alert(rooms);
                if (rooms !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/program/ajax.php?action=findroom&location="+rooms,
                        success: function(data){
                             var template = '<option value>--Select Room--</option>';                         
                              $.each(data.data, function( index, value) {

                                   template +=  '<option value = ' + value.id + ' >' +value.name + '</option>';
                              });
                            $("#id_room").html(template);                   
                        }
                    });
                }
            });

        },
        myDescFunction: function() {
                var dots = document.getElementById('descriptiondotsBtn');
                var moreText = document.getElementById('resttextDisplay');
                var btnText = document.getElementById('readmoremyBtn');
                if (dots.style.display === "none") {
                    dots.style.display = "inline";
                    btnText.innerHTML = "Read more";
                    moreText.style.display = "none";
                  } else {
                    dots.style.display = "none";
                    btnText.innerHTML = "Read less";
                    moreText.style.display = "inline";
                }
            },
    };
});
