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
<p>Sync preivous articles:</p>
<label for="ws_Yes">Yes</label>
<input type="radio" name="ws_history" id="ws_Yes"/>
<label for="ws_No">No</label>
<input type="radio" name="ws_history" id="ws_No"/>
</div>
<?php submit_button(); ?>
</form>
</div>