<?php
/**
 * Company : YouNet SI
 */
IncludeModuleLangFile(__FILE__);

class YNSIRConfig
{
    const SALT_NAME = 'SALT_NAME';
    const GENDER = 'GENDER';
    const MARITAL_STATUS = 'MARITAL_STATUS';
    const QUALIFICATION = 'QUALIFICATION';
    const CURRENT_JOB_TITLE = 'CURRENT_JOB_TITLE';
    const CANDIDATE_STATUS = 'CANDIDATE_STATUS';
    const SOURCE = 'SOURCE';

    const CS_BASIC_INFO = 'CBASIC_INFO';
    const CS_ADDRESS_INFORMATION = 'CADDRESS_INFORMATION';
    const CS_PROFESSIONAL_DETAILS = 'CPROFESSIONAL_DETAILS';
    const CS_OTHER_INFO = 'COTHER_INFO';
    const CS_ATTACHMENT_INFORMATION = 'CATTACHMENT_INFORMATION';

    const OS_BASIC_INFO = 'OBASIC_INFO';
    const OS_SENSITIVE = 'OSENSITIVE';
    const OS_INTERVIEWS = 'OINTERVIEWS';
    const OS_DESCRIPTION = 'ODESCRIPTION';
    const OS_APPROVE = 'OAPPROVE';
    //update by nhatth@younetco.com
    const TL_SALT_NAME = 'SALT_NAME';
    const TL_TYPE_OF_EMPLOYMENT = 'TYPE_OF_EMPLOYMENT';
    const TL_CURRENT_JOB_TITLE = 'CURRENT_JOB_TITLE';
    const TL_CANDIDATE_STATUS = 'CANDIDATE_STATUS';
    const TL_SOURCES = 'SOURCE';
    const TL_UNIVERSITY = 'EDUCATION';
    const TL_APPLY_POSITION = 'WORK_POSITION';
    const TL_EDUCATION = 'EDUCATION';
    const TL_MAJOR = 'MAJOR';
    const TL_ENGLISH_PROFICIENCY = 'ENGLISH_PROFICIENCY';
    const TL_INTEREST = 'INTEREST';
    const TL_CURRENT_STATUS = 'CURRENT_STATUS';
    const TL_EQUIPMENT = 'EQUIPMENT';
    const TL_CONTRACT_TYPE = 'CONTRACT_TYPE';
    const TL_REASON_FOR_LEAVING = 'REASON_FOR_LEAVING';
    const TL_MARITAL_STATUS = 'MARITAL_STATUS';
    const TL_ORDER_JOB_STATUS = 'ORDER_JOB_STATUS';

    const TL_WORK_POSITION = 'WORK_POSITION';
    const TL_PERSONAL_PROFESSION = 'PERSONAL_PROFESSION';
    const TL_CURRENT_FORMER_ISSUE_PLACE = 'CURRENT_FORMER_ISSUE_PLACE';

    const TL_TEMPLATE_CATEGORY = 'TEMPLATE_CATEGORY';
    const TL_SECTION_JO_DESCRIPTION = 'SECTION_JO_DESCRIPTION';


    const YNSIR_TYPE_LIST_DATE = 1;
    const YNSIR_TYPE_LIST_NUMBER = 2;
    const YNSIR_TYPE_LIST_STRING = 3;
    const YNSIR_TYPE_LIST_USER = 4;

    //add method
    const IMPORT_FROM_EXCEL = 'IMPORT_FROM_EXCEL';
    const MANUAL = 'MANUAL';
    const IMPORT_FROM_RESUME = 'IMPORT_FROM_RESUME';

    //CACHE
    const YNSIR_CACHE_TIME = 86400;
    const YNSIR_CACHE_LISTS_ID = 'YNSIR_LISTS';
    const YNSIR_CACHE_LISTS_PATH = '/ynsirecruitment/lists/';

    //end update by nhatth@younetco.com
    public static function getListConfig($sEntity = "")
    {
        $arResult = array();
        switch ($sEntity) {
            case static::GENDER:
                $arResult = array(
                    'M' => "Male",
                    'F' => "Female"
                );
                break;
            case 'ADD_METHOD':
                $arResult = array(
                    static::MANUAL => "Manual",
                    static::IMPORT_FROM_EXCEL => "Import from excel",
                    static::IMPORT_FROM_RESUME => "Parser from resume"
                );
                break;
            case static::MARITAL_STATUS:
                $arResult = array(
                    1 => "Single",
                    2 => "Married",
                    2 => "Separated",
                    2 => "Divorced"
                );
                break;
            case static::SALT_NAME:
                $arResult = array(
                    1 => "Mr.",
                    2 => "Mrs.",
                    3 => "Ms.",
                );
                break;
            case static::QUALIFICATION:
                $arResult = array(
                    1 => "M.C.A.",
                    2 => "B.E.",
                    3 => "B.SC.",
                    4 => "M.S.",
                    5 => "B.Tech",
                );
                break;
            case static::CURRENT_JOB_TITLE:
                $arResult = array(
                    1 => "Fresher",
                    2 => "Project-Lead",
                    3 => "Project-Manager",
                );
                break;
            case static::CANDIDATE_STATUS:
                $arResult = array(
                    1 => "New",
                    2 => "Waiting-for-Evaluation",
                    3 => "Qualified",
                    4 => "Unqualified",
                    5 => "Junk candidate",
                    6 => "Contacted",
                    7 => "Contact in Future",
                    8 => "Not Contacted",
                    9 => "Attempted to Contact",
                    10 => "Associated",
                    11 => "Submitted-to-client",
                    12 => "Approved by client",
                    13 => "Rejected by client",
                    14 => "Interview-to-be-Scheduled",
                    15 => "Interview-Scheduled",
                    16 => "Rejected-for-Interview",
                    17 => "Interview-in-Progress",
                    18 => "On-Hold",
                    19 => "Hired",
                    20 => "Rejected",
                    21 => "Rejected-Hirable",
                    22 => "To-be-Offered",
                    23 => "Offer-Accepted",
                    24 => "Offer-Made",
                    25 => "Offer-Declined",
                    26 => "Offer-Withdrawn",
                    27 => "Joined",
                    28 => "No-Show",
                );
                break;
            case static::SOURCE:
                $arResult = array(
                    1 => "Added by User",
                    2 => "Advertisement",
                    3 => "API",
                    4 => "Cold Call",
                    5 => "Embed",
                    6 => "Employee Referral",
                    7 => "External Referral",
                    8 => "Facebook",
                    9 => "Gapps",
                    10 => "Google import",
                    11 => "Import",
                    12 => "Imported by parser",
                    13 => "Internal",
                    14 => "Partner",
                    15 => "Resume Inbox",
                    16 => "Search Engine",
                    17 => "Twitter",
                    18 => "Imported from Zoho CRM",
                );
                break;
            default:
                // TODO
                break;
        }
        return $arResult;
    }

    public static function getFieldsIntabViewCandidate()
    {
        return array(
            static::CS_BASIC_INFO => array(
                'NAME' => GetMessage('YNSIR_CS_BASIC_INFO'),
                'FIELDS' => array(
                    array('KEY' => 'EMAIL', 'NAME' => GetMessage('YNSIR_IG_TITLE_EMAIL')),
                    array('KEY' => 'ID', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_ID')),
                    array('KEY' => 'GENDER', 'NAME' => GetMessage('YNSIR_IG_TITLE_GENDER')),
                    array('KEY' => 'MARITAL_STATUS', 'NAME' => GetMessage('YNSIR_IG_TITLE_MARITAL_STATUS')),
                    array('KEY' => 'CMOBILE', 'NAME' => GetMessage('YNSIR_IG_TITLE_CMOBILE')),
                    array('KEY' => 'NAME', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_NAME')),
                    array('KEY' => 'WEBSITE', 'NAME' => GetMessage('YNSIR_IG_TITLE_WEBSITE')),
                    array('KEY' => 'FACEBOOK', 'NAME' => GetMessage('YNSIR_IG_TITLE_FACEBOOK')),
                    array('KEY' => 'DOB', 'NAME' => GetMessage('YNSIR_IG_TITLE_DOB')),
                )
            ),
            static::CS_PROFESSIONAL_DETAILS => array(
                'NAME' => GetMessage('YNSIR_CS_PROFESSIONAL_DETAILS'),
                'FIELDS' => array(
                    array('KEY' => 'EXPERIENCE', 'NAME' => GetMessage('YNSIR_IG_TITLE_EXPERIENCE')),
                    array('KEY' => 'TYPE_OF_EMPLOYMENT', 'NAME' => GetMessage('YNSIR_IG_TITLE_HIGHEST_QUALIFICATION_HELD')),
                    array('KEY' => 'CURRENT_JOB_TITLE', 'NAME' => GetMessage('YNSIR_IG_TITLE_CURRENT_JOB_TITLE')),
                    array('KEY' => 'CURRENT_EMPLOYER', 'NAME' => GetMessage('YNSIR_IG_TITLE_CURRENT_EMPLOYER')),
                    array('KEY' => static::TL_APPLY_POSITION, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_APPLY_POSITION')),
                    array('KEY' => static::TL_MAJOR, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_MAJOR')),
                    array('KEY' => static::TL_UNIVERSITY, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_UNIVERSITY')),
                    array('KEY' => static::TL_ENGLISH_PROFICIENCY, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_ENGLISH_PROFICIENCY')),


                    array('KEY' => 'EXPECTED_SALARY', 'NAME' => GetMessage('YNSIR_IG_TITLE_EXPECTED_SALARY')),
                    array('KEY' => 'CURRENT_SALARY', 'NAME' => GetMessage('YNSIR_IG_TITLE_CURRENT_SALARY')),
                    array('KEY' => 'SKILL_SET', 'NAME' => GetMessage('YNSIR_IG_TITLE_SKILL_SET')),
                    array('KEY' => 'ADDITIONAL_INFO', 'NAME' => GetMessage('YNSIR_IG_TITLE_ADDITIONAL_INFO')),
                    array('KEY' => 'CANDIDATE_STATUS', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_STATUS')),
                    array('KEY' => 'SKYPE_ID', 'NAME' => GetMessage('YNSIR_IG_TITLE_SKYPE_ID')),
                    array('KEY' => 'TWITTER', 'NAME' => GetMessage('YNSIR_IG_TITLE_TWITTER')),
                    array('KEY' => 'LINKEDIN', 'NAME' => GetMessage('YNSIR_IG_TITLE_LINKEDIN')),
                    array('KEY' => 'CREATED_BY', 'NAME' => GetMessage('YNSIR_IG_TITLE_CREATED_BY')),
                    array('KEY' => 'MODIFIED_BY', 'NAME' => GetMessage('YNSIR_IG_TITLE_MODIFIED_BY')),
                )
            ),
            static::CS_OTHER_INFO => array(
                'NAME' => GetMessage('YNSIR_CS_OTHER_INFO'),
                'FIELDS' => array(
                    array('KEY' => 'CANDIDATE_OWNER', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_OWNER')),
                    array('KEY' => 'SOURCE', 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_SOURCE')),
//                    array('KEY' => 'OMOBILE', 'NAME' => GetMessage('YNSIR_IG_TITLE_OMOBILE')),
                    array('KEY' => 'EMAIL_OPT_OUT', 'NAME' => GetMessage('YNSIR_IG_TITLE_EMAIL_OPT_OUT')),
                )
            ),
            static::CS_ATTACHMENT_INFORMATION => array(
                'NAME' => GetMessage('YNSIR_CS_ATTACHMENT_INFORMATION'),
                'FIELDS' => array(
                    array('KEY' => YNSIR_FT_RESUME, 'NAME' => GetMessage('YNSIR_IG_TITLE_FT_RESUME')),
                    array('KEY' => YNSIR_FT_FORMATTED_RESUME, 'NAME' => GetMessage('YNSIR_IG_TITLE_FT_FORMATTED_RESUME')),
                    array('KEY' => YNSIR_FT_COVER_LETTER, 'NAME' => GetMessage('YNSIR_IG_TITLE_FT_COVER_LETTER')),
                    array('KEY' => YNSIR_FT_OTHERS, 'NAME' => GetMessage('YNSIR_IG_TITLE_YNSIR_FT_OTHERS')),
                )
            )
            //update by nhatth2

        );
    }

    public static function getFieldsIntabEditCandidate()
    {
        return array(
            static::CS_BASIC_INFO => array(
                'NAME' => GetMessage('YNSIR_CS_BASIC_INFO'),
                'FIELDS' => array(
                    array(
                        array('KEY' => 'FIRST_NAME', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_FIRST_NAME'), 'ROW' => 1),
                        array('KEY' => 'LAST_NAME', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_LAST_NAME'), 'ROW' => 1)
                    ),
                    array(
                        array('KEY' => 'GENDER', 'NAME' => GetMessage('YNSIR_IG_TITLE_GENDER'), 'ROW' => 1),
                        array('KEY' => 'MARITAL_STATUS', 'NAME' => GetMessage('YNSIR_IG_TITLE_MARITAL_STATUS'), 'ROW' => 1)
                    ),
                    array(
                        array('KEY' => 'EMAIL', 'NAME' => GetMessage('YNSIR_IG_TITLE_EMAIL')),
                        array('KEY' => 'CMOBILE', 'NAME' => GetMessage('YNSIR_IG_TITLE_CMOBILE'))
                    ),
                    array(
                        array('KEY' => 'DOB', 'NAME' => GetMessage('YNSIR_IG_TITLE_DOB'))
                    ),
                    array(
                        array('KEY' => 'WEBSITE', 'NAME' => GetMessage('YNSIR_IG_TITLE_WEBSITE')),
                        array('KEY' => 'FACEBOOK', 'NAME' => GetMessage('YNSIR_IG_TITLE_FACEBOOK'))
                    )
                )
            ),
            static::CS_PROFESSIONAL_DETAILS => array(
                'NAME' => GetMessage('YNSIR_CS_PROFESSIONAL_DETAILS'),
                'FIELDS' => array(
                    array(
                        array('KEY' => 'EXPERIENCE', 'NAME' => GetMessage('YNSIR_IG_TITLE_EXPERIENCE')),
                        array('KEY' => 'TYPE_OF_EMPLOYMENT', 'NAME' => GetMessage('YNSIR_IG_TITLE_HIGHEST_QUALIFICATION_HELD')),
                    ),
                    array(
                        array('KEY' => 'CURRENT_JOB_TITLE', 'NAME' => GetMessage('YNSIR_IG_TITLE_CURRENT_JOB_TITLE')),
                        array('KEY' => 'CURRENT_EMPLOYER', 'NAME' => GetMessage('YNSIR_IG_TITLE_CURRENT_EMPLOYER')),
                    ),
                    array(
                        array('KEY' => static::TL_APPLY_POSITION, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_APPLY_POSITION')),
                        array('KEY' => static::TL_MAJOR, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_MAJOR')),
                    ),
                    array(
                        array('KEY' => static::TL_UNIVERSITY, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_UNIVERSITY')),
                        array('KEY' => static::TL_ENGLISH_PROFICIENCY, 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_ENGLISH_PROFICIENCY')),
                    ),
                    array(
                        array('KEY' => 'EXPECTED_SALARY', 'NAME' => GetMessage('YNSIR_IG_TITLE_EXPECTED_SALARY')),
                        array('KEY' => 'CURRENT_SALARY', 'NAME' => GetMessage('YNSIR_IG_TITLE_CURRENT_SALARY')),
                    ),
                    array(
                        array('KEY' => 'SKILL_SET', 'NAME' => GetMessage('YNSIR_IG_TITLE_SKILL_SET')),
                        array('KEY' => 'ADDITIONAL_INFO', 'NAME' => GetMessage('YNSIR_IG_TITLE_ADDITIONAL_INFO')),
                    ),
                    array(
                        array('KEY' => 'SKYPE_ID', 'NAME' => GetMessage('YNSIR_IG_TITLE_SKYPE_ID')),
                        array('KEY' => 'TWITTER', 'NAME' => GetMessage('YNSIR_IG_TITLE_TWITTER')),
                    ), array(
                        array('KEY' => 'LINKEDIN', 'NAME' => GetMessage('YNSIR_IG_TITLE_LINKEDIN')),
                    )
                )
            ),
            static::CS_OTHER_INFO => array(
                'NAME' => GetMessage('YNSIR_CS_OTHER_INFO'),
                'FIELDS' => array(
                    array(
                        array('KEY' => 'CANDIDATE_STATUS', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_STATUS')),
                        array('KEY' => 'SOURCE', 'NAME' => GetMessage('YNSIR_IG_TITLE_TL_SOURCE')),
                    ),
                    array(
                        array('KEY' => 'CANDIDATE_OWNER', 'NAME' => GetMessage('YNSIR_IG_TITLE_CANDIDATE_OWNER')),
//                        array('KEY' => 'OMOBILE', 'NAME' => GetMessage('YNSIR_IG_TITLE_OMOBILE')),
                    ),
                    array(
                        array('KEY' => 'EMAIL_OPT_OUT', 'NAME' => GetMessage('YNSIR_IG_TITLE_EMAIL_OPT_OUT')),
                    )
                )
            ),
            static::CS_ATTACHMENT_INFORMATION => array(
                'NAME' => GetMessage('YNSIR_CS_ATTACHMENT_INFORMATION'),
                'FIELDS' => array()
            )
            //update by nhatth2

        );
    }

    public static function getFieldsCandidateMult()
    {
        return array(
            'PHONE',
            'EMAIL',
            'CMOBILE',
            'CURRENT_JOB_TITLE',
            'TYPE_OF_EMPLOYMENT',
            'EDUCATION',
            'MAJOR',
            'SOURCE',
            'ENGLISH_PROFICIENCY'
        );
    }
    public static function getFieldsExecel()
    {
        return array(
            'TYPE_OF_EMPLOYMENT',
            'EDUCATION',
            'SOURCE',
            'ENGLISH_PROFICIENCY'
        );
    }

    public static function getFieldsCandidate()
    {
        $arField = self::getFieldsIntabEditCandidate();
        $arFieldCandidata = array();
        foreach ($arField as $key => $fields) {
            foreach ($fields['FIELDS'] as $k => $f) {
                foreach ($f as $_k => $_field) {
                    $arFieldCandidata[$_field['KEY']] = $_field['NAME'];
                }
            }
        }

        $arFieldFile = self::getArrayFileFieldSection();
        return array_merge($arFieldCandidata, $arFieldFile);
    }

    public static function getSectionCandidate()
    {
        return array(
            static::CS_BASIC_INFO,
            static::CS_ADDRESS_INFORMATION,
            static::CS_PROFESSIONAL_DETAILS,
            static::CS_OTHER_INFO,
            static::CS_ATTACHMENT_INFORMATION,
        );
    }

    public static function getArrayFileFieldSection()
    {
        return array(
            YNSIR_FT_RESUME => GetMessage('YNSIR_IG_TITLE_FILE_RESUME'),
            YNSIR_FT_FORMATTED_RESUME => GetMessage('YNSIR_IG_TITLE_FILE_FORMATTED_RESUME'),
            YNSIR_FT_COVER_LETTER => GetMessage('YNSIR_IG_TITLE_FILE_COVER_LETTER'),
            YNSIR_FT_OTHERS => GetMessage('YNSIR_IG_TITLE_FILE_OTHERS'),
        );
    }

    public static function getSectionCandidatePerms()
    {
        return array(
            YNSIR_PERM_ENTITY_CANDIDATE => array(
                'FIELDS' => array(
                    static::CS_BASIC_INFO => GetMessage('YNSIR_CS_BASIC_INFO'),
                    static::CS_ADDRESS_INFORMATION => GetMessage('YNSIR_CS_ADDRESS_INFORMATION'),
                    static::CS_PROFESSIONAL_DETAILS => GetMessage('YNSIR_CS_PROFESSIONAL_DETAILS'),
                    static::CS_OTHER_INFO => GetMessage('YNSIR_CS_OTHER_INFO'),
                    static::CS_ATTACHMENT_INFORMATION => GetMessage('YNSIR_CS_ATTACHMENT_INFORMATION'),
                )
            ),
            YNSIR_PERM_ENTITY_ORDER => array(
                'FIELDS' => array(
                    static::OS_BASIC_INFO => GetMessage('YNSIR_OS_BASIC_INFO'),
                    static::OS_SENSITIVE => GetMessage('YNSIR_OS_SENSITIVE'),
                    static::OS_INTERVIEWS => GetMessage('YNSIR_OS_INTERVIEWS'),
                    static::OS_DESCRIPTION => GetMessage('YNSIR_OS_DESCRIPTION'),
                    static::OS_APPROVE => GetMessage('YNSIR_OS_APPROVE'),
                )
            )
        );
    }

    public static function getEntityPerms()
    {
        return array(
//            YNSIR_PERM_ENTITY_GENERAL => GetMessage('YNSIR_PERM_ENTITY_GENERAL'),
//            YNSIR_PERM_ENTITY_LIST => GetMessage('YNSIR_PERM_ENTITY_LIST'),
            YNSIR_PERM_ENTITY_ORDER => GetMessage('YNSIR_PERM_ENTITY_ORDER'),
            YNSIR_PERM_ENTITY_CANDIDATE => GetMessage('YNSIR_PERM_ENTITY_CANDIDATE'),
//            YNSIR_PERM_ENTITY_OTHER => GetMessage('YNSIR_PERM_ENTITY_OTHER'),
//            YNSIR_PERM_ENTITY_ACTIVITIES => GetMessage('YNSIR_PERM_ENTITY_ACTIVITIES'),
        );
    }

    public static function getPermissionSet()
    {
        $arResult = array();
        $arPermsFull = array(
            YNSIR_PERM_NONE => GetMessage('YNSIR_PERMS_TYPE_' . YNSIR_PERM_NONE),
            YNSIR_PERM_SELF => GetMessage('YNSIR_PERMS_TYPE_' . YNSIR_PERM_SELF),
            YNSIR_PERM_DEPARTMENT => GetMessage('YNSIR_PERMS_TYPE_' . YNSIR_PERM_DEPARTMENT),
            YNSIR_PERM_SUBDEPARTMENT => GetMessage('YNSIR_PERMS_TYPE_' . YNSIR_PERM_SUBDEPARTMENT),
//            YNSIR_PERM_OPEN => GetMessage('YNSIR_PERMS_TYPE_' . YNSIR_PERM_OPEN),
            YNSIR_PERM_ALL => GetMessage('YNSIR_PERMS_TYPE_' . YNSIR_PERM_ALL)
        );

//        $arResult[YNSIR_PERM_ENTITY_GENERAL] = $arPermsFull;
//        $arResult[YNSIR_PERM_ENTITY_LIST] = $arPermsFull;
        $arResult[YNSIR_PERM_ENTITY_CANDIDATE] = $arPermsFull;
        $arResult[YNSIR_PERM_ENTITY_ORDER] = $arPermsFull;
//        $arResult[YNSIR_PERM_ENTITY_OTHER] = $arPermsFull;
//        $arResult[YNSIR_PERM_ENTITY_ACTIVITIES] = $arPermsFull;
        return $arResult;
    }

    public static function getAllowedEntityPerms()
    {
        // 'READ', 'ADD', 'WRITE', 'DELETE', 'EXPORT', 'IMPORT'
        return array(/*YNSIR_PERM_ENTITY_ORDER => array('READ', 'ADD', 'WRITE', 'DELETE'),
			YNSIR_PERM_ENTITY_GENERAL => array('READ', 'ADD', 'WRITE', 'DELETE'),
			YNSIR_PERM_ENTITY_LIST => array('READ', 'ADD', 'WRITE', 'DELETE'),*/
        );
    }

    public static function getFullPerms()
    {
        return array(
            'READ' => GetMessage('YNSIR_PERMS_HEAD_READ'),
            'ADD' => GetMessage('YNSIR_PERMS_HEAD_ADD'),
            'WRITE' => GetMessage('YNSIR_PERMS_HEAD_WRITE'),
            'DELETE' => GetMessage('YNSIR_PERMS_HEAD_DELETE'),
//            'EXPORT' => GetMessage('YNSIR_PERMS_HEAD_EXPORT'),
//            'IMPORT' => GetMessage('YNSIR_PERMS_HEAD_IMPORT')
        );
    }

    //update by nhatth2@youentco.com
    public static function getTypeList()
    {
        return array(
//            static::TL_SALT_NAME => array(
//                'CODE' => strtolower(static::TL_SALT_NAME),
//                "NAME" => GetMessage("YNSIR_IG_TITLE_SALT_NAME"),
//            ),
            static::TL_ORDER_JOB_STATUS => array(
                'CODE' => strtolower(static::TL_ORDER_JOB_STATUS),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TL_ORDER_JOB_STATUS"),
            ),
            static::TL_CANDIDATE_STATUS => array(
                'CODE' => strtolower(static::TL_CANDIDATE_STATUS),
                "NAME" => GetMessage("YNSIR_IG_TITLE_CANDIDATE_STATUS"),
            ),
            static::TL_TYPE_OF_EMPLOYMENT => array(
                'CODE' => strtolower(static::TL_TYPE_OF_EMPLOYMENT),
                "NAME" => GetMessage("YNSIR_IG_TITLE_HIGHEST_QUALIFICATION_HELD"),
            ),
            static::TL_SOURCES => array(
                'CODE' => strtolower(static::TL_SOURCES),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TL_SOURCE"),
            ),
            static::TL_UNIVERSITY => array(
                'CODE' => strtolower(static::TL_UNIVERSITY),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TL_UNIVERSITY"),
            ),
            static::TL_MAJOR => array(
                'CODE' => strtolower(static::TL_MAJOR),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TL_MAJOR"),
            ),
            static::TL_ENGLISH_PROFICIENCY => array(
                'CODE' => strtolower(static::TL_ENGLISH_PROFICIENCY),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TL_ENGLISH_PROFICIENCY"),
            ),
            static::TL_APPLY_POSITION => array(
                'CODE' => strtolower(static::TL_APPLY_POSITION),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TL_APPLY_POSITION"),
            ),
            static::TL_MARITAL_STATUS => array(
                'CODE' => strtolower(static::TL_MARITAL_STATUS),
                "NAME" => GetMessage("YNSIR_IG_TITLE_MARITAL_STATUS"),
            ),
            static::TL_TEMPLATE_CATEGORY => array(
                'CODE' => strtolower(static::TL_TEMPLATE_CATEGORY),
                "NAME" => GetMessage("YNSIR_IG_TITLE_TEMPLATE_CATEGORY"),
            ),
            static::TL_SECTION_JO_DESCRIPTION => array(
                'CODE' => strtolower(static::TL_SECTION_JO_DESCRIPTION),
                "NAME" => GetMessage("YNSIR_IG_TITLE_SECTION_JO_DESCRIPTION"),
            ),

        );
    }

    public static function getListContentType()
    {
        return array(
            static::YNSIR_TYPE_LIST_DATE => "Date",//GetMessage('YNSIR_TYPE_LIST_DATE'),
            static::YNSIR_TYPE_LIST_NUMBER => "Number",//GetMessage('YNSIR_TYPE_LIST_NUMBER'),
            static::YNSIR_TYPE_LIST_STRING => "String",//GetMessage('YNSIR_TYPE_LIST_STRING'),
            static::YNSIR_TYPE_LIST_USER => "User",//GetMessage('YNSIR_TYPE_LIST_STRING'),
        );
    }
    public static function resolveListContentType($type)
    {
        $arContent =  array(
            static::YNSIR_TYPE_LIST_DATE => "DATE",//GetMessage('YNSIR_TYPE_LIST_DATE'),
            static::YNSIR_TYPE_LIST_NUMBER => "NUMBER",//GetMessage('YNSIR_TYPE_LIST_NUMBER'),
            static::YNSIR_TYPE_LIST_STRING => "STRING",//GetMessage('YNSIR_TYPE_LIST_STRING'),
            static::YNSIR_TYPE_LIST_USER => "USER",//GetMessage('YNSIR_TYPE_LIST_STRING'),
        );
        return $arContent[$type];
    }


    /**
     * @param int $catcheTime
     * @return array
     */
    public static function GetListTypeList($catcheTime = self::YNSIR_CACHE_TIME)
    {

        $userCache = false;
        $arResult = array();
        $arList = YNSIRCacheHelper::GetCached($catcheTime, self::YNSIR_CACHE_LISTS_ID, self::YNSIR_CACHE_LISTS_PATH);
        if (is_array($arList["DATA"]) && !($arList["ERROR"]) && $userCache) {
            $arResult = $arList["DATA"];
        } else {
            $rsTypeList = YNSIRTypelist::GetList(array(), array(), false);
            while ($element_list = $rsTypeList->GetNext()) {
                $arResult[$element_list['ENTITY']][$element_list['ID']] = $element_list;
            }
            //save cache
            if ($catcheTime > 0 && (count($arResult) > 0)) {
                YNSIRCacheHelper::SetCached($arResult, $catcheTime, self::YNSIR_CACHE_LISTS_ID, self::YNSIR_CACHE_LISTS_PATH);
            }
        }
        return $arResult;
    }

    /**
     * @function getFieldAssociate
     * @author Nhatth2
     * @created_date 15-09-2017
     * @return array
     */
    public static function getFieldAssociate()
    {
        $arResult = array(
            "ID" => GetMessage("YNSIR_COLUMN_ID"),
            "MODIFIED_BY" => GetMessage("YNSIR_COLUMN_MODIFIED_BY"),
            "CREATED_BY" => GetMessage("YNSIR_COLUMN_CREATED_BY"),
            "STATUS_ID" => GetMessage("YNSIR_COLUMN_STATUS_ID"),
            "STATUS_ROUND_ID" => GetMessage("YNSIR_COLUMN_STATUS_ROUND_ID"),
            "ORDER_JOB_ID" => GetMessage("YNSIR_COLUMN_ORDER_JOB"),
            "CANDIDATE_ID" => GetMessage("YNSIR_COLUMN_CANDIDATE"),
            "CREATED_DATE" => GetMessage("YNSIR_COLUMN_CREATED_DATE"),
            "MODIFIED_DATE" => GetMessage("YNSIR_COLUMN_MODIFIED_DATE"),
        );


        return $arResult;
    }

    public static function getSectionFieldsJobOrder()
    {
        return array(
            YNSIRConfig::OS_BASIC_INFO => array(
                'FIELD' => array(
                    'TITLE' => GetMessage("YNSIR_JO_TITLE"),
                    'HEADCOUNT' => GetMessage("YNSIR_JO_HEADCOUNT"),
                    'DEPARTMENT' => GetMessage("YNSIR_JO_DEPARTMENT"),
                    'SUPERVISOR' => GetMessage("YNSIR_JO_SUPERVISOR"),
                    'SUBORDINATE' => GetMessage("YNSIR_JO_SUBORDINATE"),
                    'EXPECTED_END_DATE' => GetMessage("YNSIR_JO_EXPECTED_END_DATE"),
                    'STATUS' => GetMessage("YNSIR_JO_STATUS"),
                    'OWNER' => GetMessage("YNSIR_JO_OWNER"),
                    'RECRUITER' => GetMessage("YNSIR_JO_RECRUITER"),
                    'LEVEL' => GetMessage("YNSIR_JO_LEVEL"),
                ),
                'REQUIRE' => array(
                    'TITLE', 'HEADCOUNT', 'DEPARTMENT', 'SUPERVISOR', 'EXPECTED_END_DATE', 'STATUS', 'OWNER', 'RECRUITER'
                ),
                'NAME' => GetMessage("YNSIR_OS_BASIC_INFO"),
            ),
            YNSIRConfig::OS_SENSITIVE => array(
                'FIELD' => array(
                    'VACANCY_REASON' => GetMessage("YNSIR_JO_VACANCY_REASON"),
                    'SALARY_FROM' => GetMessage("YNSIR_JO_SALARY_FROM"),
                    'SALARY_TO' => GetMessage("YNSIR_JO_SALARY_TO"),
                    'NOTE_SALARY' => GetMessage("YNSIR_JO_NOTE_SALARY"),
                    'IS_REPLACE' => GetMessage("YNSIR_JO_TYPE")
                ),
                'REQUIRE' => array('VACANCY_REASON'),
                'NAME' => GetMessage("YNSIR_OS_SENSITIVE"),
            ),
            YNSIRConfig::OS_INTERVIEWS => array(
                'FIELD' => array(
                    'INTERVIEW_PARTICIPANT' => GetMessage("YNSIR_JO_INTERVIEW_PARTICIPANT"),
                    'INTERVIEW_NOTE' => GetMessage("YNSIR_JO_INTERVIEW_NOTE")
                ),
                'REQUIRE' => array(),
                'NAME' => GetMessage("YNSIR_OS_INTERVIEWS"),
            ),
            YNSIRConfig::OS_DESCRIPTION => array(
                'FIELD' => array(
                    'TEMPLATE_ID' => GetMessage("YNSIR_JO_TEMPLATE_ID"),
                    'DESCRIPTION' => GetMessage("YNSIR_JO_DESCRIPTION")
                ),
                'REQUIRE' => array(),
                'NAME' => GetMessage("YNSIR_OS_DESCRIPTION"),
            )
        );
    }

    public static function getFieldsJobOrder()
    {
        $arField = self::getSectionFieldsJobOrder();
        $arFieldJObOrder = array();
        foreach ($arField as $key => $fields) {
            foreach ($fields['FIELD'] as $k => $f) {
                $arFieldJObOrder[$k] = $f;
            }
        }
        return $arFieldJObOrder;
    }

    public static function getCandiateStatusLock()
    {
        return array(JOStatus::CDTATUS_HIRED, JOStatus::CDTATUS_REJECT);
    }

}

?>