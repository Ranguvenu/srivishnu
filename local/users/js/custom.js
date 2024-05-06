// $('#id_open_costcenterid').on('change', function() {
 $(document).on('change', '#id_open_costcenterid', function() {
  var costcentervalue = $(this).find("option:selected").val();
   if (costcentervalue !== null) {
		$.ajax({
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?action=departmentlist&costcenter="+costcentervalue,
      success: function(data){
          var template = '<option value= >--Select College--</option>';
          $.each(data.colleges, function( index, value) {
             template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
          });
          $("#id_open_collegeid").html(template);

          var udept = '<option value=0>--Select Department--</option>';
          $.each(data.departments, function( index, value) {
             udept +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
          });
          // console.log(udept);
          $("#id_open_departmentid").html(udept);
      }
		});
	} 
  $('#id_department').trigger('change');
  $('#id_open_subdepartment').trigger('change');
  $('#supervisorid').trigger('change');
});
 
// $('#id_department').on('change', function() {
 $(document).on('change', '#id_open_departmentid', function() {
   var costcentervalue = $(this).find("option:selected").val();
   var template = '<option value=0>Select Subdepartment</option>';
   if (costcentervalue != 0) {
  		$.ajax({
  			method: "GET",
  			dataType: "json",
  			url: M.cfg.wwwroot + "/local/users/ajax.php?action=subdepartmentlist&department="+costcentervalue,
        success: function(data){
         	$.each( data.data, function( index, value) {
  					template +=	'<option value = ' + value.id + ' >' +value.fullname + '</option>';
  				});
          $("#id_open_subdepartment").html(template);
        }
  		});
	} else{
      $("#id_open_subdepartment").html(template);
  }
  $('#id_open_subdepartment').trigger('change');
// $('#id_sub_sub_department').trigger('change');
});

//Supervisor List
/*  $(document).on('change', '#id_open_costcenterid', function() {
  var costcentervalue = $(this).find("option:selected").val();
   if (costcentervalue != 0) {
		$.ajax({
			method: "GET",
			dataType: "json",
			url: M.cfg.wwwroot + "/local/users/ajax.php?action=supervisorlist&supervisor="+costcentervalue,
      success: function(data){
        var template = '<option value=0>Select Supervisor</option>';
       	$.each( data.data, function( index, value) {
					template +=	'<option value = ' + value.id + ' >' +value.username + '</option>';
          
				});
        $("#open_supervisorid").html(template);
      }
    
		});
	} 
});*/
