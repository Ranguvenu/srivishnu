$(document).ready(function() {
	$(document).on('click', "#id_program_approval", function () {
        	if(($(this).is(":checked")))
            {
                $("#status_block").show();
            } else {
                $("#status_block").hide();
            }
  });
});

