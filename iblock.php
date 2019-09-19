<?php

/**
 *
 * @ Сергей Талызенков - 2018-06-09
 * @ tsv.core *
 * User: Sergey Talyzenkov
 * http://tsv.rivne.me
 */

namespace Tsv\Core;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * 	Набор инструментов для работы с инфоблоками
 */
class Iblock
{

	protected static $cacheTime = 9999999;
	public static $iblocks = [];
	public static $arIB = [];

	/**
	 * ???
	 */
	public static function getSectionFilterConfig($sectionsId)
	{
		$arFilter = array('IBLOCK_ID' => IB_ID_CATALOG, 'ID' => $sectionsId);
		$arSelectFields = array('UF_FILTER_CONFIG');
		$dbRes = \CIBlockSection::GetList($arOrder, $arFilter, $bIncCnt, $arSelectFields);
		$arRes = $dbRes->Fetch();
		return $arRes['UF_FILTER_CONFIG'];
	}

	/**
	 * ???
	 */
	public static function getSectionFirstLevel($sectionId)
	{
		$section = \Tsv\Core\Main::$arGlobals['SECTIONS'][$sectionId];
		if (!$section['DEPTH_LEVEL'])
			return;
		if ($section['DEPTH_LEVEL'] == '1')
			return $section;
		else
		{
			if ($section['IBLOCK_SECTION_ID'])
				return self::getSectionFirstLevel($section['IBLOCK_SECTION_ID']);
			else
				return;
		}
	}

	/**
	 * ???
	 */
	public static function resortSectionsByParents($arSections)
	{
		$arNew = [];
		foreach ($arSections as $arSection)
		{
			\Tsv\Core\Main::$arGlobals['arSectionsIdByCode'][$arSection['CODE']] = $arSection['ID'];
			if (!$arSection['IBLOCK_SECTION_ID'])
				$arSection['IBLOCK_SECTION_ID'] = 0;
			$arNew[$arSection['IBLOCK_SECTION_ID']][] = $arSection;
		}
		return $arNew;
	}

	/**
	 * 	Получение списка записей
	 * 	@param mixed[] $params
	 * 	  'select'  => ... // имена полей, которые необходимо получить в результате,PROPERTY_*, PROPERTY_[CODE]
	 *    'filter'  => ... // описание фильтра для WHERE и HAVING
	 *    'group'   => ... // явное указание полей, по которым нужно группировать результат
	 *    'order'   => ... // параметры сортировки
	 *    'limit'   => ... // количество записей
	 *    'offset'  => ... // смещение для limit
	 *    'runtime' => ... // динамически определенные поля
	 * 		"cache"=>array("ttl"=>3600, "cache_joins"=>true);
	 * 	@return array $arResult
	 * */
	public static function getElements($params = [])
	{
		\Bitrix\Main\Loader::includeModule('iblock');

		if (count($params))
			$params = array_change_key_case($params);



		//  5-я страница с записями, по 20 на страницу
		//'limit' => 20,
		//'offset' => 80
		//if (!count($ar['FILTER']));
		/*
		  'select' => array('CNT'),
		  'runtime' => array(
		  new Entity\ExpressionField('CNT', 'COUNT(*)')
		  )
		 * 		 */

		if (!$params['select'] || !is_array($params['select']))
			$params['select'] = ['*'];
		elseif (!in_array('ID', $params['select']))
		{
			$params['select'][] = 'ID';
		}

		$bGetProperty = $bGetAllProps = false;


		foreach ($params['select'] as $k => $v)
		{
			if ($v == 'PROPERTY_*')
			{
				$bGetAllProps = true;
				unset($params['select'][$k]);
			} elseif (strpos($v, 'PROPERTY_') !== false)
			{
				$bGetProperty = $v;
				unset($params['select'][$k]);
			}
		}

		if ($bGetProperty || $bGetAllProps)
		{
			if ($params['select'] != ['*'] && !in_array('IBLOCK_ID', $params['select']))
			{
				$params['select'][] = 'IBLOCK_ID';
			}
		}

		if (!$params['cache'])
		{
			$params['cache'] = ['ttl' => self::$cacheTime];
		}
		if ($params['filter']['=PROPERTY_CODE'])
		{
			$join = ['join_type' => 'LEFT'];
			$referenceField = new \Bitrix\Main\Entity\ReferenceField('PROPERTY', '\Tsv\Core\Property', ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID'], $join);
			$referenceFieldProp = new \Bitrix\Main\Entity\ReferenceField('PROPERTY_PROP', '\Bitrix\Iblock\PropertyTable', ['=this.PROPERTY.IBLOCK_PROPERTY_ID' => 'ref.ID'], $join);
			$params['runtime'] = [$referenceField, $referenceFieldProp];
			$params['select']['PROPERTY_CODE'] = 'PROPERTY_PROP.CODE';
			$params['select']['PROPERTY_VALUE'] = 'PROPERTY.VALUE';
			$params['select']['PROPERTY_TYPE'] = 'PROPERTY_PROP.PROPERTY_TYPE';
		}

		$ob = \Bitrix\Iblock\ElementTable::getList($params);
		//$arRes = $ob->fetchAll();
		while ($arRes = $ob->fetch())
		{
			$arResult[$arRes['ID']] = $arRes;

			if ($bGetProperty)
			{
				$pCode = substr($bGetProperty, 9);
			}

			if ($bGetProperty)
				$arPropFilter['CODE'] = $pCode;
			else
				$arPropFilter = false;


			$db = \CIBlockElement::GetProperty($arRes['IBLOCK_ID'], $arRes['ID'], array("sort" => "asc"), $arPropFilter);
			while ($ar = $db->Fetch())
			{
				$arResult[$arRes['ID']][$ar['CODE']] = $ar['VALUE'];
			}
		}

		return $arResult;
	}

	/**
	 * 	Получение списка разделов, ключ ID
	 * 	@param array $params =  ['filter' => ['ID' => $_POST['section']],'select' => ['LEFT_MARGIN','RIGHT_MARGIN']]
	 * 	@return array $result
	 */
	public static function getSections($ar)
	{
		\Bitrix\Main\Loader::includeModule('iblock');

		$params = [];
		foreach ($ar as $k => $v)
		{
			$params[strtolower($k)] = $v;
		}

		if (!$params['select'])
			$params['select'] = ['*'];
		if (!in_array('ID', $params['select']))
		{
			$params['select'][] = 'ID';
		}
		if (!$params['cache'])
		{
			$params['cache'] = ['ttl' => self::$cacheTime];
		}

		$ob = \Bitrix\Iblock\SectionTable::getList($params);

		while ($res = $ob->fetch())
		{
			$result[$res['ID']] = $res;
		}
		if (count($result) == 1)
		{
			$result = array_shift($result);
		}
		return $result;
	}

	/**
	 * 	Получение списка активных свойст инфолблока с ключами CODE
	 * 	@return array $result
	 */
	public static function getIbProperties($IBID, $sort = array("sort" => "asc", "name" => "asc"))
	{
		\Bitrix\Main\Loader::includeModule('iblock');
		if (!$IBID)
			return;

		$db = \CIBlockProperty::GetList($sort, Array("ACTIVE" => "Y", "IBLOCK_ID" => $IBID));
		while ($res = $db->GetNext())
		{
			$result[$res["CODE"]] = $res["ID"];
		}
		return $result;
	}

	/**
	 * 	Получение значения списка пользовательского поля
	 * 	@return array $result
	 */
	public static function getUfPropertiesEnum()
	{
		\Bitrix\Main\Loader::includeModule('iblock');
		$db = \CUserFieldEnum::GetList();
		while ($res = $db->fetch())
		{
			$result[$res['ID']] = $res;
		}
		return $result;
	}

	/**
	 * 	Получение списка ID инфоблоков c ключами NAME
	 * 	@return array $result
	 */
	public static function getIbIdsByNames()
	{
		$result = [];
		if (!count(self::$arIB))
		{
			self::getIb();
		}
		foreach (self::$arIB as $ar)
		{
			if ($ar['NAME'])
			{
				$result[$ar['NAME']] = $ar['ID'];
			}
		}
		return $result;
	}

	/**
	 * 	Получение списка ID инфоблоков c ключами CODE
	 * 	getIbIdsByCodes($groupByIblockType = 0)
	 * 	@return array $result[$ar['CODE']] = $ar['ID'];
	 */
	public static function getIbIdsByCodes($groupByIblockType = 0)
	{
		$result = [];
		if (!count(self::$iblocks))
		{
			self::getIb();
		}
		foreach (self::$iblocks as $ar)
		{
			if ($ar['CODE'])
			{
				if ($groupByIblockType && $ar['IBLOCK_TYPE_ID'])
				{
					$result[$ar['IBLOCK_TYPE_ID']][$ar['CODE']] = $ar['ID'];
				} else
				{
					$result[$ar['CODE']] = $ar['ID'];
				}
			}
		}
		return $result;
	}

	/**
	 * 	Получение списка инфоблоков
	 * if (!$params['select'])
	 * $params['select'] = ['ID', 'CODE', 'NAME', 'SORT','IBLOCK_TYPE_ID'];
	 * 	@param array $params
	 * 	@return array self::$iblocks[$ar['ID']] = $ar;
	 */
	public static function getIb($params = [])
	{
		if (!count(self::$iblocks))
		{
			\Bitrix\Main\Loader::includeModule('iblock');
			if (!$params['select'])
				$params['select'] = ['ID', 'CODE', 'NAME', 'SORT', 'IBLOCK_TYPE_ID'];
			if (!in_array('IBLOCK_TYPE_ID', $params['select']))
				$params['select'][] = 'IBLOCK_TYPE_ID';

			if (!$params['cache'])
			{
				$params['cache'] = ['ttl' => self::$cacheTime];
			}
			$ob = \Bitrix\Iblock\IblockTable::getList($params);
			while ($ar = $ob->fetch())
			{
				self::$iblocks[$ar['ID']] = $ar;
			}
		}
		return self::$iblocks;
	}

}
