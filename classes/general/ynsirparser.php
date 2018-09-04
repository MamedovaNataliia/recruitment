<?php

/**
 * Author : LichTV
 * Company : YouNet SI
 */
class YNSIRParser
{

    public static function getContentDocument($sFilePath = "")
    {

        $arResult = array();
        $rs = YNSIRParser::parser_cv($sFilePath);
        $arResult['FIRST_NAME'] = $rs['basics']['name']['surname'] . ' ' . $rs['basics']['name']['middle'];
        $arResult['LAST_NAME'] = $rs['basics']['name']['firstName'];
        $arResult['GENDER'] = '';
        if ($rs['basics']['gender'] == 'female') {
            $arResult['GENDER'] = 'F';
        }
        if ($rs['basics']['gender'] == 'male') {
            $arResult['GENDER'] = 'M';
        }
        $arResult['EMAIL'] = $rs['basics']['email'];
        $arResult['PHONE'] = $rs['basics']['phone'];
        $arResult[YNSIRConfig::TL_CURRENT_JOB_TITLE] = $rs['basics']['title'];
        $arResult['SKILL_SET'] = '';
//        print_r($rs);die;
        foreach ($rs['skills'] as $value) {
            foreach ($value as $v) {
                $arResult['SKILL_SET'] .= $v;

            }
        }
        foreach ($rs['education_and_training'] as $value) {
            foreach ($value as $v) {
                $arResult['ADDITIONAL_INFO'] .= $v;

            }
        }
        return $arResult;
    }

    public static function parser_cv($ifile, $ofile = '')
    {
       $ofile = strlen($ofile) > 0?$ofile :$_SERVER["DOCUMENT_ROOT"].'/recruitment/uploadcv/text.txt';
       	shell_exec("cd ".$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ynsirecruitment/classes/general/ResumeParser/ResumeTransducer;java -cp 'bin/*:../GATEFiles/lib/*:../GATEFiles/bin/gate.jar:lib/*' code4goal.antony.resumeparser.ResumeParserProgram $ifile $ofile;");
		$striped_content = shell_exec("cd /home/bitrix/www/bitrix/modules/ynsirecruitment/classes/general/ResumeParser/ResumeTransducer;cat   $ofile");
        shell_exec("cd /home/bitrix/www/bitrix/modules/ynsirecruitment/classes/general/ResumeParser/ResumeTransducer;rm   $ofile");
        $striped_content = json_decode($striped_content, true);
        return $striped_content;
    }

    public static function parser($arDocument = "")
    {
        $arConfig = static::getSectionAndRegex();
        $arDocument = explode("\n", $arDocument);
//        $arDocument = static::getContentDocument($sFilePath);
        $sSection = 'GENERAL';
        $arCheched = array();
        $arResult = array();
        foreach ($arDocument as $sTextLine) {
            if (strlen(trim($sTextLine)) <= 0) continue;
            $sTmpDection = static::checkSection($sTextLine, $arConfig);
            if ($sTmpDection != 'GENERAL')
                $sSection = $sTmpDection;
            if (!isset($arCheched[$sSection])) {
                $arResult[$sSection] = array();
                $arCheched[$sSection] = array();
            }
            foreach ($arConfig[$sSection]['ITEM'] as $itemCheck) {
                $sRegex = '/' . $itemCheck['VALUE'] . '/';
                if ($itemCheck['TYPE'] != 'REGEX') {
                    $sRegex = '/(([^,]*)' . implode('([^,]*)|([^,]*)', $itemCheck['VALUE']) . '([^,]*))/';
                }
                $arPattern = array();
                if (!in_array($itemCheck['NAME'], $arCheched[$sSection])) {
                    preg_match_all($sRegex, $sTextLine, $arPattern);
                    if (!empty($arPattern[0])) {
                        $arCheched[$sSection][] = $itemCheck['NAME'];
                        $arResult[$sSection][$itemCheck['NAME']] = $arPattern[0];
                        break;
                    } else {
                        $sTmpDection = 'GENERAL';
                        foreach ($arConfig[$sTmpDection]['ITEM'] as $itemCheck) {
                            $sRegex = '/' . $itemCheck['VALUE'] . '/';
                            if ($itemCheck['TYPE'] != 'REGEX') {
                                $sRegex = '/(([^,]*)' . implode('([^,]*)|([^,]*)', $itemCheck['VALUE']) . '([^,]*))/';
                            }
                            $arPattern = array();
                            if (!in_array($itemCheck['NAME'], $arCheched[$sTmpDection])) {
                                preg_match_all($sRegex, $sTextLine, $arPattern);
                                if (!empty($arPattern[0])) {
                                    $arCheched[$sTmpDection][] = $itemCheck['NAME'];
                                    $arResult[$sTmpDection][$itemCheck['NAME']] = $arPattern[0];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $arResult;
    }

    public static function checkSection($sText = "", &$arConfig)
    {
        $sResult = "GENERAL";
        if (strlen($sText) > 0 && !empty($arConfig)) {
            $iIndex = -1;
            foreach ($arConfig as $key => $arSection) {
                foreach ($arSection['NAME'] as $itemText) {
                    if (strpos($sText, $itemText) !== false) {
                        $iIndexTmp = intval(intval(strpos($sText, $itemText)));
                        if ($iIndex == -1 || $iIndex > $iIndexTmp) {
                            $iIndex = $iIndexTmp;
                            $sResult = $key;
                        }
                    }
                }
            }
        }
        return $sResult;
    }

    public static function getSectionAndRegex()
    {
        $arResult = array(
            "GENERAL" => array(
                "NAME" => array(),
                "ITEM" => array(
                    array(
                        "VALUE" => "(^(Name:\s*)?(.+))",
                        "TYPE" => "REGEX",
                        "NAME" => "Name",
                    ),
                    array(
                        "VALUE" => "((\w+[.|\w])*@(\w+[.])*\w+)",
                        "TYPE" => "REGEX",
                        "NAME" => "Email",
                    ),
                    array(
                        "VALUE" => "([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})",
                        "TYPE" => "REGEX",
                        "NAME" => "Email",
                    ),
                    array(
                        "VALUE" => "(([a-z0-9!#$%'*+\/=?^_`{|.}~-]+@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?))",
                        "TYPE" => "REGEX",
                        "NAME" => "Email",
                    ),
                    array(
                        "VALUE" => "((\+\d+\s*)?(\(?\d{3}\)?\D+\d{3}\D+\d{4}))",
                        "TYPE" => "REGEX",
                        "NAME" => "Phone",
                    ),
                    array(
                        "VALUE" => "((\d{10}))",
                        "TYPE" => "REGEX",
                        "NAME" => "Phone",
                    ),
                    array(
                        "VALUE" => "((?:\(?\+91\)?)?\d{9})",
                        "TYPE" => "REGEX",
                        "NAME" => "Phone",
                    ),
                    array(
                        "VALUE" => "Address: (.+)",
                        "TYPE" => "REGEX",
                        "NAME" => "Address",
                    ),
                ),
            ),
            "EDUCATION" => array(
                "NAME" => array("Education", "EDUCATION"),
                "ITEM" => array(
                    array(
                        "VALUE" => array("10th", "X", "Matric", "SSC"),
                        "TYPE" => "TEXT",
                        "NAME" => "Secondary",
                    ),
                    array(
                        "VALUE" => array("Le Hong Phong High School", "THPT"),
                        "TYPE" => "TEXT",
                        "NAME" => "HighSchool",
                    ),
                    array(
                        "VALUE" => array("CDAC", "PGDBM"),
                        "TYPE" => "TEXT",
                        "NAME" => "Diploma",
                    ),
                    array(
                        "VALUE" => array("University", "Bachelor", "Diploma", "BE", "B.E.", "BTech", "B.Tech", "BS", "Mechanical", "Instrumentation", "Civil"),
                        "TYPE" => "TEXT",
                        "NAME" => "Bachelors",
                    ),
                    array(
                        "VALUE" => array("ME", "MTech", "M.E.", "M.Tech", "MS"),
                        "TYPE" => "TEXT",
                        "NAME" => "Masters",
                    ),
                    array(
                        "VALUE" => array("Doctorate", "PhD", "Ph.D.", "Ph.D"),
                        "TYPE" => "TEXT",
                        "NAME" => "PhD",
                    ),
                ),
            ),
            "SKILLS" => array(
                "NAME" => array("Skill", "Skills", "SKILLS", "Professional Skills", "PROFESSIONAL SKILLS"),
                "ITEM" => array(
                    array(
                        "VALUE" => array("Certified Scrum Master", "ISTQB"),
                        "TYPE" => "TEXT",
                        "NAME" => "Certifications",
                    ),
                    array(
                        "VALUE" => array("Selenium", "Selenium Grid", "Cucumber", "Jenkins", "Robot", "AutoIT", "SilkTest", "JMeter"),
                        "TYPE" => "TEXT",
                        "NAME" => "QA",
                    ),
                    array(
                        "VALUE" => array("Python", "Perl", "Java"),
                        "TYPE" => "TEXT",
                        "NAME" => "Programming",
                    ),
                ),
            ),
        );
        return $arResult;
    }
}

?>