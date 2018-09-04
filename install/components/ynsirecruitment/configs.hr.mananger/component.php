<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if (!CModule::IncludeModule('bizproc'))
    return false;
CJSCore::Init(array("jquery"));

$arResult["CONFIGS"]['HRM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_hr_manager_config'));
$arResult["CONFIGS"]['RM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_recruitment_manager_config'));

$res = \CUser::GetList(
    ($by = 'ID'),
    ($order = 'desc'),
    array(
        'ID' => implode(' | ', $arResult["CONFIGS"]['HRM']),
        'ACTIVE' => 'Y'
    )
);
$arResult['HR_MANAGER'] = array();
while ($row = $res->fetch()) {
    $index = array_search(intval($row['ID']),$arResult["CONFIGS"]['HRM']);
    $arResult['HR_MANAGER'][$index] = array(
        'ID' => $row['ID'],
        'NAME' => $row['NAME'],
        'LAST_NAME' => $row['LAST_NAME'],
        'SECOND_NAME' => NULL,
        'LOGIN' => $row['LOGIN'],
        'WORK_POSITION' => $row['WORK_POSITION'],
        'PERSONAL_PHOTO' => $row['PERSONAL_PHOTO'],
        'PERSONAL_GENDER' => $row['PERSONAL_GENDER'],
        'IS_EXTRANET_USER' => false,
        'IS_EMAIL_USER' => false,
        'IS_NETWORK_USER' => false,
    );
}
ksort($arResult['HR_MANAGER']);

$res = \CUser::GetList(
    ($by = 'ID'),
    ($order = 'desc'),
    array(
        'ID' => implode(' | ', $arResult["CONFIGS"]['RM']),
        'ACTIVE' => 'Y'
    )
);
$arResult['RECRUITMENT_MANAGER'] = array();
while ($row = $res->fetch()) {
    $index = array_search(intval($row['ID']),$arResult["CONFIGS"]['RM']);
    $arResult['RECRUITMENT_MANAGER'][$index] = array(
        'ID' => $row['ID'],
        'NAME' => $row['NAME'],
        'LAST_NAME' => $row['LAST_NAME'],
        'SECOND_NAME' => NULL,
        'LOGIN' => $row['LOGIN'],
        'WORK_POSITION' => $row['WORK_POSITION'],
        'PERSONAL_PHOTO' => $row['PERSONAL_PHOTO'],
        'PERSONAL_GENDER' => $row['PERSONAL_GENDER'],
        'IS_EXTRANET_USER' => false,
        'IS_EMAIL_USER' => false,
        'IS_NETWORK_USER' => false,
    );
}
ksort($arResult['RECRUITMENT_MANAGER']);

$this->IncludeComponentTemplate();
