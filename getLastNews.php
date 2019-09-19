<?php
if (!$_SERVER["DOCUMENT_ROOT"])
	$_SERVER["DOCUMENT_ROOT"] = '/home/bitrix/www';
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
?>
<?
$APPLICATION->IncludeComponent("bitrix:rss.show", "template1", Array(
	"SITE" => "https://lenta.ru/rss",
		"PORT" => "80",
		"PATH" => "",
		"QUERY_STR" => "",
		"OUT_CHANNEL" => "N",	// Находятся ли новости вне канала (обычно нет)
		"NUM_NEWS" => "5",	// Количество новостей для показа (0 - не ограничивать)
		"CACHE_TYPE" => "A",	// Тип кеширования
		"CACHE_TIME" => "3600",	// Время кеширования (сек.)
		"COMPONENT_TEMPLATE" => ".default",
		"URL" => "https://lenta.ru/rss",	// Адрес ленты rss
		"PROCESS" => "TEXT",	// Обработка содержимого rss канала
	),
	false
);
?>
