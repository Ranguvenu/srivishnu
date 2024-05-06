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
        trainerUpcomingSessionsDatatable: function(args){
            $("#trainer_classroom_upcomingsessions").DataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "language": {
                    "emptyTable": "No Upcoming/Present Sessions available in table",
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                "bServerSide": true,
                "lengthChange": false,
                'pageLength': 5,
                "sAjaxSource": M.cfg.wwwroot + "/blocks/classroomsessions/sessions.php?action="+args+"",
            });
        },

        trainerPreviousSessionsDatatable: function(args){
            $("#trainer_classroom_previoussessions").dataTable({
                "searching": true,
                "responsive": true,
                "processing": true,
                "language": {
                    "emptyTable": "No Completed Sessions available in table",
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    },
                    "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                },
                "bServerSide": true,
                "lengthChange": false,
                'pageLength': 5,
                "sAjaxSource": M.cfg.wwwroot + "/blocks/classroomsessions/sessions.php?action="+args+"",
            });
        },
    };
});