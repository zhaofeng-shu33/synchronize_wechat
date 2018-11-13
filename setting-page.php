<div>
<h1> Wechat synchronization </h1>
<form method="post" action="options.php">
<?php settings_fields('ws-settings-group'); ?>
<?php do_settings_sections('ws-settings-group'); ?>
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
<form>
<div>
    <p>Get preivous articles Url</p>
    <label for="ws_Yes">Yes</label>
    <input type="radio" name="ws_history" id="ws_Yes"/>
    <label for="ws_No">No</label>
    <input type="radio" name="ws_history" id="ws_No"/>

     <p>Keep original style</p>
    <select class="custom-select" name="keep_style">
	    <option value="keep" selected>Yes</option>
	    <option value="remove">No</option>
    </select>
    <div class="form-group">
	    <label for="formGroupExampleInput2">Keep original author info</label>
	    <select class="custom-select" name="keep_source">
		    <option value="keep" selected>Yes</option>
		    <option value="remove">No</option>
	    </select>
    </div>
    <textarea type="text" name="given_urls"></textarea>
<?php submit_button(); ?>
</div>

</form>


</div>