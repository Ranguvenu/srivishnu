function displayRecords(lim, off) {
        $.ajax({
          type: "POST",
          async: false,
          url: M.cfg.wwwroot+"/local/users/team_actions/courseallocation_ajax.php?action=departmentusers&limit=" + lim + "&offset=" + off,
          // data: "action=departmentusers&limit=" + lim + "&offset=" + off,
          cache: false,
          beforeSend: function() {
            $("#loader_message").html("").hide();
            $('#loader_image').show();
          },
          success: function(html) {
            $("#results").append(html);
            $('#loader_image').hide();
            if (html === "") {
              $("#loader_message").html('<button data-atr="nodata" class="btn btn-default" type="button">No more records.</button>').show()
            } else {
              $("#loader_message").html('<button class="btn btn-default" type="button">Loading please wait...</button>').show();
            }
            window.busy = false;
 
          }
        });
} 
function learningtypefilter(learningtype){
       
        $('#learning_type').val(learningtype);
        var selectedcontent = $('#nominate_courseslist').val();
        var user = $("input[name='allocateuser']:checked").val();
        
        if(user == undefined){
                $( "#coursenominate_confirm").html("Please select Users.");
                dlg1 = $( "#coursenominate_confirm").dialog({
                        height: "auto",
                        width: "auto",
                        modal: true,
                        closeOnEscape: false,
                        buttons: {
                            Ok: function () {
                                dlg1.dialog("close");
                            }
                        },                        
                });
                return false;
        }       
        var type;
        switch(learningtype){
                case 4:
                        type = 'Learning Plan';
                break;
                case 2:
                        type = 'Classroom';                        
                break;
                case 3:
                        type = 'E-Learning';                        
                break;
                default:
                        type = 'LEARNING TYPE';        
                break;
        }
        $('.allocation_course_type').html(type + ' <span class="fa fa-angle-down"></span>');
        $.ajax({
                type: "GET",
                datatype: "json",
                url: M.cfg.wwwroot+"/local/users/team_actions/courseallocation_ajax.php?action=departmentcourses&learningtype=" + learningtype+'&selectedcontent='+selectedcontent+'&type=courses'+'&user='+user,
                success: function(data) {

                    // data = JSON.parse(data);
                    $(".departmentcourses").html(data);
                }
        });        
}
function departmentdata(type, data){
        var search = data.value.trim();
        var learningtype = $('#learning_type').val();
        var user = $("input[name='allocateuser']:checked").val();
        if(type === 'users'){
                var selectedcontent = $('#nominate_userslist').val();
        }else if(type === 'courses'){
                var selectedcontent = $('#nominate_courseslist').val();
                
        }
        $.ajax({
                type: "POST",
                url: M.cfg.wwwroot+"/local/users/team_actions/courseallocation_ajax.php?action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                // data: "action=searchdata&type="+type+'&search='+search+'&learningtype='+learningtype+'&selectedcontent='+selectedcontent+'&user='+user,
                success: function(data) {
                        $(".department"+type).html(data);
                }
        });
}
function confirm_courseallocate(){
    var allocateuser = $('#nominate_userslist').val();
    var type = $('#learning_type').val();
    var allocatecourse = $('#nominate_courseslist').val();
    // (".allocation_course_type").va
    if(!allocateuser.length){
            $( "#coursenominate_confirm").html("Please select Users.");
            dlg1 = $( "#coursenominate_confirm").dialog({
                    height: "auto",
                    width: "auto",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        Ok: function () {
                            dlg1.dialog("close");
                        }
                    },                        
            });
            return false;
    } else if(!allocatecourse.length){
            $( "#coursenominate_confirm").html("Please select Courses.");
            dlg2 = $( "#coursenominate_confirm").dialog({
                    height: "auto",
                    width: "auto",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        Ok: function () {
                            dlg2.dialog("close");
                        }
                    },                        
            });
            return false;
    }
    $( "#coursenominate_confirm").html("Are you sure you want to Approve/Reject selected requests?");
	dlg3 = $("#coursenominate_confirm").dialog({
        		height: "auto",
        		width: "auto",
                autoOpen: true,
        		modal: true,
                buttons: {
                    YES: function () {
                        // alert(allocateuser);
                        // alert(allocatecourse);
                        $.ajax({
                            type: "post",
                            url: M.cfg.wwwroot + "/local/users/team_actions/courseallocation_ajax.php?action=courseallocate"+type+"&allocateuser="+allocateuser+"&allocatecourse="+allocatecourse,
                            success: function () {
                                dlg3.dialog("close");
                                window.location.href = window.location.href; 
                            }
                        });                                
                    },
                    CANCEL: function () {
                        $(this).dialog("close");
                    }
                },
	       });
}
//$(function() {
//    $('#departmentusers').scroll(function() {
//      if($("#loading").css('display') == 'none') {
//        //if($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
//        if ($(this).scrollTop() + $(this).height() > $(this).height()){
//           var limitStart = $("#departmentusers li").length;
//           loadResults(limitStart); 
//        }
//      }
//    });
//});
function loadResults(limitStart) {
	$("#loading").show();
        $.ajax({
            url: M.cfg.wwwroot + "/local/users/team_actions/courseallocation_ajax.php",
            type: "post",
            dataType: "json",
            data: {
                    action: 'AjaxPagination',
                limitStart: limitStart
            },
            success: function(data) {
                   $.each(data, function(index, value) {
                   $("#results").append("<li id='"+index+"'>"+value+"</li>");
                 });
                 $("#loading").hide();     
            }
        });
}
$(document).on('click', '.allocateuser', function(){
        var selecteduser = $(this).val();
        var userslist = $('#nominate_userslist').val();        
        var courseslist = $('#nominate_courseslist').val();        
        if($(this).is(":checked")){
                if(!userslist){
                      $('#nominate_userslist').val(selecteduser);
                }else {
                     var users = userslist.split(',');
                     $('#nominate_userslist').val(userslist+','+selecteduser);
                }
        }else{
                var users = userslist.split(',');
                var position = users.indexOf(selecteduser);
                delete users[position];
                users = users.filter(function(x){
                        return (x !== (undefined || ''));
                });                                          
                users.join(',');
                $('#nominate_userslist').val(users);  
        }
});
$(document).on('click', '.allocatecourse', function(){
        var selecteduser = $(this).val();
        var courseslist = $('#nominate_courseslist').val();        
        if($(this).is(":checked")){
                $('.allocate_button').prop( "disabled", false );
                $('.allocation_course_type_btn').prop( "disabled", false );                 
                if(!courseslist){
                      $('#nominate_courseslist').val(selecteduser);
                }else {
                     var courses = courseslist.split(',');
                     $('#nominate_courseslist').val(courseslist+','+selecteduser);
                }
        }else{
                $('.allocate_button').prop( "disabled", true );
                $('.allocation_course_type_btn').prop( "disabled", true );                
                var courses = courseslist.split(',');
                var position = courses.indexOf(selecteduser);
                delete courses[position];
                courses = courses.filter(function(x){
                        return (x !== (undefined || ''));
                });                                          
                courses.join(',');
                $('#nominate_courseslist').val(courses);  
        }
});
$( document ).ready(function() {
        // var selector = '.dropdown-menu li a';
        //
        //$(selector).on('click', function(){
        //        alert("hi");
        //$(selector).removeClass('active');
        //$(this).addClass('active');
        //});
        $('.allocate_button').prop( "disabled", true );
        $('.allocation_course_type_btn').prop( "disabled", true );        
//        $(selector).click(function(){
//                
//  $(this).parents(".dropdown").find('.allocation_course_type').html($(this).text() + ' <span class="caret"></span>');
//  $(this).parents(".dropdown").find('.allocation_course_type').val($(this).data('value'));
//});
    // console.log( "ready!" );
});


function userinfo() {
       
}