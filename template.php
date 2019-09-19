<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<?
if(is_array($arResult["item"])):
foreach($arResult["item"] as $arItem):?>


	<?if(strlen($arItem["link"])>0):?>
		<a href="<?=$arItem["link"]?>"><?=$arItem["title"]?></a>
	<?else:?>
		<?=$arItem["title"]?>
	<?endif;?>
	<p>
	<?echo $arItem["description"];?>
	</p>
	<br />
<?endforeach;
endif;?>
