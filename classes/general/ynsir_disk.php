<?php

use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\File;

class YNSIRDisk
{
    const TABLE_OBJECT = 'b_disk_object';
    const TABLE_STORAGE = 'b_disk_storage';
    const ENTITY_TYPE_STORAGE = 'Bitrix\\Disk\\ProxyType\\Group';

    const NAME_TEMP_DISK = 'Temporary folder';
    const USER_CREATE_TEMP_DISK = 1;
    const DEFAULT_SHARE_TEMP_DISK = 'disk_access_full';

    const DEFAULT_SHARE_TYPE_USER = 1;
    const DEFAULT_SHARE_TYPE_ALL = 2;

    const PERMS_ACCESS_FULL = 'full';
    const PERMS_ACCESS_EDIT = 'edit';
    const PERMS_ACCESS_ADD = 'add';
    const PERMS_ACCESS_READ = 'read';
    const PERMS_TYPE_USER = 'U';
    const PERMS_TYPE_WORKGROUP = 'SG';
    const PERMS_TYPE_DEPARTMENT = 'DR';

    public static function rootFolder()
    {
        global $DB;
        $iResult = 0;
        $iWorkGroup = COption::GetOptionInt(YNSIR_MODULE_ID, YNSIR_OPTION_GROUP_DISK);
        $sQuery = "SELECT * FROM " . static::TABLE_STORAGE . " WHERE ENTITY_TYPE='" . $DB->ForSql(static::ENTITY_TYPE_STORAGE) . "' AND ENTITY_ID=" . $iWorkGroup;
        $res = $DB->Query($sQuery);
        if ($arData = $res->Fetch()) {
            $iResult = intval($arData['ROOT_OBJECT_ID']);
        }
        return $iResult;
    }

    public static function getTempFolder($iIdRoot = 0)
    {
        global $DB;
        $iIdRoot = intval($iIdRoot) > 0 ? intval($iIdRoot) : static::rootFolder();
        $iResult = COption::GetOptionInt(YNSIR_MODULE_ID, YNSIR_OPTION_FOLDER_TEMP_DISK);
        if ($iIdRoot > 0) {
            $bNewTemp = true;
            if ($iResult > 0) {
                $sQuery = "SELECT * FROM " . static::TABLE_OBJECT . " WHERE DELETED_TYPE = 0 AND ID=" . $iResult . " AND TYPE=" . ObjectTable::TYPE_FOLDER;
                $res = $DB->Query($sQuery);
                if ($arData = $res->Fetch()) {
                    $bNewTemp = false;
                }
            }
            // create new folder tmp
            if ($bNewTemp == true) {
                $folder = Folder::loadById($iIdRoot, array('STORAGE'));
                $arTemp = $folder->addSubFolder(array(
                    'NAME' => static::NAME_TEMP_DISK,
                    'CREATED_BY' => static::USER_CREATE_TEMP_DISK
                ));
                if ($arTemp !== null) {
                    $iResult = intval($arTemp->getId());
                    COption::SetOptionInt(YNSIR_MODULE_ID, YNSIR_OPTION_FOLDER_TEMP_DISK, $iResult);
                } else {
                    $sQuery = "SELECT * FROM " . static::TABLE_OBJECT . " WHERE DELETED_TYPE = 0 AND PARENT_ID = " . $iIdRoot . " AND NAME='" . static::NAME_TEMP_DISK . "'";
                    $res = $DB->Query($sQuery);
                    if ($arData = $res->Fetch()) {
                        $iResult = $arData['ID'];
                    }
                }
            }
        }
        return intval($iResult);
    }

    public static function getStorageByIdFile($iIdFile = 0)
    {
        global $DB;
        $iResult = 0;
        $iIdFile = intval($iIdFile);
        if ($iIdFile > 0) {
            $sQuery = "SELECT * FROM " . static::TABLE_OBJECT . " WHERE ID=" . $iIdFile . " AND TYPE=" . ObjectTable::TYPE_FILE;
            $res = $DB->Query($sQuery);
            if ($arData = $res->Fetch()) {
                $iResult = intval($arData['PARENT_ID']);
            }
        }
        return $iResult;
    }

    public static function moveFile($arIdFiles = array(), $idTarget = 0)
    {
        global $DB;
        $iResult = 1;
        $idTarget = intval($idTarget);

        foreach ($arIdFiles as $k => $id) {
            $file = File::loadById($id, array('STORAGE'));
            if (!$file) continue;

            $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();
            $name = $arFileInfo['ORIGINAL_NAME'];
            $n = 0;
            while ($n < 500) {
                $n++;
                $sQuery = "SELECT * FROM " . static::TABLE_OBJECT . " WHERE NAME=\"" . $name . "\" AND PARENT_ID = " . $idTarget . " AND TYPE=" . ObjectTable::TYPE_FILE;
                $res = $DB->Query($sQuery);
                if ($arData = $res->Fetch()) {
                    $pathinfo = pathinfo($name);
                    $name = $pathinfo['filename'] . '(copy).' . $pathinfo['extension'];
                } else {
                    break;
                }
            }
            $sQuery = "UPDATE " . static::TABLE_OBJECT . " SET NAME = \"" . $name . "\" WHERE ID =" . $id;
            $res = $DB->Query($sQuery);

        }

        if (!empty($arIdFiles) && $idTarget > 0) {
            $sQuery = "SELECT * FROM " . static::TABLE_OBJECT . " WHERE DELETED_TYPE = 0 AND ID=" . $idTarget . " AND TYPE=" . ObjectTable::TYPE_FOLDER;
            $res = $DB->Query($sQuery);
            if ($arData = $res->Fetch()) {
                $sQuery = "UPDATE " . static::TABLE_OBJECT . " SET PARENT_ID = " . $idTarget . " WHERE ID IN(" . implode(', ', $arIdFiles) . ")";
                $res = $DB->Query($sQuery);
            } else {
                $iResult = 0;
            }
        }
        return $iResult;
    }

    public static function actionDeleteFile($objectId)
    {
        global $USER;
        /** @var Folder|File $object */
        $object = \Bitrix\Disk\BaseObject::loadById((int)$objectId, array('STORAGE'));
        if (!$object) {
            return 0;
        }

        if ($object instanceof Folder) {
            if (!$object->deleteTree($this->getUser()->getId())) {
                return 0;
            }

        }

        if (!$object->delete($USER->GetID())) {
            return 0;
        }
        return 1;
    }


    public static function createSubFolder($name)
    {
        global $DB;
        $rootFolder = YNSIRDisk::rootFolder();
        $folder = Folder::loadById($rootFolder, array('STORAGE'));
        $arTemp = $folder->addSubFolder(array(
            'NAME' => $name,
            'CREATED_BY' => YNSIRDisk::USER_CREATE_TEMP_DISK
        ));
        if ($arTemp !== null) {
            $iResult = intval($arTemp->getId());
        } else {
            $sQuery = "SELECT * FROM " . static::TABLE_OBJECT . " WHERE DELETED_TYPE = 0 AND PARENT_ID = " . $rootFolder . " AND NAME='" . $name . "'";
            $res = $DB->Query($sQuery);
            if ($arData = $res->Fetch()) {
                $iResult = $arData['ID'];
            }
        }
        return $iResult;
    }

    public static function shareTempFolder($iType = 1, $idTempFolder = 0)
    {
        global $USER;
        $arResult = 0;
        $arDataShare = array();
        $diskpermission = new YNSIRDiskPermission();
        if ($iType == static::DEFAULT_SHARE_TYPE_USER && $idTempFolder > 0) {
            $bAddShare = true;
            if ($USER->IsAdmin()) {
                $bAddShare = false;
            } else {
                $arShareMember = $diskpermission->processActionShowSharingDetailChangeRights($idTempFolder);
                $iIdUser = $USER->GetID();
                foreach ($arShareMember['member'] as $arShareItem) {
                    $iId = 0;
                    switch ($arShareItem['type']) {
                        case 'department':
                            $iId = str_replace('DR', '', $arShareItem['entityId']);
                            break;
                        case 'groups':
                            $iId = str_replace('SG', '', $arShareItem['entityId']);
                            break;
                        default :
                            $iId = str_replace('U', '', $arShareItem['entityId']);
                            $bAddShare = $iId == $iIdUser ? false : true;
                            break;
                    }
                    $iId = intval($iId);
                    if ($iId > 0) {
                        $arDataShare[$arShareItem['entityId']] = array(
                            'item' => array(
                                'id' => $arShareItem['entityId'],
                                'entityId' => $iId,
                            ),
                            'type' => $arShareItem['type'],
                            'right' => $arShareItem['right']
                        );
                    }
                    if ($bAddShare == false) {
                        $arDataShare = array();
                        break;
                    }
                }
                if ($bAddShare == true) {
                    $sKeyEntity = 'U' . $iIdUser;
                    $arDataShare[$sKeyEntity] = array(
                        'item' => array(
                            'id' => $sKeyEntity,
                            'entityId' => $iIdUser,
                        ),
                        'type' => 'users',
                        'right' => static::DEFAULT_SHARE_TEMP_DISK
                    );
                }
            }
        } else {
            // update permission
        }
        if (!empty($arDataShare)) {
            $arResult = $diskpermission->processActionChangeSharingAndRights($idTempFolder, $arDataShare);
        }
        return $arResult;
    }

    /**
     * share permission for folder
     * @ param :
     *      $idFolder : id folder need sharing
     *      $arDataPerms : list item permission
     *          ID : id user, department or work group
     *          TYPE : users, department, groups
     *          PERMS : full, edit, add, read
     *      $sType : update / remove
     */
    public static function shareFolder($idFolder = 0, $arDataPerms = array(), $sType = 'update'){
        // disk_access_full
        $bResult = 1;
        $arDataShare = array();
        $diskPermission = new YNSIRDiskPermission();
        if($idFolder > 0 && !empty($arDataPerms)){
            $sPrefixPerms = "disk_access_";
            $arType = array("U" => "users", "DR" => "department", "SG" => "groups");
            $arTypePerms = array("full", "edit", "add", "read");
            $arShareMember = $diskPermission->processActionShowSharingDetailChangeRights($idFolder);
            foreach ($arShareMember['member'] as $arShareItem) {
                $iEntityId = 0;
                switch ($arShareItem['type']) {
                    case 'department':
                        $iEntityId = str_replace('DR', '', $arShareItem['entityId']);
                        break;
                    case 'groups':
                        $iEntityId = str_replace('SG', '', $arShareItem['entityId']);
                        break;
                    default :
                        $iEntityId = str_replace('U', '', $arShareItem['entityId']);
                        break;
                }
                $iEntityId = intval($iEntityId);
                if ($iEntityId > 0) {
                    $arDataShare[$arShareItem['entityId']] = array(
                        'item' => array(
                            'id' => $arShareItem['entityId'],
                            'entityId' => $iEntityId,
                        ),
                        'type' => $arShareItem['type'],
                        'right' => $arShareItem['right']
                    );
                }
            }
            // check isset permission
            foreach ($arDataPerms as $arItemPerms){
                $sId = $arItemPerms['TYPE'] . $arItemPerms['ID'];
                if(in_array($arItemPerms['PERMS'], $arTypePerms) && array_key_exists($arItemPerms['TYPE'], $arType)){
                    if($sType == 'update'){
                        $arDataShare[$sId] = array(
                            'item' => array(
                                'id' => $sId,
                                'entityId' => $arItemPerms['ID'],
                            ),
                            'type' => $arType[$arItemPerms['TYPE']],
                            'right' => $sPrefixPerms . $arItemPerms['PERMS'],
                        );
                    }
                    else {
                        unset($arDataShare[$sId]);
                    }
                }
            }
            $bResult = $diskPermission->processActionChangeSharingAndRights($idFolder, $arDataShare, true);
        }
        return $bResult;
    }

    /**
     * remove all sharing
     * @ param :
     *      $idFolder : id folder
     */
    public static function removeSharing($idFolder = 0){
        $iResult = 0;
        if($idFolder > 0){
            $diskPermission = new YNSIRDiskPermission();
            $bResult = $diskPermission->processActionChangeSharingAndRights($idFolder, array(), true);
        }
        return $iResult;
    }
}

?>