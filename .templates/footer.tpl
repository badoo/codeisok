{*
 *  footer.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page footer template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
    <div class="page_footer">
        {if $project}
            <div class="page_footer_text">{$project->GetDescription()}</div>
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=rss" class="rss_logo">{t}RSS{/t}</a>
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=atom" class="rss_logo">{t}Atom{/t}</a>
        {else}
            <a href="{$SCRIPT_NAME}?a=opml" class="rss_logo">{t}OPML{/t}</a>
            <a href="{$SCRIPT_NAME}?a=project_index" class="rss_logo">{t}TXT{/t}</a>
        {/if}

        {if $supportedlocales}
            <div class="lang_select">
                <form action="{$SCRIPT_NAME}" method="get" id="frmLangSelect">
                    <div>
                        {foreach from=$requestvars key=var item=val}
                            {if $var != "l"}
                                <input type="hidden" name="{$var|escape}" value="{$val|escape}" />
                            {/if}
                        {/foreach}
                        <label for="selLang">{t}language:{/t}</label>
                        <select name="l" id="selLang">
                            {foreach from=$supportedlocales key=locale item=language}
                                <option {if $locale == $currentlocale}selected="selected"{/if} value="{$locale}">{if $language}{$language} ({$locale}){else}{$locale}{/if}</option>
                            {/foreach}
                        </select>
                        <input type="submit" value="{t}set{/t}" id="btnLangSet" />
                    </div>
                </form>
            </div>
        {/if}
    </div>
    <!--div class="attr_footer">
    	<a href="http://xiphux.com/programming/gitphp/" target="_blank">GitPHP by Chris Han</a>
    </div-->
  </body>
</html>
