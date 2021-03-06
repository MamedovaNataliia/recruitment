<?php
namespace Bitrix\YNSIR\Search;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Main\NotSupportedException;

class YNSIRSearchMap
{
	private $data = array();
	public function add($value)
	{
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		$value = YNSIRSearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addField(array $fields, $name)
	{
		$value = isset($fields[$name]) ? $fields[$name] : '';
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		$value = YNSIRSearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addText($value, $length = null)
	{
		if(!is_string($value))
		{
			$value = (string)$value;
		}

		if($length > 0)
		{
			$value = substr($value, 0, $length);
		}

		$value = YNSIRSearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addUserByID($userID)
	{
		if($userID <= 0)
		{
			return;
		}

		$dbResult = \CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array('ID'=> $userID),
			array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'))
		);

		$user = $dbResult->Fetch();
		if(!is_array($user))
		{
			return;
		}

		$value = \CUser::FormatName(
			\CSite::GetNameFormat(),
			$user,
			true,
			false
		);

		$value = YNSIRSearchEnvironment::prepareToken($value);
		if($value !== '' && !isset($this->data[$value]))
		{
			$this->data[$value] = true;
		}
	}
	public function addPhone($phone)
	{
		$phone = DuplicateCommunicationCriterion::normalizePhone($phone);
		if($phone === '')
		{
			return;
		}

		$length = strlen($phone);

		if($length >= 10 && substr($phone, 0, 1) === '7')
		{
			$altPhone = '8'.substr($phone, 1);
			if(!isset($this->data[$altPhone]))
			{
				$this->data[$altPhone] = true;
			}
		}

		//Right bound. We will stop when 3 digits are left.
		$bound = $length - 2;
		if($bound > 0)
		{
			for($i = 0; $i < $bound; $i++)
			{
				$key = substr($phone, $i);
				if(!isset($this->data[$key]))
				{
					$this->data[$key] = true;
				}
			}
		}
	}
	public function addEmail($email)
	{
		if($email === '')
		{
			return;
		}

		$keys = preg_split('/\W+/', $email, -1, PREG_SPLIT_NO_EMPTY);
		foreach($keys as $key)
		{
			$key = YNSIRSearchEnvironment::prepareToken($key);
			if(!isset($this->data[$key]))
			{
				$this->data[$key] = true;
			}
		}
	}
	public function addMultiFieldValue($typeID, $value)
	{
		if($typeID === \CCrmFieldMulti::PHONE)
		{
			$this->addPhone($value);
		}
		elseif($typeID === \CCrmFieldMulti::EMAIL)
		{
			$this->addEmail($value);
		}
		else
		{
			throw new NotSupportedException("Multifield type: '".$typeID."' is not supported in current context");
		}
	}
	public function addEntityMultiFields($entityTypeID, $entityID, array $typeIDs)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return;
		}

		$multiFields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues($entityTypeID, $entityID);
		foreach($typeIDs as $typeID)
		{
			if(!(\CCrmFieldMulti::IsSupportedType($typeID) && isset($multiFields[$typeID])))
			{
				continue;
			}

			foreach($multiFields[$typeID] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$this->addMultiFieldValue($typeID, $multiField['VALUE']);
				}
			}
		}

	}
	public function getString()
	{
		return implode(' ', array_keys($this->data));
	}
}