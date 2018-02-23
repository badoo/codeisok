{include file='header.tpl' no_user_header=1}

<form class="login-form-container" action="" method="POST">
    <div class="login-form {if $auth_error}has-error{/if}">
        <a class="logo" href="index.php?a">codeisok</a>
        <strong>Please login with your tracker account</strong>

        <input type="text" class="text-input" name="login" placeholder="Login" autofocus value="{$cur_login|escape}" />
        <input type="password" class="text-input" placeholder="Password" name="password" value="{$cur_password|escape}" />

        {if $auth_error}
            <div class="login-error">
                <strong class="error-text login-error">{$auth_error}</strong>
            </div>
        {/if}

        <div>
            <input type='checkbox' class="checkbox-input" name='remember' id='remember' value='1' checked="checked"> <label for="remember">Remember me</label>
        </div>

        <input type="submit" value="Login" />
    </div>
</form>

{* {include file='footer.tpl'} *}
