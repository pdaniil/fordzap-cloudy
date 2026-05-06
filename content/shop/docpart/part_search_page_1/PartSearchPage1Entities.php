<?php
/**
 * Именованные сущности домена страницы проценки (режим группировки по товарам).
 * Используются для согласованности ключей фильтров, next_page(), имён блоков.
 */
defined('_ASTEXE_') or die('No access');

final class PartSearchPage1Entities
{
	public const SEARCH_OBJECT = 'search_object';
	public const PRODUCTS_ROOT = 'Products';

	public const KEY_REQUIRED = 'Required';
	public const KEY_SEARCH_NAME = 'SearchName';
	public const KEY_QUICK_ANALOGS = 'Quick_Analogs';
	public const KEY_ANALOGS = 'Analogs';
	public const KEY_POSSIBLE_REPLACEMENT = 'PossibleReplacement';
	public const KEY_SPARE_BOX = 'Spare_Box';
}
