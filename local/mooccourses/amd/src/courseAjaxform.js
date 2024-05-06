/**
 * Add a create new group modal to the page.
 *
 * @module     local_mooccourses/courseAjaxform
 * @class      courseAjaxform
 * @package    local_mooccourses
 * @copyright  2018 Sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_mooccourses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui', 'core/templates'],
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
            var head =  Str.get_string('editcourse', 'local_mooccourses');
        }else{
           var head =  Str.get_string('createnewcourse', 'local_mooccourses');
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

            this.modal.getRoot().addClass('openLMStransition local_mooccourses');

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
       
       // $footer = '<button type="button" class="btn btn-primary" data-action="save">Create</button>&nbsp;';
         if(this.args.courseid > 0){
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
  
        var params = {};
        params.contextid = this.contextid;
        params.jsonformdata = JSON.stringify(formData);
    
        Ajax.call([{
            methodname: 'local_mooccourses_submit_create_course_form',
            args: params,
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
        moochcourseslist: function(args) {            
            console.log(args);
                var self = this;
                $.ajax({
                type: "POST",
                url:   M.cfg.wwwroot + '/local/mooccourses/ajax.php',
                data: { 
                },
                success: function(returndata) {
                    //Var returned_data is ONLY available inside this fn!
                    ModalFactory.create({
                        // type: ModalFactory.types.SAVE_CANCEL,
                         title: Str.get_string('courses', 'local_mooccourses'),
                         body: returndata,                          
                      }).done(function(modal) {
                        // Do what you want with your new modal.
                        // modal.show();
                       // curriculum.chlidprogramsDatatable(args);

                         modal.setLarge();
                         modal.getRoot().addClass('openLMStransition');
                         modal.getRoot().animate({"right":"0%"}, 500);
                         modal.getRoot().on(ModalEvents.hidden, function() {
                         modal.destroy();
                     }.bind(this));
                        modal.getRoot().on(ModalEvents.cancel, function() {
                            modal.hide();
                            modal.destroy();
                        }.bind(this));
                        modal.show();
                        self.dataTableshow();
                      });
                      

                 }

            });
        },
        dataTableshow: function(){
        $('#selling_courses').dataTable({
            'bPaginate': true,
            'bFilter': true,
             "searching": true,
            'bLengthChange': true,
            'ordering': false,
            'lengthMenu': [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'All']
            ],
            'language': {
                'emptyTable': 'No Mooc Courses available in table',
                'paginate': {
                    'previous': '<',
                    'next': '>'
                }
            },
            'bProcessing': true,
        });
       
    },

        load: function () {}
        
    };
});
