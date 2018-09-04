<?php

class YNSIRTaskEvent
{
    public function changeContent(&$content){
        if (isset($_GET['UF_YNSIR_TASK'])) {
            $dbResult = YNSIRCandidate::GetListCandidate(
                $arSort,
                array('ID' => $_GET['UF_YNSIR_TASK']),
                '',
                array(), array());
            $rs = array();
            $sFormatName = CSite::GetNameFormat(false);
            while ($arProfile = $dbResult->GetNext()) {
                $arProfile['FULL_NAME'] = CUser::FormatName(
                    $sFormatName,
                    array(
                        "NAME" => $arProfile['FIRST_NAME'],
                        "LAST_NAME" => $arProfile['LAST_NAME'],
                    )
                );
                $rs = $arProfile;

            }
            $lable = GetMessage('YNSIR_TASK_REF');
            $sAdd = <<<HTML
            <script>
                BX.ready(function() {
                    BX.addCustomEvent(window, 'OnCreateIframeAfter', function(editor){
                        document.getElementsByClassName('bx-editor-iframe')[0].contentDocument.body.innerHTML = '{$lable}: <a href="/recruitment/candidate/detail/{$_GET['UF_YNSIR_TASK']}/">{$rs['FULL_NAME']}</a>';
                    });
                });
        </script>
HTML;
            $content = str_replace('</body>', $sAdd . '</body>', $content);
        }
    }
    public function GetRefCandidateHTML($arField) {

        if($arField['OWNER_TYPE_ID'] == YNSIROwnerType::Candidate && $arField['OWNER_ID'] > 0) {
            $dbResult = YNSIRCandidate::GetListCandidate(
                array(),
                array('ID' => $arField['OWNER_ID'],'CHECK_PERMISSIONS' => 'N'),
                '',
                array(), array());
            $rs = array();
            $sFormatName = CSite::GetNameFormat(false);
            while ($arProfile = $dbResult->GetNext()) {
                $arProfile['FULL_NAME'] = CUser::FormatName(
                    $sFormatName,
                    array(
                        "NAME" => $arProfile['FIRST_NAME'],
                        "LAST_NAME" => $arProfile['LAST_NAME'],
                    )
                );
                $rs = $arProfile;

            }
            $lable = GetMessage('YNSIR_TASK_REF');
            $lable_feedback = GetMessage('YNSIR_TASK_FEEDBACK');
            if($arField['REFERENCE_TYPE_ID'] == YNSIROwnerType::Order && intval($arField['REFERENCE_ID']) > 0
                && intval($arField['ROUND_ID'] > 0)) {
                $feedBack = $lable_feedback.': [URL=/recruitment/candidate/detail/'.$arField['OWNER_ID'].'/?candidate_id='.$arField['OWNER_ID'].'&job_order_id='.$arField['REFERENCE_ID'].'&round_id='.$arField['ROUND_ID'].']'.$rs['FULL_NAME'].'[/URL]';
            }
            $htmlReturn = <<<HTML
{$lable}: [URL=/recruitment/candidate/detail/{$arField['OWNER_ID']}/]{$rs['FULL_NAME']}[/URL]
{$feedBack}
HTML;
            return $htmlReturn;
        } else {
            return '';
        }
    }
    public function pageStartForTask(){
        if (isset($_GET['UF_YNSIR_TASK'])) {
            CJSCore::Init(array("jquery"));
        }
    }
}