// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handle selection changes and actions on the competency tree.
 *
 * @module     block_userdasboard/navigations
 * @package    block_userdasboard
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/url',
        'core/templates',
        'core/notification',
        'core/str',
        'core/ajax',
        'local_costcenter/cardPaginate',
        'block_userdashboard/userdashboard_elearning',
        'block_userdashboard/userdashboard_program',
        ],

       function($, url, templates, notification, str, ajax, CardPaginate, userdashboardElearning,  userdashboardProgram, userdashboardXseed) {
        var userdashboard;
        return userdashboard = {        
     
        /**
         * Initialise this page (attach event handlers etc).
         *
         * @method init
         * @param {Object} model The tree model provides some useful functions for loading and searching competencies.
         * @param {Number} pagectxid The page context ID.
         * @param {Object} taxonomies Constants indexed by level.
         * @param {Object} rulesMods The modules of the rules.
         */
        init: function() {  
           //  eleobj= {
           //    elearning_template :'block_userdashboard/elearning_courses_innercontent',
           //    target_selector :'#elearning_inprogress',
           //    filter :'',
           //    menu :'elearning',
           // };
           // var classobj={
           //   menu: 'classroom',
           // };

           $("#elearning_courses").on('click',this.menu_elearning_courses.bind(this)); 
           $("#program_courses").on('click',this.menu_program_courses.bind(this));
           // $("#userdashboard_filter").on('click',this.call_dashboard_courses.bind(this));
           // $("#userdashboard_filter").on('keyup',this.serachname());
          
        },

        load: function(){
            $("#accordion").accordion();
            $("#userdashboard_filter").val('');
        },
        serachname : function(){
          if($("#program_courses_tab").hasClass("active")){
                var myusermodule = "program_courses";
            }else if($("#elearning_courses_tab").hasClass("active")){
                var myusermodule = "elearning_courses";
            }
            if($("#completedinfo").hasClass("active")){
              var status = "completed";
            }else if($("#inprogressinfo").hasClass("active")){
              var status = "inprogress";
            }
            var searchvalue = $("#userdashboard_filter").val();
            if(myusermodule && status){
                var targetid = myusermodule+'_tabdata';
                var options = {targetID: targetid,
                            templateName: 'block_userdashboard/my_'+myusermodule,
                            methodName: 'block_userdashboard_data_for_'+myusermodule,
                            perPage: 5,
                            cardClass: 'col-md-6 col-12',
                            viewType: 'card'};
                var dataoptions = {tabname: myusermodule,contextid: 1 };
                var filterdata = {status:status,filterdata: searchvalue};
                require(['local_costcenter/cardPaginate'], function(cardPaginate) {
                    cardPaginate.reload(options, dataoptions,filterdata);
                });
            }
        },
        menu_elearning_courses : function(){
          return this.elearning_courses('menu','');
        },
        menu_program_courses : function(){
            return this.program_courses('menu','');
        },
        call_dashboard_courses: function(){
          
          var filter_text = $('#userdashboard_filter').val();
          var component = $('#userdashboard_filter').attr("data-component");
          var filter = $('#userdashboard_filter').attr("data-filter");
          // alert(filter_text);
          // alert(component);
          // alert(filter);
          switch(component){
            case "elearning_courses":
            // alert('here');
              return this.elearning_courses(filter,filter_text);
              break;
            case "program_courses":
              return this.program_courses(filter,filter_text);
              break;
          };
          
        }, 

        elearning_courses: function(filter,filter_text){
            // alert('1');
            // alert(filter_text);
            userdashboardElearning.callElearning(filter,filter_text); 
        },
        program_courses: function(filter,filter_text){
            userdashboardProgram.callProgram(filter,filter_text); 
        },
        makeActive: function(tab){
            $(document).ready(function(){
                if(!$("#courses_"+tab).hasClass('active')){
                    $("li.nav-item .nav-link.active").removeClass('active');
                    $("#courses_"+tab).addClass('active');
                }
            });
        },
        programinfotable: function(userid){
            options = {targetID: 'program_courses_tabdata',perPage:6,cardClass: 'w_oneintwo', viewType:'card',methodName: 'block_userdashboard_data_for_program_courses',templateName: 'block_userdashboard/my_program_courses'};
           

            dataoptions = {userid: userid,contextid: 1};

            filterdata = {status: 'inprogress'};
            
            CardPaginate.reload(options, dataoptions,filterdata);
        },
    }; 
});
