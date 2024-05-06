/**
 * Add a create new group modal to the page.
 *
 * @module     local_advisor/advisor
 * @class      NewMooccourse
 * @package    local_advisor
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_mooccourses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function(dataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var NewMooccourse = function(args) {
        this.contextid = args.context;
        this.id = args.id;
        var self = this;
        this.args = args;
        self.init(args);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    NewMooccourse.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    NewMooccourse.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewMooccourse.prototype.init = function(args) {
        console.log(args);
        var self = this;
            
            if (args.course == 2) {
                var head =  Str.get_string('edituser', 'local_mooccourses');
            }

            if (args.course == 1) {
                var head =  Str.get_string('selectcategory', 'local_mooccourses');
            }

            if (args.course == 3) {
                var head =  Str.get_string('edithead', 'local_mooccourses');
            }
            if(args.act == 1){
                 var head =  Str.get_string('create_newstudent', 'local_mooccourses');
            }
            return head.then(function(title) {
             
                return ModalFactory.create({
                 type: ModalFactory.types.DEFAULT,
                title: title,             
                body: this.getBody(),
                footer: this.getFooter()
                });
            }.bind(this)).then(function(modal) {
                
                      self.modal = modal;
               
                self.modal.getRoot().addClass('openLMStransition local_school');
                // Forms are big, we want a big modal.
                self.modal.setLarge();
     
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
                
                var data = self.getBody();
              
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
        
        
        // });
        
    };
 
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewMooccourse.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
       this.args['jsonformdata'] = JSON.stringify(formdata);
       if(this.args.act){

        return Fragment.loadFragment('local_mooccourses', 'enroluser', this.args.context, this.args);

       }else{
        if(this.args.courseid){
        return Fragment.loadFragment('local_mooccourses', 'selectcategory', this.args.contextid, this.args);
       }else{
        return Fragment.loadFragment('local_mooccourses', 'selectcategory', this.contextid, this.args);
       }
      }
    };
      NewMooccourse.prototype.getFooter = function() {
      
        if(this.args.course == 3){
             $footer = '<button type="button" class="btn btn-primary" data-action="save">Update</button>&nbsp;';
        }
        else if(this.args.act == 1){

            $footer = '<button type="button" class="btn btn-primary" data-action="save">Assign</button>&nbsp;';
        }
        else{
        $footer = '<button type="button" class="btn btn-primary" data-action="save">Create</button>&nbsp;';
        }
        if (this.form_status == 0) {
            $style = 'style="display:none;"';
            $footer += '<button type="button" class="btn btn-secondary" data-action="skip" ' + $style + '>Skip</button>&nbsp;';
        }
        $footer += '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };
 
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    NewMooccourse.prototype.handleFormSubmissionResponse = function() {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        document.location.reload();
    };
 
    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    NewMooccourse.prototype.handleFormSubmissionFailure = function(data) {
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
    NewMooccourse.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
       
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();

        // Now we can continue...
       //console.log(JSON.stringify(formData));
    
       if(this.args['cid']){
         Ajax.call([{
            methodname: 'local_mooccourses_submit_create_mooccourse_form',
            args: {contextid: this.contextid,courseid:this.args['courseid'],parentcourseid: this.args['cid'] ,jsonformdata: JSON.stringify(formData)},
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
       }
       else if(this.args['act'] == 1){
      
             Ajax.call([{
            methodname: 'local_mooccourses_submit_enrolusers_form',
            args: {contextid: this.contextid,courseid:this.args['courseid'],jsonformdata: JSON.stringify(formData)},
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
       }
       else{
      
         Ajax.call([{
            methodname: 'local_mooccourses_submit_edit_mooccourse_form',
            args: {contextid: this.args['contextid'],courseid:this.args['courseid'] ,forpurchaseindividually:this.args['forpurchaseindividually'],jsonformdata: JSON.stringify(formData)},
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
       }
       
    };
 
    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    NewMooccourse.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };
    return  {
                // Public variables and functions.
                /**
                 * Attach event listeners to initialise this module.
                 *
                 * @method init
                 * @param {string} selector The CSS selector used to find nodes that will trigger this module.
                 * @param {int} contextid The contextid for the course.
                 * @return {Promise}
                 */
                init: function(args) {
                  
                   
                    return new NewMooccourse(args);
                },
                load: function(){

                $(document).on('change', '#id_open_costcenterid', function() {
                var costcentervalue = $(this).find("option:selected").val();
                if (costcentervalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/curriculum/ajax.php?action=departmentlist&costcenter="+costcentervalue,
                        success: function(data){
                            var template = '<option value=>--Select College--</option>';
                            $.each(data.colleges, function( index, value) {
                            template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                            });
                            $("#id_open_collegeid").html(template);

                            var udept = '<option value=0>--Select Department--</option>';
                            $.each(data.departments, function( index, value) {
                            udept +=  '<option value = ' + value.id + ' >' + value.fullname + '</option>';
                            });
                            $("#id_open_departmentid").html(udept);
//                            var template = '<option value = 0>Select Departments</option>';
//
//                              $.each( data.data, function( index, value) {
//                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
//                              });
//                              // alert(template);
//                            $("#id_category").html(template);
                        }
                    });
                }
            });


        
                },
                mooccoursesList: function(args) {
                var self = this;
                $.ajax({
                type: "POST",
               //  url:   M.cfg.wwwroot + '/local/mooccourses/ajax.php',
          //      url:   M.cfg.wwwroot + '/local/mooccourses/ajax.php',
                data: {action:'mooccoursesList',
                    sesskey: M.cfg.sesskey
                },
                success: function(returndata) {
                    //Var returned_data is ONLY available inside this fn!
                    ModalFactory.create({
                        title: Str.get_string('selecttemplate', 'local_mooccourses'),
                        body: returndata
                      }).done(function(modal) {
                        // Do what you want with your new modal.
                        // modal.show();
                       // curriculum.chlidprogramsDatatable(args);
                         modal.setLarge();
                         modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().on(ModalEvents.hidden, function() {
                            // modal.getRoot().animate({"right":"-85%"}, 500);
                                    // setTimeout(function(){
                                    modal.destroy();
                                // }, 1000);
                            }.bind(this));
                            /*$(".close").click(function(){
                                modal.hide();
                                modal.destroy();
                            });*/
                            // We want to reset the form every time it is opened.
                            modal.getRoot().on(ModalEvents.cancel, function() {
                                // self.modal.setBody('');
                                modal.hide();
                                modal.destroy();
                            }.bind(this));
                            modal.show();
                            self.dataTableshow();
                         //return modal;
                      });
                }
            });
        },
         
    };
});
