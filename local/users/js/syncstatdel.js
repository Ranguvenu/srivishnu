$(document).ready(function() {
    $("#btn_delete").click(function(){
        var id = [];
        $(":checkbox:checked").each(function(i){
            id[i] = $(this).val();
        });
        if(id.length === 0){ //tell you if the array is empty
            alert("Please Select atleast one checkbox");
        }    
        else if(confirm("Are you sure you want to delete this?")){
            $.ajax({
                url: M.cfg.wwwroot + "/local/users/sync/delete.php",
                method:"POST",
                data:{id:id},
                success:function(){
                    for(var i=0; i<id.length; i++){   
                        $("#"+id[i]+"").css("background-color", "#ccc");
                        $("#"+id[i]+"").fadeOut("slow");
                        window.location.assign("syncstatistics.php");
                    }
                }
            });
        }else{
            return false;
        }
    });
});