/**
 * Add a create new group modal to the page.
 *
 * @module     local_users/newuser
 * @class      NewUser
 * @package    local_users
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var NewUser = function(args) {

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
    NewUser.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    NewUser.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewUser.prototype.init = function(args) {
        //var triggers = $(selector);
        var self = this;
        // Fetch the title string.
            if (self.id && args.employee == 2) {
                var head =  Str.get_string('edituser', 'local_users');
            }else if(args.employee == 2){
               var head =  Str.get_string('adnewuser', 'local_users');
            }

            if (self.id && args.employee == 1) {
                var head =  Str.get_string('editemployee', 'local_users');
            }else if(args.employee == 1){
               var head =  Str.get_string('adnewemployee', 'local_users');
            }

            if (self.id && args.employee == 3) {
                var head =  Str.get_string('edithead', 'local_users');
            }else if(args.employee == 3){
               var head =  Str.get_string('addnewhead', 'local_users');
            }
            return head.then(function(title) {
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
                // self.modal.show();
                // Forms are big, we want a big modal.
                this.modal.setLarge(); 
                
                this.modal.getRoot().addClass('openLMStransition local_users');

                // this.modal.getRoot().on(ModalEvents.hidden, function() {
                //     this.modal.setBody('');
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    this.modal.getRoot().animate({"right":"-85%"}, 500);
                    setTimeout(function(){
                        modal.destroy();
                    }, 5000);
                }.bind(this));

                this.modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));
                // We also catch the form submit event and use it to submit the form with ajax.

                // this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                //     modal.setBody('');
                //     modal.hide();
                this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                    modal.hide();
                    setTimeout(function(){
                        modal.destroy();
                    }, 5000);
                    /*self.args.form_status = self.args.form_status + 1;
                    var data = self.getBody();
                    data.then(function(html, js) {
                        if(html === false) {
                            window.location.reload();
                        }
                    });
                    modal.setBody(data);*/
                });

                this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                    self.args.form_status = self.args.form_status + 1;
                    var data = self.getBody();
                    data.then(function(html, js) {
                        if(html === false) {
                            window.location.reload();
                        }
                    });
                    modal.setBody(data);
                });

                // Changes by Harish to reflect the created/updated data when clicked on close button in form2 starts here //
                this.modal.getRoot().find('[data-action="hide"]').on('click', function() {
                    self.args.form_status = self.args.form_status + 1;
                    var data = self.getBody();
                    data.then(function(html, js) {
                        if(html === false) {
                            window.location.reload();
                        }
                    });
                    modal.setBody(data);
                });

                this.modal.getFooter().find('[data-action="skip"]').on('click', function() {
                    self.args.form_status = self.args.form_status + 1;
                    var data = self.getBody();
                    data.then(function(html, js) {
                        if(html === false) {
                            window.location.reload();
                        }
                    });
                    modal.setBody(data);
                });

                this.modal.getRoot().on('submit', 'form', function(form) {
                    self.submitFormAjax(form, self.args);
                });
                this.modal.show();
                this.modal.getRoot().animate({"right":"0%"}, 500);

                return this.modal;
            }.bind(this));       
        
        
        // });
        
    };
 
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewUser.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // console.log(this);
        // alert(formdata);
        // Get the content of the modal.
        // this.args.userid = this.userid
        this.args.jsonformdata = JSON.stringify(formdata);
        return Fragment.loadFragment('local_users', 'new_create_user', this.contextid, this.args);
    };
    /**
     * @method getFooter
     * @private
     * @return {Promise}
     */
    NewUser.prototype.getFooter = function() {
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
        $footer += '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };
 
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    NewUser.prototype.handleFormSubmissionResponse = function() {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        // document.location.reload();
        // This will be the context for our template. So {{name}} in the template will resolve to "Tweety bird".
        var context = { id: args.id};
        // // This will call the function to load and render our template.
        // templates.render('local_classroom/classroomview', context);

        // // It returns a promise that needs to be resoved.
        //     .then(function(html, js) {
                var modalPromise = ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    body: Templates.render('local_classroom/classroomview', context),
                });
                $.when(modalPromise).then(function(modal) {
                    // modal.setTitle('Hi');
                    // // modal.setBody('Hi');
                    // modal.show();
                    // return modal;
                }).fail(Notification.exception);


            //     // Here eventually I have my compiled template, and any javascript that it generated.
            //     // The templates object has append, prepend and replace functions.
            //     templates.appendNodeContents('.block_looneytunes .content', source, javascript);
            // }).fail(function(ex) {
            //     // Deal with this exception (I recommend core/notify exception function for this).
            // });
    };
 
    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    NewUser.prototype.handleFormSubmissionFailure = function(data) {
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
    NewUser.prototype.submitFormAjax = function(e ,args) {
        // We don't want to do a real form submission.
        e.preventDefault();
        var self = this;
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // var methodname = args.plugintype + '_' + args.pluginname + '_submit_create_user_form';
        var methodname = 'local_users_submit_create_user_form';
        var params = {};
        params.contextid = this.contextid;
        params.jsonformdata = JSON.stringify(formData);
        params.form_status = args.form_status;
        params.employee = args.employee;
        var promise = Ajax.call([{
            methodname: methodname,
            args: params
        }]);

         promise[0].done(function(resp){
            // alert(resp.form_status);
            if(resp.form_status !== -1 && resp.form_status !== false) {
                self.args.form_status = resp.form_status;
                self.args.id = resp.id;
                self.handleFormSubmissionFailure();
            } else {
                // self.handleFormSubmissionResponse(self.args);
                // alert('here');
                self.modal.hide();
                window.location.reload();
            }
            if(args.form_status > 0) {
                $('[data-action="skip"]').css('display', 'inline-block');
            }
        }).fail(function(ex){
            self.handleFormSubmissionFailure(formData);
        })
        // alert(this.contextid);
        // Now we can continue...
        // Ajax.call([{
        //     methodname: 'local_users_submit_create_user_form',
        //     args: {contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
        //     done: this.handleFormSubmissionResponse.bind(this, formData),
        //     fail: this.handleFormSubmissionFailure.bind(this, formData)
        // }]);
    };
 
    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    NewUser.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };
 
    return /** @alias module:local_users/newuser */ {
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
            return new NewUser(args);
        },
        load: function(){

        },
        deleteConfirm: function(args) {
            console.log(args);
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_users',
            },
            {
                key: 'deleteconfirm',
                component: 'local_users',
                param :args
            },
            {
                key: 'deleteallconfirm',
                component: 'local_users'
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
                        params.id = args.id;
                        params.contextid = args.contextid;
                    
                        var promise = Ajax.call([{
                            methodname: 'local_users_'+args.action,
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
        userSuspend: function(args) {
            console.log(args);
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_users'
            },
            {
                key: 'suspendconfirm',
                component: 'local_users',
                param :args
            },
            {
                key: 'suspendallconfirm',
                component: 'local_users'
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
                            methodname: 'local_users_suspend_user',
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