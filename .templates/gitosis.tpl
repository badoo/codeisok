{include file='header.tpl'}
{include file='gitosismenu.tpl'}

{assign var=current_template value="gitosis`$current_section`.tpl"}
{include file=$current_template}

{include file='footer.tpl'}
