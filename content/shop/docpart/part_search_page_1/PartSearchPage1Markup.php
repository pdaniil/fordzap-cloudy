<?php
/**
 * Фасад: конфиг вёрстки берётся из активного шаблона {@see PartSearchPage1TemplateResolver}.
 * @deprecated Прямые обращения к классу допустимы для совместимости; предпочтительно использовать шаблоны в layout/.
 */
defined('_ASTEXE_') or die('No access');

require_once __DIR__ . '/layout/PartSearchPage1TemplateResolver.php';

final class PartSearchPage1Markup
{
	public static function forJs(): array
	{
		return PartSearchPage1TemplateResolver::resolve()->getJsConfig();
	}

	public static function emitJsAssignment(): string
	{
		$payload = json_encode(
			self::forJs(),
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		return 'var PartSearchPage1Markup = ' . $payload . ";\nvar M = PartSearchPage1Markup;\n";
	}
}
