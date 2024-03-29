{extends file="page.tpl"}

{block name="content"}
    <form action="{path_for name="mailings_filter"}" method="post" id="filtre">
        <div id="listfilter">
            <label for="start_date_filter">{_T string="since"}</label>&nbsp;
            <input type="text" name="start_date_filter" id="start_date_filter" maxlength="10" size="10" value="{$history->filters->start_date_filter}"/>
            <label for="end_date_filter">{_T string="until"}</label>&nbsp;
            <input type="text" name="end_date_filter" id="end_date_filter" maxlength="10" size="10" value="{$history->filters->end_date_filter}"/>
            <input type="submit" class="inline" value="{_T string="Filter"}"/>
            <input type="submit" name="clear_filter" class="inline" value="{_T string="Clear filter"}"/>

            <div>
                <label for="subject_filter">{_T string="Subject"}</label>
                <input type="text" name="subject_filter" id="subject_filter" value="{$history->filters->subject_filter}"/>
                {_T string="Sent mailings:"}
                <input type="radio" name="sent_filter" id="filter_dc_sent" value="{Galette\Core\MailingHistory::FILTER_DC_SENT}"{if $history->filters->sent_filter eq constant('Galette\Core\MailingHistory::FILTER_DC_SENT')} checked="checked"{/if}>
                <label for="filter_dc_sent" >{_T string="Don't care"}</label>
                <input type="radio" name="sent_filter" id="filter_sent" value="{Galette\Core\MailingHistory::FILTER_SENT}"{if $history->filters->sent_filter eq constant('Galette\Core\MailingHistory::FILTER_SENT')} checked="checked"{/if}>
                <label for="filter_sent" >{_T string="Yes"}</label>
                <input type="radio" name="sent_filter" id="filter_not_sent" value="{Galette\Core\MailingHistory::FILTER_NOT_SENT}"{if $history->filters->sent_filter eq constant('Galette\Core\MailingHistory::FILTER_NOT_SENT')} checked="checked"{/if}>
                <label for="filter_not_sent" >{_T string="No"}</label>
            </div>
        </div>
        <table class="infoline">
            <tr>
                <td class="left nowrap">
                    {_T string="%count entry" plural="%count entries" count=$history->getCount() pattern="/%count/" replace=$history->getCount()}
                </td>
                <td class="right">
                    <label for="nbshow">{_T string="Records per page:"}</label>
                    <select name="nbshow" id="nbshow">
                        {html_options options=$nbshow_options selected=$numrows}
                    </select>
                    <noscript> <span><input type="submit" value="{_T string="Change"}" /></span></noscript>
                    {include file="forms_types/csrf.tpl"}
                </td>
            </tr>
        </table>
    </form>


        <table class="listing">
            <thead>
                <tr>
                    <th class="small_head">#</th>
                    <th class="left date_row">
                        <a href="{path_for name="mailings" data=["option" => "order", "value" => "Galette\Filters\MailingsList::ORDERBY_DATE"|constant]}">
                            {_T string="Date"}
                            {if $history->filters->orderby eq constant('Galette\Filters\MailingsList::ORDERBY_DATE')}
                                {if $history->filters->ordered eq constant('Galette\Filters\MailingsList::ORDER_ASC')}
                            <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
                                {else}
                            <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left username_row">
                        <a href="{path_for name="mailings" data=["option" => "order", "value" => "Galette\Filters\MailingsList::ORDERBY_SENDER"|constant]}">
                            {_T string="Sender"}
                            {if $history->filters->orderby eq constant('Galette\Filters\MailingsList::ORDERBY_SENDER')}
                                {if $history->filters->ordered eq constant('Galette\Filters\MailingsList::ORDER_ASC')}
                            <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
                                {else}
                            <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left small_head">
                        {_T string="Recipients"}
                    </th>
                    <th class="left">
                        <a href="{path_for name="mailings" data=["option" => "order", "value" => "Galette\Filters\MailingsList::ORDERBY_SUBJECT"|constant]}">
                            {_T string="Subject"}
                            {if $history->filters->orderby eq constant('Galette\Filters\MailingsList::ORDERBY_SUBJECT')}
                                {if $history->filters->ordered eq constant('Galette\Filters\MailingsList::ORDER_ASC')}
                            <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
                                {else}
                            <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th title="{_T string="Attachments"}" class="small_head">
                        {_T string="Att."}
                    </th>
                    <th class="left right small_head">
                        <a href="{path_for name="mailings" data=["option" => "order", "value" => "Galette\Filters\MailingsList::ORDERBY_SENT"|constant]}">
                            {_T string="Sent"}
                            {if $history->filters->orderby eq constant('Galette\Filters\MailingsList::ORDERBY_SENT')}
                                {if $history->filters->ordered eq constant('Galette\Filters\MailingsList::ORDER_ASC')}
                            <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
                                {else}
                            <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="small_head"></th>
                </tr>
            </thead>
            <tbody>
{foreach from=$logs item=log name=eachlog}
                <tr class="{if $smarty.foreach.eachlog.iteration % 2 eq 0}even{else}odd{/if}">
                    <td data-scope="row">
    {if $preferences->pref_show_id}
                        {$log.mailing_id}
    {else}
                        {$smarty.foreach.eachlog.iteration}
    {/if}
                        <span class="row-title">
                            {_T string="Mailing entry %id" pattern="/%id/" replace=$log.mailing_id}
                        </span>
                    </td>
                    <td class="nowrap" data-title="{_T string="Date"}">{$log.mailing_date|date_format:{_T string="Y-m-d H:i:s"}}</td>
                    <td data-title="{_T string="Sender"}">{if $log.mailing_sender eq 0}{_T string="Superadmin"}{else}{$log.mailing_sender_name}{/if}</td>
                    <td data-title="{_T string="Recipients"}">{$log.mailing_recipients|unserialize|@count}</td>
                    <td data-title="{_T string="Subject"}">{$log.mailing_subject}</td>
                    <td class="center" data-title="{_T string="Attachments"}">{$log.attachments}</td>
                    <td class="center{if $log.mailing_sent == 1} use{/if}" data-title="{_T string="Sent"}">
                        {if $log.mailing_sent == 1}
                            <i class="fas fa-thumbs-up"></i>
                        {else}
                            <i class="fas fa-thumbs-down"></i>
                        {/if}
                    </td>
                    <td class="center nowrap actions_row">
                        <a
                            href="{path_for name="mailingPreview" data=["id" => $log.mailing_id]}"
                            class="showdetails tooltip"
                        >
                            <i class="fas fa-eye"></i>
                            <span class="sr-only">{_T string="Display mailing '%subject' details in preview window" pattern="/%subject/" replace=$log.mailing_subject}</span>
                        </a>
                        <a
                            href="{path_for name="mailing"}?from={$log.mailing_id}"
                            class="tooltip"
                        >
                            <i class="fas fa-clone"></i>
                            <span class="sr-only">{_T string="Use mailing '%subject' as a template for a new one" pattern="/%subject/" replace=$log.mailing_subject}</span>
                        </a>
                        <a
                            href="{path_for name="removeMailing" data=["id" => $log.mailing_id]}"
                            class="delete tooltip"
                        >
                            <i class="fas fa-trash"></i>
                            <span class="sr-only">{_T string="Delete mailing '%subject'" pattern="/%subject/" replace=$log.mailing_subject}</span>
                        </a>
                    </td>
                </tr>
{foreachelse}
                <tr><td colspan="8" class="emptylist">{_T string="No sent mailing has been stored in the database yet."}</td></tr>
{/foreach}
            </tbody>
        </table>
        <div class="center cright">
            {_T string="Pages:"}<br/>
            <ul class="pages">{$pagination}</ul>
        </div>
        <div class="center">
            <a
                class="button use"
                href="{path_for name="mailing"}?mailing_new=true"
            >
                <i class="fas fa-plus-square"></i>
                {_T string="Create new mailing"}
            </a>
        </div>
{/block}

{block name="javascripts"}
        <script type="text/javascript">
            {include file="js_removal.tpl"}

            $('#start_date_filter, #end_date_filter').datepicker({
                changeMonth: true,
                changeYear: true,
                showOn: 'button',
                buttonText: '<i class="far fa-calendar-alt"></i> <span class="sr-only">{_T string="Select a date" escape="js"}</span>'
            });

            {* Preview popup *}
            $('.showdetails').click(function(){
                $.ajax({
                    url: $(this).attr('href'),
                    type: "POST",
                    data: {
                        ajax: true,
                    },
                    {include file="js_loader.tpl"},
                    success: function(res){
                        _preview_dialog(res);
                    },
                    error: function() {
                        alert("{_T string="An error occurred displaying preview :(" escape="js"}");
                    }
                });
                return false;
            });

            var _preview_dialog = function(res){
                var _el = $('<div id="ajax_preview" title="{_T string="Mailing preview" escape="js"}"> </div>');
                _el.appendTo('body').dialog({
                    modal: true,
                    hide: 'fold',
                    width: '80%',
                    height: 500,
                    close: function(event, ui){
                        _el.remove();
                    }
                });
                $('#ajax_preview').append( res );
            }

        </script>
{/block}
