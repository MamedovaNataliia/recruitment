<?php
/**
 * Company YouNet SI
 */

use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\ObjectTable;

class YNSIRFile
{
    const TABLE_NAME = 'b_ynsir_file';

    public static function resetCFile($iIdCandidate = 0, $sType = YNSIR_FT_RESUME, $arFiles = array(), $bSaveEvent = false, &$arMsg)
    {
        global $DB;
        $iIdCandidate = intval($iIdCandidate);
        $arFileField = YNSIRConfig::getArrayFileFieldSection();
        $sQuery = "DELETE FROM b_ynsir_file WHERE CANDIDATE_ID={$iIdCandidate} AND TYPE='{$sType}'";
        if (!empty($arFiles) && $iIdCandidate > 0) {
            $sQuery .= " AND FILE_ID IN('" . implode("','", $arFiles) . "')";
            $DB->Query($sQuery);
            //TODO: SAVE ADD FILE EVENT
            //MESSAGE EVENT
            if ($bSaveEvent) {
                foreach ($arFiles as $iFileId) {
                    $iId = intval($iFileId);
                    if ($iId > 0) {
                        $file = File::loadById($iId, array('STORAGE'));

                        $arDetailIFile = CFile::GetByID($file->getFileId())->Fetch();

                        $FILE_NAME = $arDetailIFile['ORIGINAL_NAME'];
                        $arMsg[] = Array(
                            'ENTITY_FIELD' => $sType,
                            'EVENT_NAME' => GetMessage('YNSIR_ADDITIONAL_FIELD_COMPARE_REMOVE',
                                array('#FIELD#' => $arFileField[$sType])),
                            'EVENT_TEXT_1' => $FILE_NAME,
                            'EVENT_TEXT_2' => '',
                        );
                    }
                }
            }
            //END
        }
        return 1;
    }

    public static function addCFile($iIdCandidate = 0, $sType = YNSIR_FT_RESUME, $arFiles = array(), $bSaveEvent = false, &$arMsg)
    {
        global $DB, $USER;
        $arFileField = YNSIRConfig::getArrayFileFieldSection();
        $idUser = $USER->GetID();
        $iIdCandidate = intval($iIdCandidate);

        if ($iIdCandidate > 0 && !empty($arFiles)) {
            $sQuery = "";
            foreach ($arFiles as $iFileId) {
                $iId = intval($iFileId);
                if ($iId > 0) {

                    // region move file
                    $iResult = YNSIRDisk::createSubFolder('[' . $iIdCandidate . ']');
                    $idf = YNSIRDisk::getStorageByIdFile($iId);
                    if ($idf != $iResult) {
                        YNSIRDisk::moveFile(array($iId), $iResult);
                    }
                    //endregion
                    $file = File::loadById($iId, array('STORAGE'));
                     print_r($file);
                    $arDetailIFile = CFile::GetByID($file->getFileId())->Fetch();

                    $pathinfo = pathinfo($arDetailIFile['ORIGINAL_NAME']);
                    $file = $_SERVER["DOCUMENT_ROOT"] . '/upload/' . $arDetailIFile['SUBDIR'] . '/' . $arDetailIFile['FILE_NAME'];
                    $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.' . $pathinfo['extension'];
                    if(!file_exists($newfile)){
                        if (!copy($file, $newfile)) {
                            //TO SHOW ERROR!!
                        }
                    }
                    if ($pathinfo['extension'] == 'docx') {
                        $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.' . $pathinfo['extension'];
                        $content_file = YNSIRHelper::read_file_docx($newfile);
                    }
                    if ($pathinfo['extension'] == 'doc') {
                        $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.html';
                        $content_file = YNSIRHelper::html2text($newfile);

                    }
                    if ($pathinfo['extension'] == 'pdf') {
                        $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.' . $pathinfo['extension'];
                        $content_file = YNSIRHelper::read_file_pdf($newfile);
                    }
                    $content_file = '"' . htmlspecialcharsbx($content_file) . '"';
                    $sQuery .= "(" . $iIdCandidate . ", '" . $sType . "', " . $iId . ", " . $idUser . ", " . $content_file . "),";

                    //TODO: ADD MESSAGE EVENT
                    $arFile_event = CFile::makeFileArray($arDetailIFile['ID']);
                    if ($bSaveEvent) {
                        $arMsg[] = Array(
                            'ENTITY_FIELD' => $sType,
                            'EVENT_NAME' => GetMessage('YNSIR_ADDITIONAL_FIELD_COMPARE_ADD',
                                array('#FIELD#' => $arFileField[$sType])),
                            'EVENT_TEXT_1' => '',//$arDetailIFile['ORIGINAL_NAME'],
                            'EVENT_TEXT_2' => '',
                            'FILES' => array($arFile_event)
                        );
                    }
                    unset($arFile_event);
                    //END

                }
            }
            if (strlen($sQuery) > 0) {
                $sQuery = rtrim($sQuery, ",");
                $sQuery = "INSERT INTO " . static::TABLE_NAME . " (CANDIDATE_ID, TYPE, FILE_ID, USER_UPLOAD,FILE_CONTENT) VALUES " . $sQuery;
                $DB->Query($sQuery);
            }
        }
        return 0;
    }

    public static function getListById($iIdCandidate = 0)
    {
        global $DB;
        $arResult = array();
        $iIdCandidate = intval($iIdCandidate);
        if ($iIdCandidate > 0) {
            $res = $DB->Query("SELECT * FROM " . static::TABLE_NAME . " WHERE CANDIDATE_ID=" . $iIdCandidate);
            while ($arItem = $res->Fetch()) {
                $arResult[$arItem['TYPE']][$arItem['FILE_ID']] = $arItem;
            }
        }
        return $arResult;
    }

    public static function compareAndAdd($iIdCandidate = 0, $arFiles = array(), $bSaveEvent = false)
    {
        $arFileUploaded = YNSIRFile::getListById($iIdCandidate);

        $arMsg = array();
        $idUser = YNSIRSecurityHelper::GetCurrentUserID();

        // RESUME - FILE_RESUME - YNSIR_FT_RESUME
        $arFileR = array_keys($arFileUploaded[YNSIR_FT_RESUME]);
        if (empty($arFileR)) {
            static::addCFile($iIdCandidate, YNSIR_FT_RESUME, $arFiles['FILE_RESUME'], $bSaveEvent, $arMsg);
        } else if (!empty($arFiles['FILE_RESUME'])) {
            $arIR = array_intersect($arFiles['FILE_RESUME'], $arFileR);
            $arDR = array_diff($arFileR, $arIR);
            $arAR = array_diff($arFiles['FILE_RESUME'], $arFileR);
            static::resetCFile($iIdCandidate, YNSIR_FT_RESUME, $arDR, $bSaveEvent, $arMsg);
            static::addCFile($iIdCandidate, YNSIR_FT_RESUME, $arAR, $bSaveEvent, $arMsg);
        } else {
            static::resetCFile($iIdCandidate, YNSIR_FT_RESUME, $arFileR, $bSaveEvent, $arMsg);
        }
        // FORMATTED_RESUME - FILE_FORMATTED_RESUME - YNSIR_FT_FORMATTED_RESUME
        $arFileFR = array_keys($arFileUploaded[YNSIR_FT_FORMATTED_RESUME]);
        if (empty($arFileFR)) {
            static::addCFile($iIdCandidate, YNSIR_FT_FORMATTED_RESUME, $arFiles['FILE_FORMATTED_RESUME'], $bSaveEvent, $arMsg);
        } else if (!empty($arFiles['FILE_FORMATTED_RESUME'])) {
            $arIFR = array_intersect($arFiles['FILE_FORMATTED_RESUME'], $arFileFR);
            $arDFR = array_diff($arFileFR, $arIFR);
            $arAFR = array_diff($arFiles['FILE_FORMATTED_RESUME'], $arFileFR);
            static::resetCFile($iIdCandidate, YNSIR_FT_FORMATTED_RESUME, $arDFR, $bSaveEvent, $arMsg);
            static::addCFile($iIdCandidate, YNSIR_FT_FORMATTED_RESUME, $arAFR, $bSaveEvent, $arMsg);
        } else {
            static::resetCFile($iIdCandidate, YNSIR_FT_FORMATTED_RESUME, $arFileFR);
        }
        // COVER_LETTER - FILE_COVER_LETTER - YNSIR_FT_COVER_LETTER
        $arFileCL = array_keys($arFileUploaded[YNSIR_FT_COVER_LETTER]);
        if (empty($arFileCL)) {
            static::addCFile($iIdCandidate, YNSIR_FT_COVER_LETTER, $arFiles['FILE_COVER_LETTER'], $bSaveEvent, $arMsg);
        } else if (!empty($arFiles['FILE_COVER_LETTER'])) {
            $arICL = array_intersect($arFiles['FILE_COVER_LETTER'], $arFileCL);
            $arDCL = array_diff($arFileCL, $arICL);
            $arACL = array_diff($arFiles['FILE_COVER_LETTER'], $arFileCL);
            static::resetCFile($iIdCandidate, YNSIR_FT_COVER_LETTER, $arDCL, $bSaveEvent, $arMsg);
            static::addCFile($iIdCandidate, YNSIR_FT_COVER_LETTER, $arACL, $bSaveEvent, $arMsg);
        } else {
            static::resetCFile($iIdCandidate, YNSIR_FT_COVER_LETTER, $arFileCL, $bSaveEvent, $arMsg);
        }
        // OTHERS - FILE_OTHERS - YNSIR_FT_OTHERS
        $arFileO = array_keys($arFileUploaded[YNSIR_FT_OTHERS]);
        if (empty($arFileO)) {
            static::addCFile($iIdCandidate, YNSIR_FT_OTHERS, $arFiles['FILE_OTHERS']);
        } else if (!empty($arFiles['FILE_OTHERS'])) {
            $arIO = array_intersect($arFiles['FILE_OTHERS'], $arFileO);
            $arDO = array_diff($arFileO, $arIO);
            $arAO = array_diff($arFiles['FILE_OTHERS'], $arFileO);
            static::resetCFile($iIdCandidate, YNSIR_FT_OTHERS, $arDO, $bSaveEvent, $arMsg);
            static::addCFile($iIdCandidate, YNSIR_FT_OTHERS, $arAO, $bSaveEvent, $arMsg);
        } else {
            static::resetCFile($iIdCandidate, YNSIR_FT_OTHERS, $arFileO, $bSaveEvent, $arMsg);
        }
        if ($bSaveEvent) {
            foreach ($arMsg as $arEvent) {
                $arEvent['ENTITY_TYPE'] = 'CANDIDATE';
                $arEvent['ENTITY_ID'] = $iIdCandidate;
                $arEvent['EVENT_TYPE'] = 1;

                if (!isset($arEvent['USER_ID'])) {
                    if ($idUser > 0) {
                        $arEvent['USER_ID'] = $idUser;
                    } else if (isset($arFields['MODIFIED_BY']) && $arFields['MODIFIED_BY'] > 0) {
                        $arEvent['USER_ID'] = $arFields['MODIFIED_BY'];
                    }
                }

                $YNSIREvent = new YNSIREvent();
                $YNSIREvent->Add($arEvent, false);
            }
        }

    }
}

?>