$(document).ready(function(){
     $(".myteam_status").dataTable({
          'iDisplayLength':5,
		'bLengthChange': false,
		'bInfo': false,
		language: {
               search: "",
               searchPlaceholder:"Search Team Members",
			paginate: {
				'previous': '<',
				'next': '>'
			},
        oLanguage: { "search": '<i class="fa fa-search"></i>' },
            "emptyTable": "<div class='alert alert-info'>No records found</div>"
		}
     });
});
