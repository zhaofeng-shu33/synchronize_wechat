<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div>
<h1> Wechat synchronization </h1>
<form method="post" action="options.php">
<?php settings_fields('wsync-settings-group'); ?>
 <!--tab implementation in the future -->
 <table class="form-table">
        <tr valign="top">
        <th scope="row">AppId</th>
        <td><input type="text" name="appid" value="<?php echo esc_attr( get_option('appid') ); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">AppSecret</th>
        <td><input type="text" name="appsecret" value="<?php echo esc_attr( get_option('appsecret') ); ?>" /></td>
        </tr>
 </table>
<?php submit_button(); ?>
</form>
<form id="url">
<table class="form-table">
    <tr>
     <th scope="row"><label for="wsync_history">Get preivous articles</label></th>
     <td>
        <select name="wsync_history">
                <option value="wsync_Yes" selected>Yes</option>
                <option value="wsync_No">No</option>
        </select>
     </td>
     <th scope="row"><label for="change_post_time">Use current time to publish</label></th>
     <td>
        <select name="change_post_time">
                <option value="wsync_Yes">Yes</option>
                <option value="wsync_No" selected>No</option>
        </select>
     </td>     
     </tr>    
    <tr>
     <th scope="row"><label for="keep_style">Keep original style</label></th>
     <td>
        <select name="keep_style">
                <option value="keep" selected>Yes</option>
                <option value="remove">No</option>
        </select>
     </td>
     <th scope="row"><label for="post_status">Default post status</label></th>
     <td>
        <select name="post_status">
                <option value="publish" selected>publish</option>
                <option value="pending">pending</option>
                <option value="pending">draft</option>                
        </select>
     </td>       
     </tr>
    <tr>
     <th scope="row"><label for="keep_source">Keep original source info</label></th>
     <td>
        <select name="keep_source">
                <option value="keep" selected>Yes</option>
                <option value="remove">No</option>
        </select>
     </td>
     <th scope="row"><label for="debug">Debug mode</label></th>
     <td>
        <select name="debug">
                <option value="off">off</option>
                <option value="on" selected>on</option>
        </select>
     </td>      
     </tr>
     <tr>
     <textarea type="text" name="given_urls" class="large-text code" rows="3"></textarea>         
     </tr>
</table>
<?php submit_button("Synchronize"); ?>
</form>
<textarea id="console" class="large-text code" rows="1" style="display:none"></textarea>
<script>
    var wsync_url_list = [];
    var wsync_url_global_id = 0;
    var wsync_global_offset = 0;
    var wsync_is_debug = false;
    var wsync_get_newsync_termination = false;
    var wsync_console = jQuery("#console");
    var wsync_console_lines = 1;
    var wsync_submit_single = function(url){
        var data_ = {
            'action': 'wsync_process_request',
            'url_id': wsync_url_global_id,
            'given_urls': url,
            'keep_source': jQuery('select[name="keep_source"]').val(),
            'keep_style': jQuery('select[name="keep_style"]').val(),
            'debug': jQuery('select[name="debug"]').val()
        };
        wsync_url_global_id += 1;
        jQuery.ajax({
         url: ajaxurl,
         type: "POST",
         timeout: 50000,
         data: data_,
         success: function(data, textStatus, jqXHR){
             var previous_value = wsync_console.val();
             var data_json = JSON.parse(data);
             var extra_info = '';
             if(data_json['status_code'] < 0 && wsync_is_debug)
                extra_info = '*' + url;
             wsync_console.val(previous_value  + data + extra_info + "\n");
             var row = parseInt(wsync_console.attr("rows"));
             wsync_console.attr("rows", row+1);
             var new_url = wsync_url_list.pop();
             if(new_url != undefined){
                 wsync_submit_single(new_url);
             }
             else{
                if(jQuery('select[name="wsync_history"]').val() == 'wsync_Yes' && wsync_get_newsync_termination == false && jQuery('textarea[name="given_urls"]').val() == "")
                    wsync_get_news();
             }
         },
        error: function(jqXHR, textStatus, errorThrown){
            var console_value = wsync_console.val();  
            console_value += textStatus + '*'+ errorThrown + "\n";
            if(wsync_is_debug){
                console_value += url + "\n";
                console_value += jqXHR.responseText + "\n";
            }
            wsync_console.val(console_value);

        }           
        })        
    }
    var wsync_submit_multiple = function(){
        var submitted_length = wsync_url_list.length;
        for(var i = 0; i < Math.min(5, submitted_length); i++){ 
            var url = wsync_url_list.pop();
            wsync_submit_single(url);
        }          
    }

    var wsync_console_write = function(content){
    
    }
    var wsync_get_news = function(){
        var data_to_sent = 
                   {'action':'wsync_process_request',
                    'offset': wsync_global_offset,
                    'wsync_history':jQuery('select[name="wsync_history"]').val()
                   };
        wsync_global_offset += 20;           
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            timeout: 35000,
            data: data_to_sent,
            success: function(data, textStatus, jqXHR){
                var return_array = JSON.parse(data);
                var console_value = wsync_console.val();
                if(return_array.status_code < 0){
                    wsync_console.val(console_value + data + "\n");
                    return
                }
                var url_list = return_array.data;
                console_value +=  "get urls : " + url_list.length + "\n";
                var row = parseInt(wsync_console.attr("rows"));
                wsync_console.attr("rows", row+1);
                // issue new requests for each url in result_array
                if(url_list.length == 0)
                    wsync_get_newsync_termination = true;
                else if(wsync_is_debug == false){
                    wsync_url_list = wsync_url_list.concat(url_list);
                    wsync_submit_multiple();                    
                }                
                else{
                    console_value += url_list.join("\n") + "\n";
                }
                wsync.console.val(console_value);
            },
            error: function(jqXHR, textStatus, errorThrown){
                var console_value = wsync_console.val();
                console_value += textStatus + '*' + errorThrown 
                    + "at global offset : " + (wsync_global_offset - 20) + "\n";
                if(wsync_is_debug){
                    console_value +=  jqXHR.responseText + "\n";

                }
                wsync_console.val(console_value);
            }   
        });        
    }
   jQuery("#url").on('submit', function(e){
       e.preventDefault();
       wsync_is_debug = jQuery('select[name="debug"]').val() == 'on'; // refresh the debug status
       var wsync_url_list_string = jQuery('textarea[name="given_urls"]').val();
       wsync_console.attr("style", "display:block");
       if(wsync_url_list_string.length>0){
           wsync_url_list = wsync_url_list_string.split("\n");
           wsync_submit_multiple();
       }
       else{
           wsync_get_news();
       }
    }); 
</script>
</div>