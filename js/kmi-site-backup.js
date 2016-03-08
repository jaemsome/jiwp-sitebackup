//function Ajax_Click(button, data, message_container, Success_Callback_Function)
//{
//    button.on('click', function(){
//        // Cancel event if already disabled
//        if(button.hasClass('disabled')) return false;
//            
//        jQuery.ajax({
//            url: ajax_object.ajax_url,
//            type: 'POST',
//            dataType: 'json',
//            data: data,
//            beforeSend: function() {
//                // Disable the button while processing
//                button.addClass('disabled');
//            },
//            success: function(response) {
//                // Delete previous message
//                jQuery('p.kmi-message').remove();
//                
//                // Check for the message container if it exists
//                if(message_container) {
//                    if(response.success) {
//                        ajax_response = response.success;
//                        // Add success message
//                        message_container.prepend('<p class="success kmi-message">'+response.success+'</p>');
//                    } else if(response.error) {
//                        // Add error message
//                        message_container.prepend('<p class="error kmi-message">'+response.error+'</p>');
//                    }
//                }
//                // Enable the button
//                button.removeClass('disabled');
//                    
//                if(Success_Callback_Function)
//                    Success_Callback_Function(response);
//            },
//            error: function(xhr, error) {
//                // Enable the button
//                button.removeClass('disabled');
//            }
//        });
//        // Cancel the event, no need to process by the server
//        return false;
//    });
//}
//
//jQuery.fn.KMI_Create_Backup = function() {
//    // Additional ajax success function
//    function Success_Callback_Function(response) {
//        if(response.success && response.file) {
//            // Add newly created backup into the list
//            var new_backup_file = '<tr id="backup_'+response.file.filename+'">';
//            new_backup_file += '<td>'+response.file.filename+'.zip</td>';
//            new_backup_file += '<td class="align-center">'+response.file.size+'</td>';
//            new_backup_file += '<td class="align-center">';
//            new_backup_file += '<a href="?page='+ajax_object.option_page+'&download='+response.file.filename+'.zip" class="dashicons dashicons-download" title="Download File" alt="Download File"></a>';
//            new_backup_file += '<a href="?page='+ajax_object.option_page+'&delete='+response.file.filename+'.zip" class="dashicons dashicons-trash kmi_delete_backup_btn" id="delete_'+response.file.filename+'" title="Delete File"></a>';
//            new_backup_file += '</td>';
//            new_backup_file += '</tr>';
//            jQuery('table#kmi_site_backup_list').append(new_backup_file);
//            
//            // Remove the empty row
//            jQuery('table#kmi_site_backup_list tr#kmi_empty_row').remove();
//        }
//    }
//            
//    // Send request
//    Ajax_Click(jQuery(this), {action: 'create_backup'}, jQuery('div.wrap'), Success_Callback_Function);
//};
//
//jQuery.fn.KMI_Delete_Backup = function() {
//    // Additional ajax success function
//    function Success_Callback_Function(response) {
//        if(response.success && response.file) {
//            // Remove the deleted row
//            jQuery('table#kmi_site_backup_list tr#backup_'+response.file.filename).remove();
//        }
//    }
//    
//    // Get the ID attribute
//    var btn_ID = jQuery(this).attr('id');
//            
//    // Send request
//    Ajax_Click(jQuery(this), {action: 'delete_backup', filename: btn_ID}, jQuery('div.wrap'), Success_Callback_Function);
//};

jQuery(document).ready(function($){
    var new_backup_counter = 0;
    
    function Ajax_Click(button, data, message_container, Success_Callback_Function, BeforeSend_Callback_Function)
    {
        button.on('click', function(){
            // Cancel event if already disabled
            if(button.hasClass('disabled')) return false;
            
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: data,
                beforeSend: function() {
                    // Disable the button while processing
                    button.addClass('disabled');
                    
                    if(BeforeSend_Callback_Function)
                        BeforeSend_Callback_Function(button);
                },
                success: function(response) {
                    // Delete previous message
                    $('p.kmi-message').remove();
                    
                    // Check for the message container if it exists
                    if(message_container) {
                        if(response.success) {
                            ajax_response = response.success;
                            // Add success message
                            message_container.prepend('<p class="success kmi-message">'+response.success+'</p>');
                        } else if(response.error) {
                            // Add error message
                            message_container.prepend('<p class="error kmi-message">'+response.error+'</p>');
                        }
                    }
                    // Enable the button
                    button.removeClass('disabled');
                    
                    if(Success_Callback_Function)
                        Success_Callback_Function(response);
                },
                error: function(xhr, error) {
                    // Enable the button
                    button.removeClass('disabled');
                }
            });
            // Cancel the event, no need to process by the server
            return false;
        });
    }
    
    $.fn.extend({
        KMI_Delete_Backup: function() {
            // Additional ajax success function
            function Success_Callback_Function(response) {
                if(response.success && response.file) {
                    // Remove the deleted row
                    $('table#kmi_site_backup_list tr#backup_'+response.file.filename).remove();
                    
                    // Add empty row if all backup files are deleted
                    var row_count = $('table#kmi_site_backup_list tr:last').index();
                    
                    if(row_count < 1) {
                        $('table#kmi_site_backup_list').append('<tr id="kmi_empty_row"><td class="align-center" colspan="3">No backup files found.</td></tr>');
                    }
                }
            }
            
            // Get the ID attribute
            var btn_ID = $(this).attr('id');
            
            // Send request
            Ajax_Click($(this), {action: 'delete_backup', filename: btn_ID}, $('div.wrap'), Success_Callback_Function, null);
        },
        KMI_Create_Backup: function() {
            // Additional ajax success function
            function Success_Callback_Function(response) {
                if(response.success && response.file) {
                    new_backup_counter++;
                    
                    // Add newly created backup into the list
                    var new_backup_file = '<tr id="backup_'+response.file.filename+'">';
                    new_backup_file += '<td>'+response.file.filename+'.zip</td>';
                    new_backup_file += '<td class="align-center">'+response.file.size+'</td>';
                    new_backup_file += '<td class="align-center">';
                    new_backup_file += '<a href="?page='+ajax_object.option_page+'&download='+response.file.filename+'.zip" class="dashicons dashicons-download" title="Download File" alt="Download File"></a>';
                    new_backup_file += '<a href="?page='+ajax_object.option_page+'&delete='+response.file.filename+'.zip" class="dashicons dashicons-trash kmi_delete_backup_btn kmi_new_backup_file_'+new_backup_counter+'" id="delete_'+response.file.filename+'" title="Delete File"></a>';
                    new_backup_file += '</td>';
                    new_backup_file += '</tr>';
                    $('table#kmi_site_backup_list').append(new_backup_file);
                    
                    // Add delete backup event to the newly added backup file
                    $('table#kmi_site_backup_list .kmi_new_backup_file_'+new_backup_counter).KMI_Delete_Backup();
                    
                    // Remove the empty row
                    $('table#kmi_site_backup_list tr#kmi_empty_row').remove();
                }
            }
            
            // Send request
            Ajax_Click($(this), {action: 'create_backup'}, $('div.wrap'), Success_Callback_Function, null);
        }
    });
    
    $('#kmi_create_backup_btn').KMI_Create_Backup();
    $('.kmi_delete_backup_btn').KMI_Delete_Backup();
});