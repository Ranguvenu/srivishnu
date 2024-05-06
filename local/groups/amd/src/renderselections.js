define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events'],
        function($, Str, ModalFactory) {
    return {
        init: function() {
            $(document).on('change', '#id_costcenterid', function() {
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
          $("#id_departmentid").html(udept);
                            var nullcurriculum = '<option value = 0>Select Curriculum</option>';
                            $("#id_curriculumid").html(nullcurriculum);
                        }
                        
//                        method: "GET",
//                        dataType: "json",
//                        url: M.cfg.wwwroot + "/local/groups/ajax.php?action=departmentlist&costcenter="+costcentervalue,
//                        success: function(data){
//                            var template = '<option>Select Departments</option>';
//                              $.each( data.data, function( index, value) {
//                                   template +=	'<option value = ' + value.id + ' >' +value.fullname + '</option>';
//                              });
//                            $("#id_departmentid").html(template);
//                        }
                    });
                }
            });
        },
        displayusers: function(id) {
            $.ajax({
                url:M.cfg.wwwroot+"/local/groups/custom_ajax.php?page=1&id="+id,
                cache: false,
                success:function(result){
                        ModalFactory.create({
                        title: 'Enrolled users',
                        type: ModalFactory.types.DEFAULT,
                        body: result
                    }).done(function(modal) {
                            this.modal = modal;
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().find('[data-action="hide"]').on('click', function() {
                                modal.setBody('');
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            });
                    });                    
                },
                error: function(){
                    $('#onlinetestview'+id).html('error');
                },                
                dataType: "html"
            });
            
        },
        deletecohort: function(elem, name) {
            return Str.get_strings([{
                key: 'delcohort',
                component: 'local_groups'
            }, {
                key: 'delconfirm',
                component: 'local_groups',
                param:name
            }]).then(function(s) {
                ModalFactory.create({
                    title: s[0],
                    type: ModalFactory.types.DEFAULT,
                    body: s[1],
                    footer: '<button type="button" class="btn btn-primary" data-action="save">Yes</button>&nbsp;' +
        '<button type="button" class="btn btn-secondary" data-action="cancel">No</button>'
                }).done(function(modal) {
                    this.modal = modal;
                    modal.getRoot().find('[data-action="save"]').on('click', function() {
                        window.location.href =M.cfg.wwwroot +'/local/groups/edit.php?id='+elem+'&confirm=1&delete=1&sesskey=' + M.cfg.sesskey;
                    }.bind(this));
                    modal.getFooter().find('[data-action="cancel"]').on('click', function() {
                        modal.setBody('');
                        modal.hide();
                    });
                    modal.show();
                }.bind(this));
            }.bind(this));
        }
    };
});