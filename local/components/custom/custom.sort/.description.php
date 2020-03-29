<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("CUSTOM_SORT_COMPONENT_NAME_VALUE"),
	"DESCRIPTION" => GetMessage("CUSTOM_SORT_COMPONENT_DESCRIPTION_VALUE"),
	"ICON" => "/images/icon.gif",
	"SORT" => 100,
	"PATH" => array(
		"ID" => "custom.sort",
		"SORT" => 500,
		"NAME" => GetMessage("CUSTOM_SORT.PANEL_COMPONENTS_FOLDER_NAME"),
		"CHILD" => array(
			"ID" => GetMessage("CUSTOM_SORT_COMPONENT_TYPE_CONTENT_VALUE"),
			"NAME" => '',
			"SORT" => 500,
		)
	),
);
