{include file='header.tpl' no_user_header=1}

<form action="" method="POST">
    <div style="margin: 10px;">
        <div style="margin-bottom: 10px;">Please login with your tracker account:</div>
    <label for="login">Login: </label><input name="login" autofocus value="{$cur_login|escape}" />
    <label for="password">Password: </label><input type="password" name="password" value="{$cur_password|escape}" />
    <input type="submit" value="Submit" />
    <p>
    <label for="remember">Remember me <input type='checkbox' name='remember' id='remember' value='1' checked="checked"></label>
    </p>
    </div>
    {if $auth_error}
        <div>
            <span style="color: #ff0000;">{$auth_error}</span>
        </div>
    {/if}
</form>

{include file='footer.tpl'}
