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
     <th scope="row"><label for="ws_history">Get preivous articles Url</label></th>
     <td>
        <select name="ws_history">
                <option value="ws_Yes" selected>Yes</option>
                <option value="ws_No">No</option>
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
     </tr>
    <tr>
     <th scope="row"><label for="keep_source">Keep original source info</label></th>
     <td>
        <select name="keep_source">
                <option value="keep" selected>Yes</option>
                <option value="remove">No</option>
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
    var submit_multiple = function(url_list){
        for(var i = 0; i < url_list.length; i++){ 
            var data_ = {
                'action': 'ws_process_request',
                'url_id': i,
                'given_urls': url_list[i],
                'keep_source': jQuery('select[name="keep_source"]').val(),
                'keep_style': jQuery('select[name="keep_style"]').val()
            };
            jQuery.ajax({
             url: ajaxurl,
             type: "POST",
             timeout: 50000,
             data: data_,
             success: function(data, textStatus, jqXHR){
                 var console = jQuery("#console");
                 console.attr("style", "display:block");
                 var previous_value = console.val();
                 console.val(previous_value  + data + "\n");
                 var row = parseInt(console.attr("rows"));
                 console.attr("rows", row+1);
             }
            })   
        }          
    }
   jQuery("#url").on('submit', function(e){
       e.preventDefault();
       var url_list_string = jQuery('textarea[name="given_urls"]').val();
       if(url_list_string.search("\n")>0){
           submit_multiple(url_list_string.split("\n"));
       }
       else{
            jQuery(this).ajaxSubmit({
            type: "POST",
            url: ajaxurl,
            timeout: 35000,
            data: {'action':'ws_process_request', 'offset': 0},
            success: function(data, textStatus, jqXHR){
                var result_array = JSON.parse(data);
                var console = jQuery("#console");
                if(result_array.length == undefined){
                    console.val(data);
                }
                else{
                    console.val("get urls : " + result_array.length + "\n");
                    console.attr("rows", 2);
                    // issue new requests for each url in result_array
                    // submit_multiple(result_array);
                }
                console.attr("style", "display:block");
            },
            error: function(data, textStatus, errorThrown){
                if(textStatus == 'timeout'){
                    jQuery("#console").val('timeout');
                    jQuery("#console").attr("style", "display:block");               
                }
            }   
            })
       }
    }); 
</script>
</div>