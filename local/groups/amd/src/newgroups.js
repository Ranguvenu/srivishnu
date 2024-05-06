define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    var Newgroups = function(args) {
        this.contextid = args.contextid;
        this.id = args.id;
        var self = this;
        self.init(args);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    Newgroups.prototype.modal = null;
    /**
     * @var {int} contextid
     * @private
     */
    Newgroups.prototype.contextid = -1;

    Newgroups.prototype.init = function(args) {
        //var triggers = $(selector);
        var self = this;
           
            var editid = $(this).data('value');
            if (editid) {
                self.schoolid = editid;
            }
            if(self.id){
                var head =  Str.get_string('editgroups', 'local_groups');
            }
            else{
               var head = Str.get_string('addNewgroups', 'local_groups');
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
                
                // Keep a reference to the modal.
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
    Newgroups.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }

        // alert(formdata);
        // Get the content of the modal.
        console.log(this);
        var params = {id:this.id, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_groups', 'new_groupsform', this.contextid, params);
    };
      Newgroups.prototype.getFooter = function() {
        
        if(this.id){
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
    Newgroups.prototype.handleFormSubmissionResponse = function() {
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
    Newgroups.prototype.handleFormSubmissionFailure = function(data) {
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
    Newgroups.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // alert(this.contextid);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_groups_submit_groupsform_data',
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
    Newgroups.prototype.submitForm = function(e) {
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
                    return new Newgroups(args);
                },
                load: function(){
             /*   $(document).on('change', '#id_schoolid', function() {
              var schoolvalue = $(this).find("option:selected").val();
               if (schoolvalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/groupss/ajax.php?action=departmentlist&schoolid="+schoolvalue,
                    success: function(data){
                        var template = '<option value=0>Select Departments</option>';
                        $.each( data.data, function( index, value) {
                             template +='<option value = ' + value.id + ' >' +value.fullname + '</option>';
                        });
                      $("#id_departmentid").html(template);
                  }
                    });
                } 
            });*/
                },
            groupsStatus: function(args) {
                ModalFactory.create({
                    title: args.actionstatus,
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: args.actionstatusmsg
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText('Confirm');
                    modal.getRoot().on(ModalEvents.yes, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        var promise = Ajax.call([{
                            methodname: 'local_groups_suspend_groups',
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
                key: 'confirm'
            },
            {
                key: 'deleteconfirm',
                component: 'local_groups',
                param :args
            },
            {
                key: 'deleteallconfirm',
                component: 'local_groups'
            },
            {
                key: 'delete'
            }]).then(function(s) {
                var type = ModalFactory.types.CONFIRM;
                var body = s[1];
                if(args['count'] > 0){
                    var groupsfaculty = args['count']; 
                    type = ModalFactory.types.CANCEL;
                    body = Str.get_string('groupsfacultyvalidation', 'local_groups', groupsfaculty);
                    // s[0] = Str.get_string('cannotdeletegroups', 'local_groupss');
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
                    modal.getRoot().on(ModalEvents.yes, function(e) {
                        e.preventDefault();
                        args.confirm = true;
                        var params = {};
                        params.id = args.id;
                        params.contextid = args.contextid;
                    
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
                    }
                    modal.show();
                }.bind(this));
            }.bind(this));
        },    
    };
});