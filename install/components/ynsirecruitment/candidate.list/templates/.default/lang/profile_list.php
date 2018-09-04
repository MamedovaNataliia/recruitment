<?php

CModule::IncludeModule('dhcustom') or die;

$APPLICATION->IncludeComponent(
    "donghung:order.right",
    ".default",
    Array(
        'ENTITY_TYPE' => DH_ENTITY_TYPE_STOCK,
        'ENTITY' => GetMessage('MAT_STOCK')
    )
);
?>