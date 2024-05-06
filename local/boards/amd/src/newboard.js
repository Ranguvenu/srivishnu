define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    var Newboard = function(args) {
        this.contextid = args.contextid;
        this.boardid = args.boardid;
        var self = this;
        self.init(args);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    Newboard.prototype.modal = null;
    /**
     * @var {int} contextid
     * @private
     */
    Newboard.prototype.contextid = -1;

    Newboard.prototype.init = function(args) {
        //var triggers = $(selector);
        var self = this;

            var editid = $(this).data('value');
            if (editid) {
                self.schoolid = editid;
            }
            if(self.boardid){
                var head =  Str.get_string('editboard', 'local_boards');
            }
            else{
               var head = Str.get_string('adnewboard', 'local_boards');
            }
            return head.then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    title: title,
                    body: self.getBody(),
                    footer: this.getFooter(),
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
                 if (args.callback == 'program_form') {
                // OL-1042 Add Target Audience to curriculums//
                    // curriculumlastchildpopup(args);
                 }
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
    };
    Newboard.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // alert(formdata);
        // Get the content of the modal.
        var params = {boardid:this.boardid, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_boards', 'new_boardform', this.contextid, params);
    };
      Newboard.prototype.getFooter = function() {
        console.log(this);
        if(this.boardid){
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
    Newboard.prototype.handleFormSubmissionResponse = function() {
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
    Newboard.prototype.handleFormSubmissionFailure = function(data) {
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
    Newboard.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // alert(this.contextid);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_boards_submit_boardform_data',
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
    Newboard.prototype.submitForm = function(e) {
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
                    return new Newboard(args);
                },
                load: function(){
                $(document).on('change', '#id_schoolid', function() {
              var schoolvalue = $(this).find("option:selected").val();
               if (schoolvalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/boards/ajax.php?action=departmentlist&schoolid="+schoolvalue,
                    success: function(data){
                        var template = '<option value=0>Select Departments</option>';
                        $.each( data.data, function( index, value) {
                             template +='<option value = ' + value.id + ' >' +value.fullname + '</option>';
                        });
                      $("#id_departmentid").html(template);
                  }
                    });
                } 
            });
                },
            boardStatus: function(args) {
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
                            methodname: 'local_boards_suspend_board',
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
    //     deleteConfirm: function(args) {
    //         return Str.get_strings([{
    //             key: 'confirm'
    //         },
    //         {
    //             key: 'deleteconfirm',
    //             component: 'local_boards',
    //             param :args
    //         },
    //         {
    //             key: 'deleteallconfirm',
    //             component: 'local_boards'
    //         },
    //         {
    //             key: 'delete'
    //         }]).then(function(s) {
    //             var type = ModalFactory.types.CONFIRM;
    //             var body = s[1];
    //             var title = Str.get_string('deleteconfirmation', 'local_boards');
    //             if(args['count'] > 0){
    //                 var boardfaculty = args['count'];
    //                 title = 'Alert!';
    //                 type = ModalFactory.types.CANCEL;
    //                 body = Str.get_string('boardfacultyvalidation', 'local_boards', boardfaculty);
    //                 // s[0] = Str.get_string('cannotdeleteboard', 'local_boards');
    //                 s[0] = "Alert!";
    //             }
    //             ModalFactory.create({
    //                 title: title,
    //                 type: type,
    //                 body: body
    //             }).done(function(modal) {
    //                 this.modal = modal;
    //                 if(args.count == 0){
    //                 modal.setSaveButtonText(s[3]);
    //                 modal.getRoot().on(ModalEvents.yes, function(e) {
    //                     e.preventDefault();
    //                     args.confirm = true;
    //                     var params = {};
    //                     params.id = args.id;
    //                     params.contextid = args.contextid;
                    
    //                     var promise = Ajax.call([{
    //                         methodname: 'local_boards_delete_board',
    //                         args: params
    //                     }]);
    //                     promise[0].done(function(resp) {
    //                         window.location.href = window.location.href;
    //                     }).fail(function(ex) {
    //                         // do something with the exception
    //                          console.log(ex);
    //                     });
    //                 }.bind(this));
    //                 }
    //                 modal.show();
    //             }.bind(this));
    //         }.bind(this));
    //     },    
    // };
//added revathi

    deleteConfirm: function(args) {
            return Str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'deleteconfirm',
                component: 'local_boards',
                param :args
            },
            {
                key: 'deleteallconfirm',
                component: 'local_boards'
            },
            {
                key: 'delete'
            }]).then(function(s) {
                var type = ModalFactory.types.CONFIRM;
                var body = s[1];
                var title = Str.get_string('deleteconfirmation', 'local_boards');
                if(args['count'] > 0){
                    var boardfaculty = args['count'];
                    title = 'Alert!';
                    type = ModalFactory.types.CANCEL;
                    body = Str.get_string('boardfacultyvalidation', 'local_boards', boardfaculty);
                    // s[0] = Str.get_string('cannotdeleteboard', 'local_boards');
                    s[0] = "Alert!";
                }
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL,
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
                            methodname: 'local_boards_delete_board',
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
    //end added
});