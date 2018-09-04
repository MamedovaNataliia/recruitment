<?php
class YNSIRJobOrderEdit{

    public static function getDepartment(){
        $arResult = array();
        $iIblockID = COption::GetOptionInt('intranet', 'iblock_structure');
        $dbDRes = CIBlockSection::GetList(
            array('left_margin' => asc),
            array('IBLOCK_ID' => $iIblockID, 'GLOBAL_ACTIVE' => 'Y')
        );
        while ($arDRes = $dbDRes->Fetch()) {
            $arResult[intval($arDRes['ID'])] = $arDRes;
        }
        return $arResult;
    }

    public static function getUserIno($arIdUser = array()){
        $arResult = array();
        $arFilter = !empty($arIdUser) ? array('ID' => implode(' | ', $arIdUser)) : array();
        $rsUsers = CUser::GetList(($by = "personal_country"), ($order = "desc"), $arFilter);
        $sFormatFullName = CSite::GetNameFormat(false);
        $objFile = new CFile();
        while($arUser = $rsUsers->Fetch()){
            $arUser['FULL_NAME'] = CUser::FormatName($sFormatFullName, $arUser);
            $arFileInfo = $objFile->ResizeImageGet(
                $arUser['PERSONAL_PHOTO'],
                array('width' => 34, 'height' => 34),
                BX_RESIZE_IMAGE_EXACT
            );
            $arUser['PHOTO_SRC'] = $arFileInfo['src'];
            $arResult[intval($arUser['ID'])] = $arUser;
        }
        return $arResult;
    }

    public static function htmlExtendsDataItem($arData = array(), $sName = '', $sDafaultValue1 = '', $sDafaultValue2 = ''){
        $sResult = "";
        if(!empty($arData) && $arData['ADDITIONAL_INFO'] > 0){
            $sName = strlen($sName) <= 0 ? $arData['ENTITY'] : $sName;
            $sResult .= '<div class="extends-data-item" id="extends-data-item-' . $arData['ID']
                .'" hidden><span class="extends-data-item-label">' . $arData['ADDITIONAL_INFO_LABEL_'.strtoupper(LANGUAGE_ID)]
                .': </span><span class="extends-data-item-field">';
            $sNameHtml = $sName . '_' . $arData['ID'];
            $sIdHtml = 'ex-item-field-'.$arData['ID'];
            switch ($arData['ADDITIONAL_INFO']){
                case YNSIRConfig::YNSIR_TYPE_LIST_DATE:
                    $sResult .= '<input style="width: 50%" type="text" class="ex-item-field-input-date" id="'.$sIdHtml.'"'
                        . 'name="'.$sNameHtml.'" value="'.$sDafaultValue1.'" onkeyup="">'
                        . '<span class="ex-item-field-date-btn" id="ex_item_field_date_'.$arData['ID'].'"></span>'
                        . '<script>BX.ready(function () {'
                        . 'BX.YSIRDateLinkField.create(BX("ex-item-field-'.$arData['ID'].'"), BX("ex_item_field_date_'.$arData['ID'].'"), {'
                        . 'showTime: false, setFocusOnShow: false});});</script>';
                    break;
                case YNSIRConfig::YNSIR_TYPE_LIST_NUMBER:
                    $sResult .= '<input type="text" class="ex-item-field-input-number" id="'.$sIdHtml
                        .'" name="'.$sNameHtml.'" value="'.$sDafaultValue1.'">';
                    break;
                case YNSIRConfig::YNSIR_TYPE_LIST_USER:
                    ob_start();
                    CCrmViewHelper::RenderUserCustomSearch(
                        array(
                            'ID' => $sIdHtml.'_id',
                            'SEARCH_INPUT_ID' => $sIdHtml.'_search_id',
                            'DATA_INPUT_ID' => $sIdHtml,
                            'COMPONENT_NAME' => $sNameHtml,
                            'NAME_FORMAT' => CSite::GetNameFormat(false),
                            'USER' => array(
                                'ID' => $sDafaultValue1,
                                'NAME' => $sDafaultValue2
                            )
                        )
                    );
                    $userSelectorHtml = ob_get_contents();
                    ob_end_clean();
                    $sResult .= $userSelectorHtml;
                    break;
                case YNSIRConfig::YNSIR_TYPE_LIST_STRING:
                    $sResult .= '<input type="text" class="ex-item-field-input-string" id="'.$sIdHtml
                        .'" name="'.$sNameHtml.'" value="'.$sDafaultValue1.'">';
                    break;
                default:
                    break;
            }
            $sResult .= '</span></div>';
        }
        return $sResult;
    }
}
?>