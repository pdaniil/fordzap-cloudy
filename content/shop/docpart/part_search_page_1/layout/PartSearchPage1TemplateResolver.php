<?php
defined('_ASTEXE_') or die('No access');

require_once __DIR__ . '/PartSearchPage1TemplateInterface.php';
require_once __DIR__ . '/default/DefaultPartSearchPage1Template.php';
require_once __DIR__ . '/modern/ModernPartSearchPage1Template.php';

/**
 * Выбор активного шаблона: $DP_Config->part_search_page_1_template ('default' | 'modern').
 * Если свойство не задано — используется {@see DefaultPartSearchPage1Template}.
 */
final class PartSearchPage1TemplateResolver
{
	public const LAYOUT_WEB_BASE = '/content/shop/docpart/part_search_page_1/layout';

	public static function resolve(): PartSearchPage1TemplateInterface
	{
		global $DP_Config;

		$id = 'default';
		if (isset($DP_Config->part_search_page_1_template) && is_string($DP_Config->part_search_page_1_template)) {
			$id = $DP_Config->part_search_page_1_template;
		}

		switch ($id) {
			case 'modern':
				return new ModernPartSearchPage1Template();
			case 'default':
			default:
				return new DefaultPartSearchPage1Template();
		}
	}
}
