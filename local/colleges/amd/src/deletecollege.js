/**
 * Add a create new group modal to the page.
 *
 * @module     local_courses/newcourse
 * @class      NewCourse
 * @package    local_courses
 * @copyright  2017 Shivani
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
    var Delcollege = function(selector, contextid, categoryid, coursescount) {
        
        this.contextid = contextid;
        this.categoryid = categoryid;
        this.count = coursescount;
        var self = this;
        self.init(selector);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    Delcollege.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    Delcollege.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    Delcollege.prototype.init = function(selector) {
        //var triggers = $(selector);
        var self = this;
        // Fetch the title string.
        //$(selector).click(function(){
            
            //var editid = $(this).data("value");
            //if(typeof this.editid != 'undefined'){
            //        editid=0;
            //}
            // self.categoryid = editid;
              //alert(self.courseid);
             console.log(self.categoryid);
            return Str.get_string('deletecategory', 'local_courses').then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    title: title,
                    body: self.getBody(),
                    footer: this.getFooter(),
                });
            }.bind(self)).then(function(modal) {
                
                // Keep a reference to the modal.
               //  self.modal = modal;
               //  self.modal.show();
               //  // Forms are big, we want a big modal.
               //  self.modal.setLarge();
     
               //  // We want to reset the form every time it is opened.
               //  self.modal.getRoot().on(ModalEvents.hidden, function() {
               //      self.modal.setBody('');
               //  }.bind(this));
    
               //  // We want to hide the submit buttons every time it is opened.
               //  self.modal.getRoot().on(ModalEvents.shown, function() {
               //      self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
               //  }.bind(this));
     
               //  this.modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));
               //  // We catch the modal save event, and use it to submit the form inside the modal.
               //  // Triggering a form submission will give JS validation scripts a chance to check for errors.
               // // self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
               //  // We also catch the form submit event and use it to submit the form with ajax.
               //  self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
               //  return this.modal;

                // Keep a reference to the modal.
                this.modal = modal;
                // self.modal.show();
                // Forms are big, we want a big modal.
                this.modal.setLarge(); 
                
                // this.modal.getRoot().addClass('openLMStransition local_users');

                // this.modal.getRoot().on(ModalEvents.hidden, function() {
                //     this.modal.setBody('');
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    this.modal.getRoot().animate({"right":"-85%"}, 500);
                    setTimeout(function(){
                        modal.destroy();
                    }, 5000);
                }.bind(this));
                // console.log(this.count);
                
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
                    // modal.destroy();
                });

                this.modal.getRoot().on('submit', 'form', function(form) {
                    self.submitFormAjax(form, self.args);
                });
                this.modal.show();
                this.modal.getRoot().animate({"right":"0%"}, 500);

                return this.modal;
            }.bind(this));       
        
        
        //});
        
    };
 
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    Delcollege.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        if(typeof this.categoryid != 'undefined'){
            var params = {categoryid:this.categoryid, jsonformdata: JSON.stringify(formdata)};
        }else{
            var params = {};
        }
        //var params = {categoryid:this.categoryid, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_courses', 'deletecategory_form', this.contextid, params);
    };
 
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    Delcollege.prototype.handleFormSubmissionResponse = function() {
        this.modal.hide();
        // We could trigger an event instead.
        // Yuk.
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        document.location.reload();
    };
     /**
     * @method getFooter
     * @private
     * @return {Promise}
     */
    Delcollege.prototype.getFooter = function() {
        $footer = '';
        if(this.count == 0){
            $footer += '<button type="button" class="btn btn-primary" data-action="save">Delete</button>&nbsp;';
        }
        $footer += '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };

    /**
     * @method handleFormSubmissionFailure
     * @private
     * @return {Promise}
     */
    Delcollege.prototype.handleFormSubmissionFailure = function(data) {
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
    Delcollege.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        //console.log(this.contextid);
        // console.log(formData);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_courses_submit_delete_category_form',
            //args: {evalid:this.evalid, contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
            args: {contextid: this.contextid, jsonformdata: JSON.stringify(formData),categoryid:this.categoryid},
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
    Delcollege.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };
 
    return /** @alias module:local_evaluation/newevaluation */ {
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
            return new Delcollege(args.selector, args.contextid,args.categoryid,args.count);
        },
        load: function() {
        }
    };
});
