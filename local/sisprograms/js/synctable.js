'use strict';
$(document).ready(function () {
    $('#errors').dataTable({
        "searching": true,
        "responsive": true,
        "language": {
        "paginate": {
            "previous": "<",
            "next": ">"
            }
        },
        "aaSorting": [],
        "bProcessing": true,
        "bServerSide": true,
        // "bLengthChange": false,
        "pageLength": 10,
        "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
        "sAjaxSource":M.cfg.wwwroot + "/local/sisprograms/error_processing.php",      
    });
    $('#sismasterusersdata').DataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "emptyTable": "No Users available in table",
                "paginate": {
                    "previous": "<",
                    "next": ">"
                    }
                },
                "aaSorting": [],
                "bProcessing": true,
                "bServerSide": true,
                // "bLengthChange": false,
                "pageLength": 10,
                "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
                "sAjaxSource":M.cfg.wwwroot + "/local/sisprograms/users_processings.php",       
    });
    $('#sisprogramstable').DataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "emptyTable": "No Programs available in table",
                "paginate": {
                    "previous": "<",
                    "next": ">"
                    }
                },
                "aaSorting": [],
                "bProcessing": true,
                "bServerSide": true,
                // "bLengthChange": false,
                "pageLength": 10,
                "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
                "sAjaxSource":M.cfg.wwwroot + "/local/sisprograms/programs_processing.php",       
    });
    $('#siscoursestable').DataTable({
                "searching": true,
                "responsive": true,
                "language": {
                    "emptyTable": "No Courses available in table",
                "paginate": {
                    "previous": "<",
                    "next": ">"
                    }
                },
                "aaSorting": [],
                "bProcessing": true,
                "bServerSide": true,
                // "bLengthChange": false,
                "pageLength": 10,
                "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
                "sAjaxSource":M.cfg.wwwroot + "/local/sisprograms/courses_processing.php",       
    });
});