 $(document).on('change', '#id_parentid', function() {
  var costcentervalue = $(this).find("option:selected").val();
   if (costcentervalue !== null) {
    $.ajax({
      method: "GET",
      dataType: "json",
      url: M.cfg.wwwroot + "/local/courses/custom_ajax.php?action=facultieslist&costcenter="+costcentervalue,
      success: function(data){
        if(data){
          var template = '<option value>Select Faculties</option>'
           $.each( data.data, function( index, value) {
             template +=  '<option value = ' + value.id + ' >' +value.facultyname + '</option>';
          });
          $("#id_faculty").html(template);
        }else{
          $("#id_error_faculty").html('<span>No faculties created under selected university</span>').show();
        }
      }
    });
  } 
  // $('#id_department').trigger('change');
  // $('#id_open_subdepartment').trigger('change');
  // $('#supervisorid').trigger('change');
});


 $(document).on('change', '.deptype', function() {
    var radioValue = $("input[name='deptype']:checked").val();
    if(radioValue == 1){
            $('.college').hide();
    }else if(radioValue == 0){
            $('.college').show();
    }
});

        
        
