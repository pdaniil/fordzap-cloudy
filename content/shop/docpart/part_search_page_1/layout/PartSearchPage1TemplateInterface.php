<?php
defined('_ASTEXE_') or die('No access');

/**
 * Шаблон вёрстки страницы проценки (режим группировки по товарам).
 * Стили CMS подключаются единым файлом — дополнительные правила только через {@see getCmsOverrideCss()}.
 */
interface PartSearchPage1TemplateInterface
{
	public function getId(): string;

	/**
	 * Дополнительный CSS для переопределения глобальных стилей CMS.
	 * Для шаблона по умолчанию — пустая строка (ничего не инжектируется).
	 * Для остальных — полный текст; селекторы должны быть ограничены #products_area (или наследоваться от него).
	 */
	public function getCmsOverrideCss(): string;

	/**
	 * @return array<string, string|int>
	 */
	public function getJsConfig(): array;
}
