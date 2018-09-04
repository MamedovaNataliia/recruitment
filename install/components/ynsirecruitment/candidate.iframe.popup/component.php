<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if($this->__templateName != 'legacy')
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest();
	$arResult['IFRAME'] = $request['IFRAME'] == 'Y';

	$iFrameType = '';
	if($request['IFRAME_TYPE'])
	{
		$iFrameType = $request['IFRAME_TYPE'];
	}

	$arResult['IFRAME_TYPE'] = $iFrameType;

	$arResult['INITIALIZED'] = $initialized;
}

$this->IncludeComponentTemplate();
