/**
 * Add a create new group modal to the page.
 *
 * @module     local_location/location
 * @class      NewInstitute
 * @package    local_location
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
    var NewAssignrole = function(args) {
        this.contextid = args.contextid;


        this.roleid = args.roleid;

        this.rolename = args.rolename;
        // alert(args.roleid);
        var self = this;
        self.init(args.selector);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    NewAssignrole.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    NewAssignrole.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewAssignrole.prototype.init = function(args) {
        // console.log(args);
        //var triggers = $(selector);
        var self = this;



        // Fetch the title string.
        // $('.'+args.selector).click(function(){


            // var editid = $(this).data('value');
            // if (editid) {
            //     self.roleid = editid;
            //     // console.log(self.roleid);
            //     // alert(self.roleid);
            // }
            // console.log(self);
            // alert(self);
            return Str.get_string('assignrole', 'local_assignroles',self).then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    title: title,
                    body: self.getBody(),
                    footer: self.getFooter(),
                });
            }.bind(self)).then(function(modal) {

                // Keep a reference to the modal.
                self.modal = modal;
                // self.modal.show();
                // Forms are big, we want a big modal.
                self.modal.setLarge();
                this.modal.getRoot().addClass('openLMStransition');

                // We want to reset the form every time it is opened.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    this.modal.getRoot().animate({"right":"-85%"}, 500);
                    setTimeout(function(){
                        modal.destroy();
                    }, 1000);
                }.bind(this));

                // We want to hide the submit buttons every time it is opened.
                self.modal.getRoot().on(ModalEvents.shown, function() {
                    self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
                    // this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                    //     modal.hide();
                    //     setTimeout(function(){
                    //         modal.destroy();
                    //     }, 1000);
                    //     // modal.destroy();
                    // });
                }.bind(this));

                this.modal.getFooter().find('[data-action="save"]').on('click', this.submitForm.bind(this));

                this.modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                    modal.hide();
                    modal.destroy();
                });


                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                // self.modal.getRoot().on(ModalEvents.save, self.submitForm.bind(self));
                // We also catch the form submit event and use it to submit the form with ajax.
                self.modal.getRoot().on('submit', 'form', self.submitFormAjax.bind(self));
                self.modal.show();
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
    NewAssignrole.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // alert(formdata);
        // Get the content of the modal.
        var params = {roleid:this.roleid, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_assignroles', 'new_assignrole', this.contextid, params);
    };

    /**
     * @method handleFormSubmissionResponse
     * @private
     * @return {Promise}
     */
    NewAssignrole.prototype.handleFormSubmissionResponse = function() {
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
    NewAssignrole.prototype.handleFormSubmissionFailure = function(data) {
        // Oh noes! Epic fail :(
        // Ah wait - this is normal. We need to re-display the form with errors!
        this.modal.setBody(this.getBody(data));
    };

    /**
     * @method getFooter
     * @private
     * @return {Promise}
     */
    NewAssignrole.prototype.getFooter = function() {
        $footer = '<button type="button" class="btn btn-primary" data-action="save">Assign</button>&nbsp;';
        $footer += '<button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>';
        return $footer;
    };

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    NewAssignrole.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // alert(this.contextid);
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_assignroles_submit_assignrole_form',
            args: {contextid: this.contextid, roleid:this.roleid, jsonformdata: JSON.stringify(formData)},
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
    NewAssignrole.prototype.submitForm = function(e) {
        e.preventDefault();
        var self = this;
        self.modal.getRoot().find('form').submit();
    };

    return /** @alias module:local_location/newlocation */ {
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
            return new NewAssignrole(args);
        },
        load: function(){
             $(document).on('change', '#id_costcenter', function() {
                var costcentervalue = $(this).find("option:selected").val();
                var roleid = $("input[name=roleid]").val();

                if (costcentervalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/assignroles/ajax.php?action=userview&costcenter="+costcentervalue+"&roleid="+roleid,
                        success: function(data){
                            var template = '<option value = 0>--Select Users--</option>';

                              $.each(data.data, function( index, value) {
                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                              });
                            $("#id_users").html(template);
                        }
                    });
                }
            });

        }
    };
});