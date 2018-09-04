<?php
class YNSIRFileProxy
{
    public static function WriteEventFileToResponse($eventID, $fileID, &$errors, $options = array())
    {
        $eventID = intval($eventID);
        $fileID = intval($fileID);

        if($eventID <= 0 || $fileID <= 0)
        {
            $errors[] = 'File not found';
            return false;
        }

        //Get event file IDs and check permissions
        $dbResult = YNSIREvent::GetListEx(
            array(),
            array(
                '=ID' => $eventID
                //'CHECK_PERMISSIONS' => 'Y' //by default
            ),
            false,
            false,
            array('ID', 'FILES'),
            array()
        );

        $event = $dbResult ? $dbResult->Fetch() : null;

        if(!$event)
        {
            $errors[] = 'File not found';
            return false;
        }

        if(is_array($event['FILES']))
        {
            $eventFiles = $event['FILES'];
        }
		elseif(is_string($event['FILES']) && $event['FILES'] !== '')
        {
            $eventFiles = unserialize($event['FILES']);
        }
        else
        {
            $eventFiles = array();
        }

        if(
            empty($eventFiles)
            || !is_array($eventFiles)
            || !in_array($fileID, $eventFiles, true)
        )
        {
            $errors[] = 'File not found';
            return false;
        }

        return self::InnerWriteFileToResponse($fileID, $errors, $options);
    }
    private static function InnerWriteFileToResponse($fileID, &$errors, $options = array())
    {
        $fileInfo = CFile::GetFileArray($fileID);
        if(!is_array($fileInfo))
        {
            $errors[] = 'File not found';
            return false;
        }

        $options = is_array($options) ? $options : array();
        // �rutch for CFile::ViewByUser. Waiting for main 14.5.2
        $options['force_download'] = true;
        set_time_limit(0);
        CFile::ViewByUser($fileInfo, $options);

        return true;
    }
}
?>
