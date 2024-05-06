define(['local_notifications/jquery.dataTables', 'jquery', 'core/str', 'core/modal_factory', 'core/modal_events'],
        function(dataTable, $, Str, ModalFactory) {
    return {
        init: function() {
            $(document).on('change', '#id_notificationid', function() {
                // console.log('init');
                var notificationid = $(this).find("option:selected").val();
                 var costcenterid = $('#id_costcenterid').find("option:selected").val();
                // console.log(notificationid);
                if (notificationid !== null) {
                      $.ajax({
                        method: "GET",
                        dataType: "json",
                        url: M.cfg.wwwroot + "/local/notifications/custom_ajax.php?notificationid="+notificationid+"&page="+1+"&costcenterid="+costcenterid,
                        success: function(data){
                            // console.log(data);
                            //$(".form-control-static").append(JSON.stringify(data));
                            $(".form-control-static").html(data.datastrings);
                            var template ='';
                            $.each( data.datamoduleids, function( index, value) {
                                template += '<option value = ' + value.id + ' >' +value.name + '</option>';
                           });
                            
                            // if(template){
                            //     $(".module_label").css('display','block');
                            //     $("div .module_label .tag-info").html("");
                            //     // $("#id_moduleid").html(template);
                            //     // $(".module_label label").html(data.datamodule_label);
                            // }else{
                            //     $(".module_label").css('display','none');
                            // }
                            
                        }
                    });
                }
            });
            
        },
        //<revathi> issue ODL-808 Search is not working in notification starts--
        notificationDatatable: function(args) {
            params = [];
            params.action = 'display';
            params.id = args.id;
            params.context = args.context;
            //var oTable = $('#notification_info').dataTable({
            var table = $('#notification_info').DataTable({
                "columnDefs": [
                    { "targets": [1,3], "searchable": false }
                ],
                "bInfo" : false,
                "bLengthChange": false,
                "order": [],
                "language": {
                    "emptyTable": "No Notifications available in table",
                    "paginate": {
                        "next": ">",
                        "previous": "<"
                    }
                },
                "pageLength": 10
            });
             $('.dataTables_filter input').unbind().bind('keyup', function() {
               var searchTerm = this.value.toLowerCase(),regex = '\\b' + searchTerm + '\\b';
             table.rows().search(regex, true, false).draw();
            })
        },
         //<revathi> issue ODL-808 Search is not working in notification ends--
        deletenotification: function(elem) {
            return Str.get_strings([{
                key: 'deletenotification',
                component: 'local_notifications'
            }, {
                key: 'deleteconfirm_msg',
                component: 'local_notifications'
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
                        window.location.href ='index.php?delete='+elem+'&confirm=1&sesskey=' + M.cfg.sesskey;
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