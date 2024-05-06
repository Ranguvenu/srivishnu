define([
    'local_users/responsive.bootstrap',
    // 'core/ajax',
    'jquery',
    'jqueryui'
], function(dataTable, $) {
    var trainerview;
    return trainerview = {
        init: function() {
            
        },
        trainerprogramsDatatable: function(){
            // alert('args');
            $("#trainer_programs_courses").dataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                "bServerSide": true,
                "lengthChange": false,
               'pageLength': 2,
                 "sAjaxSource": M.cfg.wwwroot + "/blocks/faculty_dashboard/distance_programs.php",
            });
        },
        viewcountDatatable: function(){
            $("#listofusers").DataTable({
                  "searching": true,
                  "responsive": true,
                  "processing": true,
                  "language": {
                      "emptyTable": "No Users available in table",
                      "paginate": {
                          "previous": "<",
                          "next": ">"
                      },
                      "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                  },
                   "aaSorting": [],                  
                   "pageLength": 10, 
           });
        },
        trainerdistanceprogramsDatatable: function(){
            // alert('args');
            $("#trainer_distanceprograms_courses").dataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                "bServerSide": true,
                "lengthChange": false,
               'pageLength': 2,
                "sAjaxSource": M.cfg.wwwroot + "/blocks/faculty_dashboard/reg_programs.php",
            });
        },
    };
});