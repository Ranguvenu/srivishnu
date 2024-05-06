/**
 * Add a create new group modal to the page.
 *
 * @module     local_courses/coursestatus
 * @class      NewCourse
 * @package    local_courses
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['local_courses/jquery.dataTables',
		'jquery',
		'core/str',
		'core/modal_factory',
		'core/modal_events',
		'core/fragment',
		'core/ajax',
		'core/yui',
		'jqueryui'],function(dataTable, $, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {
	/**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var courseStatus = function(args){
    	var self = this;
        self.init(args);
    };

    courseStatus.prototype.modal = null;

    courseStatus.prototype.contextid = -1;

    courseStatus.prototype.init = function(args){
    	var self = this;
    	// console.log(args);
    	var buttonname = $("#progressbardisplay_course").attr('data-name');
    	return Str.get_string('course_status_popup', 'local_courses',buttonname).then(function(title) {
    		return ModalFactory.create({
                type: ModalFactory.types.CANCEL,
                title: title,
                body: self.getBody(args)
            });
            
    	}.bind(self)).then(function(modal) {
			self.modal = modal;
	        self.modal.show();
    		

	        self.modal.setLarge();
	        self.modal.getRoot().on(ModalEvents.hidden, function() {
	            self.modal.destroy();
	            // self.modal.setBody('');
	        }.bind(this));
	        
	        // self.modal.getRoot().on(ModalEvents.shown, function() {
	        //     // self.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
	        // }.bind(this));
	    }.bind(this));
	};

	courseStatus.prototype.getBody = function(args){
		console.log(args);
		return Fragment.loadFragment('local_courses', 'coursestatus_display', 1, args);
	};
	// courseStatus.prototype.statusDatatable = function(){

	// 	// var table_rows = $('#scrolltable tr');
		
	// 	// console.log(table_rows.length);
	// 	// alert(table_rows.length);
	// 	// if(table_rows.length>6){	
	// 		$('#scrolltable').dataTable({
	// 			"searching": false,
	// 			"language": {
	//             	"paginate": {
	//                 	"next": ">",
	//                 	"previous": "<"
	//             	}
	//         	},
	//         	"pageLength": 5,
	// 		});
	// 		alert('done');
	// 	// }
	// };


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
    	statuspopup : function(args){
    		return new courseStatus(args);
    	},
    	
		load : function(){
    		// alert('here');

		}
	};
});
