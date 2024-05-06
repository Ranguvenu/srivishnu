/**
 * Add a create new group modal to the page.
 *
 * @module     local_costcenter/costcenter
 * @class      NewCostcenter
 * @package    local_costcenter
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
    var NewSubdept = function(args) {
        this.contextid = args.contextid;
        this.subdept = args.subdept;
        this.dept = args.dept;
        
        this.costcenterid = args.costcenterid;
        this.cid = args.costcenterids;
        this.parentid = args.parentid;
        var self = this;
        self.init(args.selector);
    };
 
    /**
     * @var {Modal} modal
     * @private
     */
    NewSubdept.prototype.modal = null;
 
    /**
     * @var {int} contextid
     * @private
     */
    NewSubdept.prototype.contextid = -1;
 
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewSubdept.prototype.init = function(args) {
        console.log(args);
        //var triggers = $(selector);
        var self = this;



        // Fetch the title string.
        // $('.'+args.selector).click(function(){
            

            var editid = $(this).data('value');
            if (editid) {
                self.costcenterid = editid;
                //console.log(self.costcenterid);
                //alert(self.costcenterid);
            }
            if (self.dept) {
               
                var head =  Str.get_string('adnewdept', 'local_costcenter');
                
            }else{
               var head =  Str.get_string('adnewsubdept', 'local_costcenter');
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
               
                self.modal.getRoot().addClass('openLMStransition local_costcenter');
                // Forms are big, we want a big modal.
                self.modal.setLarge();
     
                // We want to reset the form every time it is opened.
                self.modal.getRoot().on(ModalEvents.hidden, function() {
                    self.modal.setBody(self.getBody());
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
    NewSubdept.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // alert(formdata);
        // Get the content of the modal.
        if(this.cid)
        var params = {costcenterid:this.costcenterid, jsonformdata: JSON.stringify(formdata),parentid:this.parentid,subdept:this.subdept,cid:this.cid,dept:this.dept};
        else
        var params = {costcenterid:this.costcenterid, jsonformdata: JSON.stringify(formdata),parentid:this.parentid,subdept:this.subdept,dept:this.dept};      
        return Fragment.loadFragment('local_costcenter', 'new_costcenterform', this.contextid, params);
    };
 
    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    NewSubdept.prototype.handleFormSubmissionResponse = function() {
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
    NewSubdept.prototype.handleFormSubmissionFailure = function(data) {
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
    NewSubdept.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
 
        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // alert(this.contextid);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_costcenter_submit_costcenterform_form',
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
    NewSubdept.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };
    return /** @alias module:local_costcenter/newcostcenter */ {
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
                    return new NewSubdept(args);
                },
                load: function(){
        
                },
        costcenterStatus: function(args) {
            console.log(args);
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
                            methodname: 'local_costcenter_status_confirm',
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
          
        }
    };
});