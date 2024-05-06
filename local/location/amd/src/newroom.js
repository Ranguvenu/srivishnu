/**
 * Add a create new group modal to the page.
 *
 * @module     local_location/location
 * @class      NewRoom
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
    var NewRoom = function(args) {
        this.contextid = args.contextid;


        this.roomid = args.roomid;
        var self = this;
        self.init(args.selector);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    NewRoom.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    NewRoom.prototype.contextid = -1;

    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewRoom.prototype.init = function(args) {
        //var triggers = $(selector);
        var self = this;

        // Fetch the title string.
        // $('.'+args.selector).click(function(){

            var editid = $(this).data('value');
            if(editid) {
                self.roomid = editid;
            }
            if(self.roomid != 0){
                header_label = 'updateroom';
            }else{
                header_label = 'createroom';
            }
            return Str.get_string(header_label, 'local_location').then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.DEFAULT,
                    title: title,
                    body: self.getBody(),
                    footer: this.getFooter()
                });
            }.bind(self)).then(function(modal) {

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
    NewRoom.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        var params = {roomid:this.roomid, jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment('local_location', 'new_roomform', this.contextid, params);
    };
    NewRoom.prototype.getFooter = function() {
        console.log(this);
        if(this.roomid){
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
    NewRoom.prototype.handleFormSubmissionResponse = function() {
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
    NewRoom.prototype.handleFormSubmissionFailure = function(data) {
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
    NewRoom.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_location_submit_roomform_form',
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
    NewRoom.prototype.submitForm = function(e) {
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

            return new NewRoom(args);
        },
        load: function(){
            $(document).on('change', '#id_costcenter', function() {
                var universityvalue = $(this).find("option:selected").val();
                if (universityvalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/location/ajax.php?action='univ_colleges'&costcenter="+universityvalue,
                    success: function(data){
                      if(data.data !== null){
                        var template = '<option value=0>Select Univ.Dept/College</option>';
                        $.each( data.data, function( index, value) {
                             template +='<option value = ' + value.id + ' >' +value.fullname + '</option>';
                        });
                      }else{
                        var template = '<option value=0>No Univ.Dept/College under this university</option>';
                      }
                      var template1 = '<option value=0>Select Location</option>';
                      $("#id_subcostcenter").html(template);
                      $("#id_location").html(template1);
                  }
                    });
                } 
            });
            $(document).on('change', '#id_subcostcenter', function() {
                var subcostcentervalue = $(this).find("option:selected").val();
                // var costcentervalue = $("#id_costcenter").val();
                var costcentervalue = $("[name='costcenter']").val();
                if (costcentervalue !== null) {
                    $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/location/ajax.php?action=roomlocation&costcenter="+costcentervalue+"&subcostcenter="+subcostcentervalue,
                        success: function(data){
                            var template = '<option value = 0>Select Location</option>';

                              $.each( data.data, function( index, value) {
                                   template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
                              });
                            $("#id_location").html(template);
                        }
                    });
                }
            });
        },
        deleteConfirm: function(args) {
            // console.log(args);
            return Str.get_strings([{
                key: 'confirm'
            },
            {
                key: 'confirm',
                component: 'local_costcenter',
                param :args
            },
            {
                key: 'suspendallconfirm',
                component: 'local_costcenter'
            },
            {
                key: 'delete'
            }]).then(function(s) {
                var type = ModalFactory.types.SAVE_CANCEL;
                var body = args.actionstatusmsg;
                var title = Str.get_string('deleteconfirmation', 'local_costcenter');
                if(args['count'] > 0){
                    var roomcount = args['count'];
                    title = 'Alert!';
                    type = ModalFactory.types.CANCEL;
                    body = Str.get_string('roomvalidation', 'local_location', roomcount);
                    s[0] = "Alert!";
                }
                ModalFactory.create({
                    title: title,
                    type: type,
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
                        // params.contextid = args.contextid;
                    
                        var promise = Ajax.call([{
                            methodname: 'local_location_delete_room',
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
});
