/**
 * Add a create new group modal to the page.
 *
 * @module     local_courses/newcourse
 * @class      NewCourse
 * @package    local_courses
 * @copyright  2017 Shivani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
        function(dataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var NewDepartment = function(selector, contextid, categoryid, underuniversity) {

        this.contextid = contextid;
        this.categoryid = categoryid;
        this.underuniversity = underuniversity;

        var self = this;
        self.init(selector);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    NewDepartment.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    NewDepartment.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewDepartment.prototype.init = function(selector) {
        //var triggers = $(selector);
        var self = this;
        // Fetch the title string.
        // $(selector).click(function(){

            // var editid = $(this).data("value");
            // //alert(editid);
            // if(typeof editid != 'undefined'){
            //         editid=0;
            // }
            //  self.categoryid = editid;
              //alert(self.courseid);
            if (self.categoryid) {
                var head =  Str.get_string('editdepartment', 'local_departments');
            }else{
               var head =  Str.get_string('adddepartment', 'local_departments');
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

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewDepartment.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        params = {};
        params.jsonformdata = JSON.stringify(formdata);
        params.categoryid = this.categoryid;
        params.underuniversity = this.underuniversity;
        // params.college = this.college;
        return Fragment.loadFragment('local_courses', 'department_form', this.contextid, params);
    };
     NewDepartment.prototype.getFooter = function() {
        console.log(this);
        if(this.categoryid){
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
    NewDepartment.prototype.handleFormSubmissionResponse = function() {
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
    NewDepartment.prototype.handleFormSubmissionFailure = function(data) {
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
    NewDepartment.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        //console.log(this.contextid);
        //console.log(JSON.stringify(formData));
        // Now we can continue...
        // alert("here");
        console.log(JSON.stringify(formData));
        Ajax.call([{
            methodname: 'local_courses_submit_create_department_form',
            //args: {evalid:this.evalid, contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
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
    NewDepartment.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_course/newcourse */ {
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

            return new NewDepartment(args.selector, args.contextid,args.categoryid,args.underuniversity);
        },
        catgoriesTableDatatable: function(){
            // console.log(args);
            $("#category_tbl").dataTable({
                "searching": true,
                "bLengthChange": false,
                "lengthChange": false,
                "lengthMenu": [5, 10, 25, 50, -1],
                "aaSorting": [],
                'language': {
                    "emptyTable": 'No Records Found',
                        "paginate": {
                                    'previous': '<',
                                    'next': '>'
                                }
                 }
            });
        },
        load: function() {

        },deleteConfirm: function(args) {
            console.log(args);
            return Str.get_strings([{
                key: 'confirm'
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
        messageConfirm: function(args) {
            return Str.get_strings([{
                key: args.title,
                component: args.component
            },
            {
                key: args.message,
                component: args.component
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: s[1]
                }).done(function(modal) {
                    this.modal = modal;
                    modal.setSaveButtonText('Ok');                    
                    modal.show();
                    $('.btn-primary').css('display','none');
                }.bind(this));
            }.bind(this));
        },
    };
});
