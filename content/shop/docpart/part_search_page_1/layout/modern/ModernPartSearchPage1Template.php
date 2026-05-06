<?php
defined('_ASTEXE_') or die('No access');

require_once dirname(__DIR__) . '/PartSearchPage1TemplateInterface.php';
require_once dirname(__DIR__) . '/default/DefaultPartSearchPage1Template.php';

/**
 * Второй шаблон: адаптивная сетка (CSS Grid / Flex), тема акцента #4294ff.
 */
final class ModernPartSearchPage1Template implements PartSearchPage1TemplateInterface
{
	public function getId(): string
	{
		return 'modern';
	}

	public function getCmsOverrideCss(): string
	{
		$path = __DIR__ . '/modern.css';
		if (!is_readable($path)) {
			return '';
		}
		$css = (string) file_get_contents($path);
		// Переменные темы — на контейнере выдачи, не на :root (единый CSS шаблона CMS).
		$css = str_replace(':root {', '#products_area {', $css);
		// Явная область: все классы psp1-m* только внутри #products_area
		$css = preg_replace('/(?m)^(\s*)\./', '$1#products_area .', $css);

		$out = "/* part_search_page_1: template modern — переопределения под единый CSS CMS */\n" . $css;

		// Последний слой: !important + изоляция от глобальных flex/inline-block CMS (см. modern-layout-isolation.css).
		$iso = __DIR__ . '/modern-layout-isolation.css';
		if (is_readable($iso)) {
			$out .= "\n" . (string) file_get_contents($iso);
		}

		return $out;
	}

	public function getJsConfig(): array
	{
		$base = DefaultPartSearchPage1Template::baseConfig();

		return array_merge($base, array(
			'template' => 'modern',
			'colspan' => 9,
			'sorted' => ' psp1-m-head__cell--sorted',
			'th_manufacturer' => 'psp1-m-head__cell psp1-m-head__cell--brand',
			'th_article' => 'psp1-m-head__cell psp1-m-head__cell--sku',
			'th_name' => 'psp1-m-head__cell psp1-m-head__cell--title',
			'th_exist' => 'psp1-m-head__cell psp1-m-head__cell--stock',
			'th_time_to_exe' => 'psp1-m-head__cell psp1-m-head__cell--eta',
			'th_info' => 'psp1-m-head__cell psp1-m-head__cell--info',
			'th_price' => 'psp1-m-head__cell psp1-m-head__cell--price',
			'th_add_to_cart' => 'psp1-m-head__cell psp1-m-head__cell--cart',
			'th_color' => 'psp1-m-head__cell psp1-m-head__cell--brandline',
			'td_manufacturer' => 'psp1-m-offer__cell psp1-m-offer__cell--brand',
			'td_article' => 'psp1-m-offer__cell psp1-m-offer__cell--sku',
			'td_name' => 'psp1-m-offer__cell psp1-m-offer__cell--title',
			'td_exist' => 'psp1-m-offer__cell psp1-m-offer__cell--stock',
			'td_time_to_exe' => 'psp1-m-offer__cell psp1-m-offer__cell--eta',
			'td_info' => 'psp1-m-offer__cell psp1-m-offer__cell--info',
			'td_price' => 'psp1-m-offer__cell psp1-m-offer__cell--price',
			'td_add_to_cart' => 'psp1-m-offer__cell psp1-m-offer__cell--cart',
			'td_color' => 'psp1-m-offer__cell psp1-m-offer__cell--brandline',
			'products_table_block_caption' => 'psp1-m-section__title',
			'next_page_box' => 'psp1-m-pager',
			'btn_load_more' => 'psp1-m-btn psp1-m-btn--load',
			'fa_arrow_down' => 'fa fa-arrow-down',
			'div_show_data_checked' => 'psp1-m-toolbar__checkbox',
			'show_data_class' => 'psp1-m-toolbar__margin',
			'info_box' => 'psp1-m-info',
			'show_hide_button' => 'psp1-m-toggle-more',
			'hide_row' => 'psp1-m-offer',
			'hidden_row_class' => 'psp1-m-offer--hidden',
			'bread_crumbs_a' => 'psp1-m-link',
			'count_need_minus' => 'psp1-m-qty-btn',
			'count_need_plus' => 'psp1-m-qty-btn',
			'hidden' => 'psp1-m-hidden',
			'product_return_icon_value' => 'psp1-m-return psp1-m-return--value',
			'product_return_icon' => 'psp1-m-return',
			'product_return_icon_tooltip' => 'psp1-m-return__tip',
			'product_return_success' => 'psp1-m-return--ok',
			'product_return_error' => 'psp1-m-return--bad',
			'prefix_hide_row_by_id' => 'psp1-m-offer--fold-',
			'root_class' => 'psp1-m-root',
			'head_row_class' => 'psp1-m-offers-head',
			'wrap_offers_head_class' => 'psp1-m-offers-head-wrap',
			'wrap_offers_body_class' => 'psp1-m-offers-body',
			'section_block_class' => 'psp1-m-section',
			'section_title_class' => 'psp1-m-section__title',
			'offer_row_class' => 'psp1-m-offer',
			'empty_filter_class' => 'psp1-m-empty',
			'style_block_caption_padding_top' => '',
			'style_block_caption_border_top_none' => '',
			'style_block_caption_spare' => '',
			'style_next_page_cell' => '',
			'style_next_page_link' => '',
			'style_time_exe_stock_highlight' => '',
			'style_info_wrap_margin' => '',
			'style_article_link' => '',
			'style_cart_wrap' => '',
			'style_cart_icon_link' => '',
			'style_cart_counter_box' => '',
			'style_cart_qty_input' => '',
			'style_thumb_table_float' => '',
			'style_thumb_link_wrap' => '',
			'style_thumb_link' => '',
			'style_thumb_span' => '',
			'style_thumb_exist_cell' => '',
			'style_used_thumb' => '',
		));
	}
}
