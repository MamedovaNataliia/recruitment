<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->SetTitle($arResult['TYPE_LIST']['NAME']);
ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
?>
<style>
	.feed-add-info-text-null {
	    font-size: 14px;
    	color: #5e6675;
	}
	#pagetitle {
	    text-transform: capitalize;
	}
</style>
<div id="order-permissions" style="margin-top: 7px;">
	<div class="new-order-permissions">
		<div class="feed-add-error feed-add-error-null">
			<span class="feed-add-info-icon"></span>
			<span class="feed-add-info-text-null">
				<?=GetMessage('ORDER_RIGHT_MESS_NEED_PERMS')?>
			</span>
		</div>
	</div>
</div>