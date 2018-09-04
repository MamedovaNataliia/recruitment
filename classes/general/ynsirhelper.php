<?php

class YNSIRHelper
{
    /*
     * require zip.so: install yum install php-pecl-zip //centos
     * using:
     * $content = read_file_docx('cv.docx');
     * if($content !== false) {
     *      echo nl2br($content);
     * }
     * else {
     *      echo 'Couldn\'t the file. Please check that file.';
     * }
     *
     **/
    function read_file_docx($filename)
    {
        $striped_content = '';
        $content = '';

        if (!$filename || !file_exists($filename)) return false;

        $zip = zip_open($filename);

        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {

            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }// end while

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);

        return $striped_content;
    }
    function read_file_doc($filename)
    {
        if(file_exists($filename))
        {
            if(($fileHandle = fopen($filename, 'r')) !== false )
            {
                $line = @fread($fileHandle, filesize($filename));
                $lines = explode(chr(0x0D),$line);
                $outtext = "";
                foreach($lines as $thisline)
                {
                    $pos = strpos($thisline, chr(0x00));
                    if (($pos !== FALSE)||(strlen($thisline)==0))
                    {
                    } else {
                        $outtext .= $thisline." ";
                    }
                }
                $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
                return $outtext;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    function html2text($filename) {
        $Document = file_get_contents($filename);
        return trim(strip_tags($Document));
    }

    /*
     * require xpdf: install yum install xpdf //centos
     **/
    function read_file_pdf($ifile, $ofile = 'text.txt')
    {
        $content = shell_exec("pdftotext -raw $ifile  $ofile");
        $striped_content = shell_exec("cat   $ofile");
        shell_exec("rm   $ofile");
        return $striped_content;
    }

    /*
     * get tooltip for user in bitrix
     * */
    public static function getTooltipUser($uuid = 0, $id)
    {
        $_user = array();
        if (intval($uuid) <= 0) return;
        $rsUser = CUser::GetByID($uuid);
        $arUser = $rsUser->Fetch();
        if (!empty($arUser)) {
            $fname = CUser::FormatName(
                CSite::GetNameFormat(false),
                array(
                    "NAME" => $arUser['NAME'],
                    "LAST_NAME" => $arUser['LAST_NAME'],
                    "SECOND_NAME" => $arUser['SECOND_NAME'],
                    "LOGIN" => $arUser['LOGIN']
                )
            );
            $sToolTip_user = '<script type="text/javascript">BX.tooltip("' . $arUser['ID'] . '","user_tooltip_' . $arUser['ID'] . '_' . $id . '","","",false,"");</script>';
            $_user = "<a id='user_tooltip_" . $arUser['ID'] . '_' . $id . "' alt ='" . $fname['ID'] . "' href = '/company/personal/user/" . $arUser['ID'] . "/'>" . $fname . "</a>" . $sToolTip_user;
        }
        return $_user;
    }

    public static function getSlug($str)
    {
        $str = htmlspecialchars_decode($str);
        $unicodes = array(
            "a" => "á|à|ạ|ả|ã|ă|ắ|ằ|ặ|ẳ|ẵ|â|ấ|ầ|ậ|ẩ|ẫ|Á|À|Ạ|Ả|Ã|Ă|Ắ|Ằ|Ặ|Ẳ|Ẵ|Â|Ấ|Ầ|Ậ|Ẩ|Ẫ",
            "o" => "ó|ò|ọ|ỏ|õ|ô|ố|ồ|ộ|ổ|ỗ|ơ|ớ|ờ|ợ|ở|ỡ|Ó|Ò|Ọ|Ỏ|Õ|Ô|Ố|Ồ|Ộ|Ổ|Ỗ|Ơ|Ớ|Ờ|Ợ|Ở|Ỡ",
            "e" => "é|è|ẹ|ẻ|ẽ|ê|ế|ề|ệ|ể|ễ|É|È|Ẹ|Ẻ|Ẽ|Ê|Ế|Ề|Ệ|Ể|Ễ",
            "u" => "ú|ù|ụ|ủ|ũ|ư|ứ|ừ|ự|ử|ữ|Ú|Ù|Ụ|Ủ|Ũ|Ư|Ứ|Ừ|Ự|Ử|Ữ",
            "i" => "í|ì|ị|ỉ|ĩ|Í|Ì|Ị|Ỉ|Ĩ",
            "y" => "ý|ỳ|ỵ|ỷ|ỹ|Ý|Ỳ|Ỵ|Ỷ|Ỹ",
            "d" => "đ|Đ",
        );
        foreach ($unicodes as $ascii => $unicode) {
            $str = preg_replace("/({$unicode})/miU", $ascii, $str);
        }
        $str = preg_replace('/[^a-zA-Z0-9\-]+/miU', '-', $str);

        return strtolower(trim($str, '-'));
    }

    /*
     * get tooltip for user in bitrix
     * */
    public static function getTooltipandPhotoUser($uuid = 0, $id)
    {
        $arReturn = array();
        if (intval($uuid) <= 0) return;
        $rsUser = CUser::GetByID($uuid);
        $arUser = $rsUser->Fetch();
        if (!empty($arUser)) {
            $fname = CUser::FormatName(
                CSite::GetNameFormat(false),
                array(
                    "NAME" => $arUser['NAME'],
                    "LAST_NAME" => $arUser['LAST_NAME'],
                    "SECOND_NAME" => $arUser['SECOND_NAME'],
                    "LOGIN" => $arUser['LOGIN']
                )
            );
            $file = new CFile();
            $fileInfo = $file->ResizeImageGet(
                $arUser['PERSONAL_PHOTO'],
                array('width' => 34, 'height' => 34),
                BX_RESIZE_IMAGE_EXACT
            );
            if (is_array($fileInfo) && isset($fileInfo['src'])) {
                $arReturn['PHOTO_URL'] = $fileInfo['src'];
            }
            $sToolTip_user = '<script type="text/javascript">BX.tooltip("' . $arUser['ID'] . '","user_tooltip_' . $arUser['ID'] . '_' . $id . '","","",false,"");</script>';
            $arReturn['TOOLTIP'] = $_user = "<a id='user_tooltip_" . $arUser['ID'] . '_' . $id . "' alt ='" . $fname['ID'] . "' href = '/company/personal/user/" . $arUser['ID'] . "/'>" . $fname . "</a>" . $sToolTip_user;
        }
        return $arReturn;
    }

    static function checkFloatField(&$value)
    {
        $flag = true;
        if (is_string($value) && $value !== '') {
            $value = str_replace(array(',', ' '), array('.', ''), $value);
            //HACK: MSSQL returns '.00' for zero value
            if (strpos($value, '.') === 0) {
                $value = '0' . $value;
            }

            if (!preg_match('/^-?\d{1,}(\.\d{1,})?$/', $value)) {
                $flag = false;
            }
        }
        return $flag;
    }
}