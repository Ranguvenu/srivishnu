define(["jquery"],function(a){return{usernav_slider:function(b){var c="#"+b+".divslider .userdashboard_content",d=a("."+b)[0],e=a(d).html();console.log(d),a(d).remove(),a(c).append('<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12 courses_inprogress_slide ctype_elearning '+b+'">'+e+"</div>")},usernavrev_slider:function(b,c){var d="#"+b+".divslider .userdashboard_content",e=a("."+b)[c],f=a(e).html();console.log(e),a(e).remove(),a(d).prepend('<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12 courses_inprogress_slide ctype_elearning '+b+'">'+f+"</div>")}}});