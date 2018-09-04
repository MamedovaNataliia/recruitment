<?php
$module_id = 'ynsirecruitment';
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
CJSCore::Init(array("jquery"));
CModule::IncludeModule($module_id);
CModule::IncludeModule("socialnetwork");
$aTabs = array(
    array("DIV" => "ynsirecruitment_general", "TAB" => GetMessage("YNSIR_OPTION_TAB_GENERAL"), "TITLE" => GetMessage("YNSIR_OPTION_TAB_GENERAL_TITLE"))
);

$arWorkGroup = array();
$dbGroups = CSocnetGroup::GetList(
    array("ID" => "ASC"),
    array(
        //"CHECK_PERMISSIONS" => $USER->GetID()
    ),
    false,
    false,
    array("ID", "NAME")
);
while ($arGroup = $dbGroups->GetNext())
    $arWorkGroup[$arGroup["ID"]] = $arGroup["NAME"];

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["Apply"]) && !isset($_REQUEST['ajax']))
{
    $iWGSelected = intval($_POST['WORKGROUP_UPLOAD']);
    if(isset($arWorkGroup[$iWGSelected])){
        COption::SetOptionInt($module_id, YNSIR_OPTION_GROUP_DISK, $iWGSelected);
        $_SESSION['CONFIG_SUCCESS'] = 1;
        LocalRedirect("/bitrix/admin/settings.php?lang=en&mid=" . $module_id);
    }
}
$iWorkGroup = COption::GetOptionInt($module_id, YNSIR_OPTION_GROUP_DISK);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(isset($_SESSION['CONFIG_SUCCESS']) && $_SESSION['CONFIG_SUCCESS'] == 1){
    CJSCore::Init(array("jquery"));
    unset($_SESSION['CONFIG_SUCCESS']);
    ?>
    <div class="ynsirecruitment-setting-sucess">
        <?=GetMessage('YNSIR_OPTION_SAVE_SUCCESS')?>
    </div>
    <script type="text/javascript">
        setTimeout(function(){
            $('.ynsirecruitment-setting-sucess').hide('slow');
        }, 1000);
    </script>
    <?php
}

$tabControl->Begin();
?>
<style type="text/css">
.ynsirecruitment-title {
    width: 20%;
}
.ynsirecruitment-require {
    font-weight: bold;
}
.ynsirecruitment-setting-sucess{
    color: blue;
    font-weight: bold;
    margin-bottom: 10px;
    background: -webkit-linear-gradient(top, rgba(244,233,141,.3), rgba(232,209,62,.3), rgba(225,194,40,.3));
    padding: 15px 30px 15px 18px;
    border: 1px solid;
    border-color: #d3c6a3 #cabc90 #c1b37f #c9bc8f;
}
</style>
<form method="POST" name="submit">
    <?php $tabControl->BeginNextTab();?>
    <tr>
        <td class="ynsirecruitment-title ynsirecruitment-require">
            <?=GetMessage("YNSIR_OPTION_WORKGROUP_LABEL")?>:
        </td>
        <td class="ynsirecruitment-config">
            <select name="WORKGROUP_UPLOAD">
                <?php
                foreach ($arWorkGroup as $iKeyWG => $sNameWG){
                    $sSelected = $iWorkGroup == $iKeyWG ? 'selected' : '';
                    ?>
                    <option value="<?=$iKeyWG?>" <?=$sSelected?>><?=$sNameWG?></option>
                    <?php
                }
                ?>
            </select>
        </td>
    </tr>
    <?$tabControl->Buttons();?>
    <input type="submit" name="Apply" value="<?=GetMessage("YNSIR_OPTION_SAVE_BUTTON_LABEL")?>">
    <?$tabControl->End();?>
</form>