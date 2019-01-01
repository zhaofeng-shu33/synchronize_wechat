<div>
<h1> Wechat synchronization </h1>
<form method="post" action="options.php">
<?php settings_fields('ws-settings-group'); ?>
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
     <th scope="row"><label for="ws_history">Get preivous articles</label></th>
     <td>
        <select name="ws_history">
                <option value="ws_Yes" selected>Yes</option>
                <option value="ws_No">No</option>
        </select>
     </td>
     <th scope="row"><label for="change_post_time">Use current time to publish</label></th>
     <td>
        <select name="change_post_time">
                <option value="ws_Yes">Yes</option>
                <option value="ws_No" selected>No</option>
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
<?php 
    // https://github.com/jquery-form/form
    wp_enqueue_script( 'jquery-form');
?>
    var url_list = [];
    var url_global_id = 0;
    var global_offset = 0;
    var get_news_termination = false;
    var console = jQuery("#console");
    var submit_single = function(url){
        var data_ = {
            'action': 'ws_process_request',
            'url_id': url_global_id,
            'given_urls': url,
            'keep_source': jQuery('select[name="keep_source"]').val(),
            'keep_style': jQuery('select[name="keep_style"]').val(),
            'debug': jQuery('select[name="debug"]').val()
        };
        url_global_id += 1;
        jQuery.ajax({
         url: ajaxurl,
         type: "POST",
         timeout: 50000,
         data: data_,
         success: function(data, textStatus, jqXHR){
             var previous_value = console.val();
             console.val(previous_value  + data + "\n");
             var row = parseInt(console.attr("rows"));
             console.attr("rows", row+1);
             var new_url = url_list.pop();
             if(new_url != undefined){
                 submit_single(new_url);
             }
             else{
                if(jQuery('select[name="ws_history"]').val() == 'ws_Yes' && get_news_termination == false)
                    get_news();
             }
         },
        error: function(jqXHR, textStatus, errorThrown){
            var previous_value = console.val();                    
            console.val(previoust_value + textStatus + '*'+ errorThrown + '*' + url + "\n");
        }           
        })        
    }
    var submit_multiple = function(){
        var submitted_length = url_list.length;
        for(var i = 0; i < Math.min(5, submitted_length); i++){ 
            var url = url_list.pop();
            submit_single(url);
        }          
    }
    var get_news = function(){
        var data_to_sent = 
                   {'action':'ws_process_request',
                    'offset': global_offset,
                    'ws_history':jQuery('select[name="ws_history"]').val()
                   };
        global_offset += 20;           
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            timeout: 35000,
            data: data_to_sent,
            success: function(data, textStatus, jqXHR){
                var result_array = JSON.parse(data);
                var previous_value = console.val();
                console.val(previous_value + "get urls : " + result_array.length + "\n");
                var row = parseInt(console.attr("rows"));
                console.attr("rows", row+1);
                // issue new requests for each url in result_array
                if(result_array.length == 0)
                    get_news_termination = true;
                else{
                    url_list = url_list.concat(result_array);
                    submit_multiple();                    
                }                
            },
            error: function(jqXHR, textStatus, errorThrown){
                var previous_value = console.val();                    
                console.val(previoust_value + textStatus + '*' + errorThrown + '*' + (global_offset - 20) + "\n");
            }   
        });        
    }
   jQuery("#url").on('submit', function(e){
       e.preventDefault();
       var url_list_string = jQuery('textarea[name="given_urls"]').val();
       jQuery("#console").attr("style", "display:block");
       if(url_list_string.length>0){
           url_list = url_list_string.split("\n");
           submit_multiple();
       }
       else{
           get_news();
       }
    }); 
</script>
</div>