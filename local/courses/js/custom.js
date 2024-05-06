function enablecourseselling(courseid){
   if(confirm("Are you sure you want to change the status")){
		var id = courseid;
		 if(id!=""){
		$.ajax({
			url: "ajax.php",
			type: "POST",
			data: {id : id},
			cache: false,
			success: function(html){
			    $("#display_info"+courseid).append(html);
			    $("#disablecourseselling"+courseid).remove();
			    $("#enablecourseselling"+courseid).remove();		
                $("#enableinfo"+courseid).addClass('fa fa-toggle-on');               
                $("#enableinfo"+courseid).css('color','#5867dd');
                $("#enableinfo"+courseid).css('font-size','16px');
                $("#enableinfo"+courseid).css("margin-right",".5rem");
 
			}
		});	
      }	
	    
    }
}
function enablecoursesellings(courseid){
   if(confirm("Are you sure you want to change the status")){

		var id = courseid;
		 if(id!=""){
		$.ajax({
			url: "ajax.php",
			type: "POST",
			data: {course : id},
			cache: false,
			success: function(data){
		
				if(data == '0'){	
				$("#enablecourseselling"+courseid).remove();			
                  $("#enableinfo"+courseid).removeClass('fa fa-toggle-on').addClass('fa fa-toggle-off');
				}
				else{                 
			/*    $("#display_info"+courseid).remove();
			    $("#display_info"+courseid).append(data);
			  */
			  $("#enablecourseselling"+courseid).remove();		
               $("#enableinfo"+courseid).removeClass('fa fa-toggle-off').addClass('fa fa-toggle-on');

                }            
 
			}
		});	
      }	
	    
    }
}
