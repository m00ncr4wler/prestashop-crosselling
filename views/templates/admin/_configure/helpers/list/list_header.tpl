{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if $init}
<script type="text/javascript">
    $(function () {
        var crossSellingTableDnD = 'table.crosssellingDnD';
        initCrosssellingDnD(crossSellingTableDnD);

        $(".ajax_delete_link").on('click', function () {
            var link = $(this);
            $.post($(this).attr('href'), function (data) {
                if (data.success == 1) {
                    showSuccessMessage(data.text);
                    link.parent().parent().parent().remove();
                    sortPositions(crossSellingTableDnD);
                } else {
                    showErrorMessage(data.text);
                }
            }, 'json');
            return false;
        });
    });

    function initCrosssellingDnD(table)
    {
        $(table).tableDnD({
            onDragStart: function(table, row) {
                originalOrder = $.tableDnD.serialize();
                reOrder = ':even';
                if (table.tBodies[0].rows[1] && $('#' + table.tBodies[0].rows[1].id).hasClass('alt_row'))
                    reOrder = ':odd';
                $(table).find('#' + row.id).parent('tr').addClass('myDragClass');
            },
            dragHandle: 'dragHandle',
            onDragClass: 'myDragClass',
            onDrop: function(table, row) {
                if (originalOrder != $.tableDnD.serialize()) {
                    var way = (originalOrder.indexOf(row.id) < $.tableDnD.serialize().indexOf(row.id))? 1 : 0;
                    var ids = row.id.split('_');
                    var params = {
                        action : 'updatePositions',
                        id : ids[2],
                        way: way,
                        ajax: 1
                    };

                    $.ajax({
                        type: 'POST',
                        headers: { "cache-control": "no-cache" },
                        async: false,
                        url: '{$currentIndex}&token={$token}&' + 'rand=' + new Date().getTime(),
                        data: $.tableDnD.serialize() + '&' + objToString(params) ,
                        success: function(data) {
                            sortPositions(table);
                            showSuccessMessage(update_success_msg);
                        }
                    });
                }
            }
        });
    }

    function sortPositions(table) {
        var nodrag_lines = $(table).find('tr:not(".nodrag")');
        var reg = /_[0-9][0-9]*$/g;
        var up_reg  = new RegExp('position=[-]?[0-9]+&');
        nodrag_lines.each(function(i) {
            $(this).attr('id', $(this).attr('id').replace(reg, '_' + i));
            $(this).find('.positions').text(i);
        });

        nodrag_lines.removeClass('odd');
        nodrag_lines.filter(':odd').addClass('odd');
        nodrag_lines.children('td.dragHandle').find('a').attr('disabled',false);

        if (typeof alternate !== 'undefined' && alternate) {
            nodrag_lines.children('td.dragHandle:first').find('a:odd').attr('disabled',true);
            nodrag_lines.children('td.dragHandle:last').find('a:even').attr('disabled',true);
        }
        else {
            nodrag_lines.children('td.dragHandle:first').find('a:even').attr('disabled',true);
            nodrag_lines.children('td.dragHandle:last').find('a:odd').attr('disabled',true);
        }
    }
</script>
{/if}

{if !$simple_header}
	{* Display column names and arrows for ordering (ASC, DESC) *}
	{if $is_order_position}
		<script type="text/javascript" src="../js/jquery/plugins/jquery.tablednd.js"></script>
		<script type="text/javascript">
			var come_from = '{$list_id|addslashes}';
			var alternate = {if $order_way == 'DESC'}'1'{else}'0'{/if};
		</script>
	{/if}
	<script type="text/javascript">
		$(function() {
			$('table.{$list_id} .filter').keypress(function(e){
				var key = (e.keyCode ? e.keyCode : e.which);
				if (key == 13)
				{
					e.preventDefault();
					formSubmit(event, 'submitFilterButton{$list_id}');
				}
			})
			$('#submitFilterButton{$list_id}').click(function() {
				$('#submitFilter{$list_id}').val(1);
			});
			if ($("table.{$list_id} .datepicker").length > 0) {
				$("table.{$list_id} .datepicker").datepicker({
					prevText: '',
					nextText: '',
					altFormat: 'yy-mm-dd'
				});
			}
		});
	</script>
{/if}

{if !$simple_header}
	<div class="leadin">
		{block name="leadin"}{/block}
	</div>
{/if}

{block name="override_header"}{/block}

{hook h='displayAdminListBefore'}

{if isset($name_controller)}
	{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListBefore{/capture}
	{hook h=$hookName}
{elseif isset($smarty.get.controller)}
	{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListBefore{/capture}
	{hook h=$hookName}
{/if}

<div class="alert alert-warning" id="{$list_id}-empty-filters-alert" style="display:none;">{l s='Please fill at least one field to perform a search in this list.'}</div>

{block name="startForm"}
	<form method="post" action="{$action}" class="form-horizontal clearfix" id="{$list_id}">
{/block}

{if !$simple_header}
	<input type="hidden" id="submitFilter{$list_id}" name="submitFilter{$list_id}" value="0"/>
	{block name="override_form_extra"}{/block}
	<div class="panel col-lg-12">
		<div class="panel-heading">
			{if isset($icon)}<i class="{$icon}"></i> {/if}{if is_array($title)}{$title|end}{else}{$title}{/if}
			{if isset($toolbar_btn) && count($toolbar_btn) >0}
				<span class="badge">{$list_total}</span>
				<span class="panel-heading-action">
				{foreach from=$toolbar_btn item=btn key=k}
					{if $k != 'modules-list' && $k != 'back'}
						<a id="desc-{$table}-{if isset($btn.imgclass)}{$btn.imgclass}{else}{$k}{/if}" class="list-toolbar-btn" {if isset($btn.href)}href="{$btn.href}"{/if} {if isset($btn.target) && $btn.target}target="_blank"{/if}{if isset($btn.js) && $btn.js}onclick="{$btn.js}"{/if}>
							<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s=$btn.desc}" data-html="true" data-placement="left">
								<i class="process-icon-{if isset($btn.imgclass)}{$btn.imgclass}{else}{$k}{/if} {if isset($btn.class)}{$btn.class}{/if}" ></i>
							</span>
						</a>
					{/if}
				{/foreach}
					<a id="desc-{$table}-refresh" class="list-toolbar-btn" href="javascript:location.reload();">
						<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Refresh list'}" data-html="true" data-placement="left">
							<i class="process-icon-refresh" ></i>
						</span>
					</a>
				</span>
			{/if}
		</div>
		{if $show_toolbar}
			<script language="javascript" type="text/javascript">
				//<![CDATA[
				var submited = false;
				$(function() {
					//get reference on save link
					btn_save = $('i[class~="process-icon-save"]').parent();
					//get reference on form submit button
					btn_submit = $('#{$table}_form_submit_btn');
					if (btn_save.length > 0 && btn_submit.length > 0) {
						//get reference on save and stay link
						btn_save_and_stay = $('i[class~="process-icon-save-and-stay"]').parent();
						//get reference on current save link label
						lbl_save = $('#desc-{$table}-save div');
						//override save link label with submit button value
						if (btn_submit.val().length > 0) {
							lbl_save.html(btn_submit.attr("value"));
						}
						if (btn_save_and_stay.length > 0) {
							//get reference on current save link label
							lbl_save_and_stay = $('#desc-{$table}-save-and-stay div');
							//override save and stay link label with submit button value
							if (btn_submit.val().length > 0 && lbl_save_and_stay && !lbl_save_and_stay.hasClass('locked')) {
								lbl_save_and_stay.html(btn_submit.val() + " {l s='and stay'} ");
							}
						}
						//hide standard submit button
						btn_submit.hide();
						//bind enter key press to validate form
						$('#{$table}_form').keypress(function (e) {
							if (e.which == 13 && e.target.localName != 'textarea') {
								$('#desc-{$table}-save').click();
							}
						});
						//submit the form
						{block name=formSubmit}
							btn_save.click(function() {
								// Avoid double click
								if (submited) {
									return false;
								}
								submited = true;
								//add hidden input to emulate submit button click when posting the form -> field name posted
								btn_submit.before('<input type="hidden" name="'+btn_submit.attr("name")+'" value="1" />');
								$('#{$table}_form').submit();
								return false;
							});
							if (btn_save_and_stay) {
								btn_save_and_stay.click(function() {
									//add hidden input to emulate submit button click when posting the form -> field name posted
									btn_submit.before('<input type="hidden" name="'+btn_submit.attr("name")+'AndStay" value="1" />');
									$('#{$table}_form').submit();
									return false;
								});
							}
						{/block}
					}
				});
				//]]>
			</script>
		{/if}
{elseif $simple_header}
	<div class="panel col-lg-12">
		{if isset($title)}<h3>{if isset($icon)}<i class="{$icon}"></i> {/if}{if is_array($title)}{$title|end}{else}{$title}{/if}</h3>{/if}
{/if}
	{block name="preTable"}{/block}
	<div class="table-responsive clearfix{if isset($use_overflow) && $use_overflow} overflow-y{/if}">
		<table {if $table_id} id={$table_id}{/if} class="table {if $table_dnd}crosssellingDnD{/if} {$table}" >
			<thead>
				<tr class="nodrag nodrop">
					{if $bulk_actions && $has_bulk_actions}
						<th class="center fixed-width-xs"></th>
					{/if}
					{foreach $fields_display AS $key => $params}
					<th class="{if isset($params.class)}{$params.class}{/if}{if isset($params.align)} {$params.align}{/if}">
						<span class="title_box {if isset($order_by) && ($key == $order_by)} active{/if}">
							{if isset($params.hint)}
								<span class="label-tooltip" data-toggle="tooltip"
									title="
										{if is_array($params.hint)}
											{foreach $params.hint as $hint}
												{if is_array($hint)}
													{$hint.text}
												{else}
													{$hint}
												{/if}
											{/foreach}
										{else}
											{$params.hint}
										{/if}
									">
									{$params.title}
								</span>
							{else}
								{$params.title}
							{/if}

							{if (!isset($params.orderby) || $params.orderby) && !$simple_header && $show_filters}
								<a {if isset($order_by) && ($key == $order_by) && ($order_way == 'DESC')}class="active"{/if}  href="{$currentIndex}&amp;{$list_id}Orderby={$key|urlencode}&amp;{$list_id}Orderway=desc&amp;token={$token}{if isset($smarty.get.$identifier)}&{$identifier}={$smarty.get.$identifier|intval}{/if}">
									<i class="icon-caret-down"></i>
								</a>
								<a {if isset($order_by) && ($key == $order_by) && ($order_way == 'ASC')}class="active"{/if} href="{$currentIndex}&amp;{$list_id}Orderby={$key|urlencode}&amp;{$list_id}Orderway=asc&amp;token={$token}{if isset($smarty.get.$identifier)}&{$identifier}={$smarty.get.$identifier|intval}{/if}">
									<i class="icon-caret-up"></i>
								</a>
							{/if}
						</span>
					</th>
					{/foreach}
					{if $shop_link_type}
						<th>
							<span class="title_box">
							{if $shop_link_type == 'shop'}
								{l s='Shop'}
							{else}
								{l s='Group shop'}
							{/if}
							</span>
						</th>
					{/if}
					{if $has_actions || $show_filters}
						<th>{if !$simple_header}{/if}</th>
					{/if}
				</tr>
			{if !$simple_header && $show_filters}
				<tr class="nodrag nodrop filter {if $row_hover}row_hover{/if}">
					{if $has_bulk_actions}
						<th class="text-center">
							--
						</th>
					{/if}
					{* Filters (input, select, date or bool) *}
					{foreach $fields_display AS $key => $params}
						<th {if isset($params.align)} class="{$params.align}" {/if}>
							{if isset($params.search) && !$params.search}
								--
							{else}
								{if $params.type == 'bool'}
									<select class="filter fixed-width-sm" name="{$list_id}Filter_{$key}">
										<option value="">-</option>
										<option value="1" {if $params.value == 1} selected="selected" {/if}>{l s='Yes'}</option>
										<option value="0" {if $params.value == 0 && $params.value != ''} selected="selected" {/if}>{l s='No'}</option>
									</select>
								{elseif $params.type == 'date' || $params.type == 'datetime'}
									<div class="date_range row">
 										<div class="input-group fixed-width-md">
											<input type="text" class="filter datepicker date-input form-control" id="local_{$params.id_date}_0" name="local_{$params.name_date}[0]"  placeholder="{l s='From'}" />
											<input type="hidden" id="{$params.id_date}_0" name="{$params.name_date}[0]" value="{if isset($params.value.0)}{$params.value.0}{/if}">
											<span class="input-group-addon">
												<i class="icon-calendar"></i>
											</span>
										</div>
 										<div class="input-group fixed-width-md">
											<input type="text" class="filter datepicker date-input form-control" id="local_{$params.id_date}_1" name="local_{$params.name_date}[1]"  placeholder="{l s='To'}" />
											<input type="hidden" id="{$params.id_date}_1" name="{$params.name_date}[1]" value="{if isset($params.value.1)}{$params.value.1}{/if}">
											<span class="input-group-addon">
												<i class="icon-calendar"></i>
											</span>
										</div>
										<script>
											function parseDate(date){
												return $.datepicker.parseDate("yy-mm-dd", date);
											}
											$(function() {
												var dateStart = parseDate($("#{$params.id_date}_0").val());
												var dateEnd = parseDate($("#{$params.id_date}_1").val());
												$("#local_{$params.id_date}_0").datepicker("option", "altField", "#{$params.id_date}_0");
												$("#local_{$params.id_date}_1").datepicker("option", "altField", "#{$params.id_date}_1");
												if (dateStart !== null){
													$("#local_{$params.id_date}_0").datepicker("setDate", dateStart);
												}
												if (dateEnd !== null){
													$("#local_{$params.id_date}_1").datepicker("setDate", dateEnd);
												}
											});
										</script>
									</div>
								{elseif $params.type == 'select'}
									{if isset($params.filter_key)}
										<select class="filter" onchange="$('#submitFilterButton{$list_id}').focus();$('#submitFilterButton{$list_id}').click();" name="{$list_id}Filter_{$params.filter_key}" {if isset($params.width)} style="width:{$params.width}px"{/if}>
											<option value="" {if $params.value == ''} selected="selected" {/if}>-</option>
											{if isset($params.list) && is_array($params.list)}
												{foreach $params.list AS $option_value => $option_display}
													<option value="{$option_value}" {if (string)$option_display === (string)$params.value ||  (string)$option_value === (string)$params.value} selected="selected"{/if}>{$option_display}</option>
												{/foreach}
											{/if}
										</select>
									{/if}
								{else}
									<input type="text" class="filter" name="{$list_id}Filter_{if isset($params.filter_key)}{$params.filter_key}{else}{$key}{/if}" value="{$params.value|escape:'html':'UTF-8'}" {if isset($params.width) && $params.width != 'auto'} style="width:{$params.width}px"{/if} />
								{/if}
							{/if}
						</th>
					{/foreach}

					{if $shop_link_type}
						<th>--</th>
					{/if}
					{if $has_actions || $show_filters}
						<th class="actions">
							{if $show_filters}
							<span class="pull-right">
								{*Search must be before reset for default form submit*}
								<button type="submit" id="submitFilterButton{$list_id}" name="submitFilter" class="btn btn-default" data-list-id="{$list_id}">
									<i class="icon-search"></i> {l s='Search'}
								</button>
								{if $filters_has_value}
									<button type="submit" name="submitReset{$list_id}" class="btn btn-warning">
										<i class="icon-eraser"></i> {l s='Reset'}
									</button>
								{/if}
							</span>
							{/if}
						</th>
					{/if}
				</tr>
			{/if}
			</thead>