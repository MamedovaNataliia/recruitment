<?php
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ynsirecruitment/public/ynsirecruitment/.top.menu_ext.php");
if (CModule::IncludeModule("ynsirecruitment")) {
    $arMenuB24[] = Array(
        GetMessage('LEFT_MENU_TITLE_YNSIR'),
        SITE_DIR."recruitment/menu/",
        array(SITE_DIR."recruitment/"),
        Array(
            "real_link" => getLeftMenuItemLink(
                "ynsirecruitment_panel_menu",
                SITE_DIR."recruitment/candidate/list/"
            ),
            "menu_item_id"=>"menu_ynsirecruitment",
            "top_menu_id" => "ynsirecruitment_panel_menu"),
        true,
    );
}
?>