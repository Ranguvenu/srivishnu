/**
 * Add a create new group modal to the page.
 *
 * @module     local_courses/courseAjaxform
 * @class      courseAjaxform
 * @package    local_courses
 * @copyright  2018 Sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui', 'core/templates'],
        function(dataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y, Templates) {

    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var courseAjaxform = function(args) {
        this.contextid = args.contextid || 1;
        this.args = args;
        this.init(args);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    courseAjaxform.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    courseAjaxform.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    courseAjaxform.prototype.init = function(args) {
        // Fetch the title string.
        var self = this;
         if (args.courseid) {
           /* <revathi> ODL-831 starts*/ 
             if(args.forpurchaseindividually == 1 || args.forpurchaseindividually == 2){
            var head =  Str.get_string('editcourse', 'local_mooccourses');
            }else{
                var head =  Str.get_string('editcourse', 'local_courses');
            }
           /* <revathi> ODL-831 ends*/
        }else{
           var head =  Str.get_string('createnewcourse', 'local_courses');
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

            // Forms are big, we want a big modal.
            this.modal.setLarge();

            this.modal.getRoot().addClass('openLMStransition local_courses');

            // We want to reset the form every time it is opened.
            this.modal.getRoot().on(ModalEvents.hidden, function() {
                this.modal.getRoot().animate({"right":"-85%"}, 500);
                setTimeout(function(){
                    modal.destroy();
                }, 1000);
                this.modal.setBody('');
            }.bind(this));

            // // We want to hide the submit buttons every time it is opened.
            // this.modal.getRoot().on(ModalEvents.shown, function() {
            //     this.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
            // }.bind(this));

            // We catch the modal save event, and use it to submit the form inside the modal.
            // Triggering a form submission will give JS validation scripts a chance to check for errors.
            // this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
            this.modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));
            // We also catch the form submit event and use it to submit the form with ajax.

            this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                modal.setBody('');
                modal.hide();
                setTimeout(function(){
                    modal.destroy();
                }, 1000);
                if (args.form_status !== 0 ) {
                    window.location.reload();
                }
            });
            
            this.modal.getRoot().find('[data-action="hide"]').on('click', function() {
                modal.hide();
                setTimeout(function(){
                    modal.destroy();
                }, 1000);
                 //modal.destroy();
                if (args.form_status !== 0 ) {
                    window.location.reload();
                }
                
            });

            this.modal.getRoot().on('submit', 'form', function(form) {
                self.submitFormAjax(form, self.args);
            });
            this.modal.show();
            this.modal.getRoot().animate({"right":"0%"}, 500);
            return this.modal;
        }.bind(this));
    };

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    courseAjaxform.prototype.getBody = function(formdata) {
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
    courseAjaxform.prototype.getFooter = function() {
           //  console.log(this.args);
       if(this.args.courseid){
             $footer = '<button type="button" class="btn btn-primary" data-action="save">Update</button>&nbsp;';
        }
        else{
        $footer = '<button type="button" class="btn btn-primary" data-action="save">Create</button>&nbsp;';
        }
        if (this.args.form_status == 0) {
            $style = 'style="display:none;"';
            $footer += '<button type="button" class="btn btn-secondary" data-action="skip" ' + $style + '>Skip</button>&nbsp;';
        }
        $footer += '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };
     /**
     * @method getFooter
     * @private
     * @return {Promise}
     */
    courseAjaxform.prototype.getcontentFooter = function() {
        $footer = '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    courseAjaxform.prototype.handleFormSubmissionResponse = function(args) {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });

        document.location.reload();
        // This will be the context for our template. So {{name}} in the template will resolve to "Tweety bird".
        // Form double submissions removed in course creation page starts here //
        /*var context = { courseid: args.courseid, configpath: M.cfg.wwwroot, enrolid: args.enrolid, contextid:args.contextid};

        var modalPromise = ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            body: Templates.render('local_courses/courses', context),
            footer: this.getcontentFooter(),
        });
        $.when(modalPromise).then(function(modal) {
            modal.setTitle('Course Overview');

            // Forms are big, we want a big modal.
            modal.setLarge();

            modal.getRoot().addClass('openLMStransition');
            modal.show();
            modal.getRoot().animate({"right":"0%"}, 500);
            // modal.getRoot().on(ModalEvents.hidden, function() {
            //     modal.hide();
            // });
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            }.bind(this));
            modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                modal.getRoot().animate({"right":"-85%"}, 500);
                setTimeout(function(){
                    window.location.reload();
                }, 600);
            });
            modal.getRoot().find('[data-action="hide"]').on('click', function() {
                modal.getRoot().animate({"right":"-85%"}, 500);
                setTimeout(function(){
                    window.location.reload();
                }, 200);
            });
            return modal;
        }).fail(Notification.exception);
        $('#coursesearch').dataTable().destroy();*/
        // Form double submissions removed in course creation page ends here //
        // Classroom.Datatable();
    };

    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    courseAjaxform.prototype.handleFormSubmissionFailure = function(data) {
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
    courseAjaxform.prototype.submitFormAjax = function(e, args) {
        // We don't want to do a real form submission.
        e.preventDefault();
        var self = this;
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        var methodname = args.plugintype + '_' + args.pluginname + '_submit_create_course_form';
        // Now we can continue...
        var params = {};
        params.contextid = this.contextid;
        params.jsonformdata = JSON.stringify(formData);
        // params.form_status = args.form_status;
        // params.id = args.id;

        Ajax.call([{
            methodname: methodname,
            args: params,
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
        // promise[0].done(function(resp){
        //     self.args.courseid = resp.courseid;
        //     self.args.enrolid = resp.enrolid;
        //     if(resp.form_status !== -1 && resp.form_status !== false) {
        //         self.args.form_status = resp.form_status;
        //         self.handleFormSubmissionFailure();
        //     } else {
        //         self.handleFormSubmissionResponse(self.args);
        //     }
        //     // if(args.form_status > 0) {
        //         // $('[data-action="skip"]').css('display', 'inline-block');
        //     // }
        // }).fail(function(){
        //     self.handleFormSubmissionFailure(formData);
        // });

    };

    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    courseAjaxform.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };

    return /** @alias module:core_group/courseAjaxform */ {
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
            return new courseAjaxform(args);
        },
        deleteConfirm: function(args){
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_courses'
            },
            {
                key: 'deleteconfirm',
                component: 'local_courses',
                param : args
            },
            {
                key: 'deleteallconfirm',
                component: 'local_courses'
            },
            {
                key: 'delete'
            }]).then(function(s) {
                var type = ModalFactory.types.SAVE_CANCEL;
                var body = s[1];
                if(args['count'] > 0){
                    var coursescount = args.count; 
                    type = ModalFactory.types.CANCEL;
                    body = Str.get_string('coursevalidationbody', 'local_courses');
                    // s[0] = Str.get_string('coursevalidationtitle', 'local_courses');
                    s[0] = "Alert";
                }
                ModalFactory.create({
                    title: s[0],
                    type: type,
                    body: body
                }).done(function(modal) {
                    this.modal = modal;
                    if(args.count == 0){
                        modal.setSaveButtonText(s[3]);
                        modal.getRoot().on(ModalEvents.save, function(e){
                        e.preventDefault();
                            args.confirm = true;
                        var promise = Ajax.call([{
                            methodname: 'local_courses_' + args.action,
                            args: args
                        }]);
                        promise[0].done(function() {
                            window.location.href = window.location.href;
                        }).fail(function(ex) {
                            // do something with the exception
                             console.log(ex);
                        });
                    }.bind(this));
                    }
                    /*modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                        modal.setBody('');
                        modal.hide();
                    });*/
                    modal.show();
                }.bind(this));
            }.bind(this));
        },
        userSuspend: function(args) {
            console.log(args);
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_courses'
            },
            {
                key: 'suspendconfirm',
                component: 'local_courses',
                param :args
            },
            {
                key: 'suspendallconfirm',
                component: 'local_courses'
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
                            methodname: 'local_courses_suspend_course',
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
          courseavailability: function(args) {
            console.log(args);
            return Str.get_strings([{
                key: 'confirmation',
                component: 'local_courses'
            },
            {
                key: 'availiabilityofcourse',
                component: 'local_courses',
                param :args
            },
            {
                key: 'availiabilityofcourseconfirm',
                component: 'local_courses'
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
                            methodname: 'local_courses_availability_course',
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
        getCatlist: function() {
            costcenter = $("#id_open_costcenterid").val();
            if (costcenter === '') {
                //code
                var template =  '<option value=\'\'>--Select Department--</option>';
                $('#id_open_departmentid').html(template);
                var cattemplate =  '<option value=\'\'>--Select Department--</option>';
                $('#id_category').html(cattemplate);
            } else {
                var promise = Ajax.call([{
                    methodname: 'local_courses_departmentlist',
                    args: {
                        costcenter: costcenter,
                    },
                }]);
                promise[0].done(function(response) {
                    //var template = '';
                    var categorytemp = '';
                    departmentlist = JSON.parse(response.departmentlist);
                    categorylist = JSON.parse(response.categorylist);
                    $.each(categorylist, function(index, value) {
                        categorytemp += '<option value = ' + value.id + ' >' + value.name + '</option>';
                    });
                    // template += '<option value = "" >--Select Department--</option>';
                    //$.each(departmentlist, function(index, value) {
                    //    template += '<option value = ' + value.id + ' >' + value.fullname + '</option>';
                    //});
                    $('#id_category').html(categorytemp);
                    //$('#id_open_departmentid').html(template);
                }).fail(function() {
                    // do something with the exception
                    alert('Error occured while processing request');
                    window.location.reload();
                });
            }
            
        },
        load: function () {}
    };
});
