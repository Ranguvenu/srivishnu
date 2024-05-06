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
    var NewCategory = function(selector, contextid, categoryid) {

        this.contextid = contextid;
        this.categoryid = categoryid;

        var self = this;
        self.init(selector);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    NewCategory.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    NewCategory.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewCategory.prototype.init = function(selector) {
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
                var head =  Str.get_string('editcategory', 'local_courses');
            }else{
               var head =  Str.get_string('addcategory', 'local_courses');
            }
            return head.then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: self.getBody()
                });
            }.bind(self)).then(function(modal) {

                // Keep a reference to the modal.
                self.modal = modal;
                self.modal.getRoot().addClass('openLMStransition');
                self.modal.show();
                // Forms are big, we want a big modal.
                self.modal.setLarge();

                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.hidden, function() {
                    self.modal.setBody('');
                    self.modal.getRoot().animate({"right":"-85%"}, 500);
                    setTimeout(function(){
                        modal.destroy();
                    }, 1000);
                }.bind(this));

                // We want to hide the submit buttons every time it is opened.
                self.modal.getRoot().on(ModalEvents.shown, function() {
                    self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
                }.bind(this));


                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
                // We also catch the form submit event and use it to submit the form with ajax.
                self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
                self.modal.getRoot().animate({"right":"0%"}, 500);
                return this.modal;
            }.bind(this));


        // });

    };

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewCategory.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }

        params = {};
        params.jsonformdata = JSON.stringify(formdata);
        params.categoryid = this.categoryid;
        console.log(params);
        console.log(this);
        return Fragment.loadFragment('local_courses', 'coursecategory_form', this.contextid, params);
    };

    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    NewCategory.prototype.handleFormSubmissionResponse = function() {
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
    NewCategory.prototype.handleFormSubmissionFailure = function(data) {
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
    NewCategory.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        //console.log(this.contextid);
        //console.log(JSON.stringify(formData));
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_courses_submit_create_category_form',
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
    NewCategory.prototype.submitForm = function(e) {
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

            return new NewCategory(args.selector, args.contextid,args.categoryid);
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

        }
    };
});