/**
 * Add a create new group modal to the page.
 *
 * @module     local_courses/newcourse
 * @class      NewCourse
 * @package    local_courses
 * @copyright  2017 Shivani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events',
        'core/fragment', 'core/ajax', 'core/yui', 'jqueryui'],
        function(dataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
 
    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var NewPopup = function(selector, contextid, categoryid,categoryname) {
        
        this.contextid = contextid;
        this.categoryid = categoryid;
        this.categoryname = categoryname;
        // alert(categoryname);
        var self = this;
        self.init(selector);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    NewPopup.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    NewPopup.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewPopup.prototype.init = function(selector) {
        //var triggers = $(selector);
        var self = this;
        // Fetch the title string.
        $(selector).click(function(){
            
            var editid = $(this).data("value");
            //alert(editid);
            if(typeof this.editid != 'undefined'){
                    editid=0;
            }
             self.categoryid = editid;
              //alert(self.courseid);
            return Str.get_string('categorypopup', 'local_courses').then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.CANCEL,
                    title: 'Category : '+this.categoryname,
                    body: self.getBody()
                });
            }.bind(self)).then(function(modal) {
                
                // Keep a reference to the modal.
                self.modal = modal;
                self.modal.show();
                // Forms are big, we want a big modal.
                self.modal.setLarge();
     
                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.hidden, function() {
                    self.modal.setBody('');
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
                return this.modal;
            }.bind(this));       
        
        
        });
        
    };
 
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewPopup.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        //alert(this.courseid);
        // Get the content of the modal.
        if(typeof this.categoryid != 'undefined'){
            var params = {categoryid:this.categoryid, jsonformdata: JSON.stringify(formdata)};
        }else{
            var params = {};
        }
        return Fragment.loadFragment('local_courses', 'coursecategory_display', this.contextid, params);
    };
 
 
    return /** @alias module:local_courses/course */ {
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
            this.Datatable();
            return new NewPopup(args.selector, args.contextid,args.categoryid,args.categoryname);
        },
        Datatable: function() {
            
        }
    };
});