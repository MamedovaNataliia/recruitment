<?php

IncludeModuleLangFile(__FILE__);

class YNSIRNotifier
{
	protected static $ERRORS = array();
	public static function Notify($addresseeID, $internalMessage, $externalMessage, $schemeTypeID, $tag = '')
	{
		self::ClearErrors();

		if(!(IsModuleInstalled('im') && CModule::IncludeModule('im')))
		{
			self::RegisterError('IM module is not installed.');
			return false;
		}

		if($addresseeID <= 0)
		{
			self::RegisterError('Addressee is not assigned.');
			return false;
		}

		$arMessage = array(
			'TO_USER_ID' => $addresseeID,
			'FROM_USER_ID' => 0,
			'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
			'NOTIFY_MODULE' => 'ynsirecruitment',
			'NOTIFY_MESSAGE' => strval($internalMessage),
			'NOTIFY_MESSAGE_OUT' => strval($externalMessage)
		);

		$schemeTypeName = YNSIRNotifierSchemeType::ResolveName($schemeTypeID);
		if($schemeTypeName !== '')
		{
			$arMessage['NOTIFY_EVENT'] = $schemeTypeName;
		}

		$tag = strval($tag);
		if($tag !== '')
		{
			$arMessage['NOTIFY_TAG'] = $tag;
		}

		$msgID = CIMNotify::Add($arMessage);
		if(!$msgID)
		{
			$e = $GLOBALS['APPLICATION']->GetException();
			$errorMessage = $e ? $e->GetString() : 'Unknown sending error. message not send.';

			self::RegisterError($errorMessage);
			return false;
		}

		return true;
	}
	protected static function RegisterError($msg)
	{
		$msg = strval($msg);
		if($msg !== '')
		{
			self::$ERRORS[] = $msg;
		}
	}
	private static function ClearErrors()
	{
		if(!empty(self::$ERRORS))
		{
			self::$ERRORS = array();
		}
	}
	public static function GetLastErrorMessage()
	{
		return ($c = count(self::$ERRORS)) > 0 ? self::$ERRORS[$c - 1] : '';
	}
	public static function GetErrorMessages()
	{
		return self::$ERRORS;
	}
	public static function GetErrorCount()
	{
		return count(self::$ERRORS);
	}
}

class YNSIRNotifierSchemeType
{
	const Undefined = 0;
	const IncomingEmail = 1;
	const WebForm = 4;
	const Callback = 5;
	const UpdateCandidateStatus = 6;
	const UpdateJobOrderStatus = 7;
	const Associate = 8;

	const IncomingEmailName = 'incoming_email';
	const WebFormName = 'webform';
	const CallbackName = 'callback';
	const UpdateCandidateStatusName = 'update_ca_status';
	const UpdateJobOrderStatusName = 'update_order_status';
	const AssociateName = 'associate';

	public static function ResolveName($typeID)
	{
		$typeID = intval($typeID);
		switch ($typeID)
		{
			case self::IncomingEmail:
				return self::IncomingEmailName;
			case self::WebForm:
				return self::WebFormName;
			case self::Callback:
				return self::WebFormName;
            case self::Associate:
				return self::AssociateName;
            case self::UpdateCandidateStatus:
				return self::UpdateCandidateStatusName;
            case self::UpdateJobOrderStatus:
				return self::UpdateJobOrderStatusName;
		}

		return '';
	}

	public static function PrepareNotificationSchemes()
	{
		return array(
			'ynsirecruitment' => array(
                self::AssociateName => array(
					'NAME' => GetMessage('YNSIR_NOTIFY_SCHEME_ASSOCIATE'),
                    "MAIL" => 'N',
                    "PUSH" => 'Y',
					'XMPP' => true,
				),
                self::UpdateJobOrderStatus => array(
					'NAME' => GetMessage('YNSIR_NOTIFY_SCHEME_UPDATE_CLOSE_JOB_ORDER'),
                    "MAIL" => 'N',
                    "PUSH" => 'Y',
					'XMPP' => true,
				),
                self::UpdateCandidateStatus => array(
					'NAME' => GetMessage('YNSIR_NOTIFY_SCHEME_UPDATE_ASSOCIATE_STATUS'),
                    "MAIL" => 'N',
                    "PUSH" => 'Y',
					'XMPP' => true,
				),
//				"post" => Array(
//					"NAME" => GetMessage('YNSIR_NOTIFY_SCHEME_LIVEFEED_POST')
//				),
//				'mention' => array(
//					'NAME' => GetMessage('YNSIR_NOTIFY_SCHEME_LIVEFEED_MENTION')
//				),
//				self::WebFormName => array(
//					'NAME' => GetMessage('YNSIR_NOTIFY_SCHEME_WEBFORM'),
//					"LIFETIME" => 86400*7
//				),
//				self::CallbackName => array(
//					'NAME' => GetMessage('YNSIR_NOTIFY_SCHEME_CALLBACK'),
//					"LIFETIME" => 86400*7
//				)
			),
		);
	}
}
