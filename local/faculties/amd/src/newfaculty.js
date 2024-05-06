define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    var Newfaculty = function(args) {
        this.contextid = args.contextid;
        this.facultyid = args.facultyid;
        var self = this;
        self.init(args);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    Newfaculty.prototype.modal = null;
    /**
     * @var {int} contextid
     * @private
     */
    Newfaculty.prototype.contextid = -1;

    Newfaculty.prototype.init = function(args) {
        //var triggers = $(selector);
        var self = this;

            var editid = $(this).data('value');
            // if (editid) {
            //     self.schoolid = editid;
            // }
            if(self.facultyid){
                var head =  Str.get_string('editfaculty', 'local_faculties');
            }
            else{
               var head = Str.get_string('adnewfaculty', 'local_faculties');
            }
            return head.then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    title: title,
                    body: self.getBody(),
                    footer: this.getFooter()
                });
            }.bind(self)).then(function(modal) {
                
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
    Newfaculty.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // alert(formdata);
        // Get the content of the modal.
        var params = {facultyid:this.facultyid, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_faculties', 'new_facultyform', this.contextid, params);
    };
    Newfaculty.prototype.getFooter = function() {
        console.log(this);
        if(this.facultyid){
             $footer = '<button type="button" class="btn btn-primary" data-action="save">Update</button>&nbsp;';
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
    Newfaculty.prototype.handleFormSubmissionResponse = function() {
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
    Newfaculty.prototype.handleFormSubmissionFailure = function(data) {
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
    Newfaculty.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // alert(this.contextid);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_faculties_submit_facultyform_data',
            args: {contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
    };
 
    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    Newfaculty.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_school/newschool */ {
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
                  
                    // alert(args.contextid);
                    return new Newfaculty(args);
                },
                load: function(){
                    /*$(document).on('change', '#id_university', function() {
                        var universityvalue = $(this).find("option:selected").val();
                        if (universityvalue !== null) {
                            $.ajax({
                                method: "GET",
                                dataType: "json",
                                url: M.cfg.wwwroot + "/local/faculties/ajax.php?university="+universityvalue,
                            success: function(data){
                                if(data.data !== null){
                                  var template = '<option value=0>---Select Board---</option>';
                                  $.each( data.data, function( index, value) {
                                       template +='<option value = ' + value.id + ' >' +value.fullname + '</option>';
                                  });
                                }else{
                                  var template = '<option value=0>No boards under university</option>';
                                }
                                $("#id_board").html(template);
                            }
                            });
                        } 
                    });*/// Commented by Harish for hiding boards functionality
                },
            facultyStatus: function(args) {
                ModalFactory.create({
                    title: args.actionstatus,
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: args.actionstatusmsg
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText('Confirm');
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        var promise = Ajax.call([{
                            methodname: 'local_faculties_suspend_faculty',
                            args: args
                        }]);
                        promise[0].done(function(resp) {
                            window.location.href = window.location.href;
                        }).fail(function(ex) {
                            // do something with the exception
                        });
                    }.bind(this));
                    modal.show();
                }.bind(this));
        },
        deleteConfirm: function(args) {
            return Str.get_strings([{
                key: 'confirmation',
                 component: 'local_faculties',
            },
            {
                key: 'deleteconfirm',
                component: 'local_faculties',
                param :args
            },
            {
                key: 'deleteallconfirm',
                component: 'local_faculties'
            },
            {
                key: 'delete'
            }]).then(function(s) {
                var type = ModalFactory.types.SAVE_CANCEL;
                var body = s[1];
                if(args['count'] > 0){
                    var facultyprogram = args['count']; 
                    type = ModalFactory.types.CANCEL;
                    body = Str.get_string('facultyprogramvalidation', 'local_faculties', facultyprogram);
                    // s[0] = Str.get_string('cannotdeletefaculty', 'local_faculties');
                    s[0] = "Alert!";
                }
                ModalFactory.create({
                    title: s[0],
                    type: type,
                    body: body
                }).done(function(modal) {
                    this.modal = modal;
                    if(args.count == 0){
                        modal.setSaveButtonText(s[3]);
                        modal.getRoot().on(ModalEvents.save, function(e) {
                            e.preventDefault();
                            args.confirm = true;
                            var params = {};
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
                            });
                        }.bind(this));
                    }
                    modal.show();
                }.bind(this));
            }.bind(this));
        },   
    };
});