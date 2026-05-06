<?php
defined('_ASTEXE_') or die('No access');

require_once dirname(__DIR__) . '/PartSearchPage1TemplateInterface.php';

/**
 * Исходная табличная вёрстка проценки (первый шаблон, обратная совместимость).
 */
final class DefaultPartSearchPage1Template implements PartSearchPage1TemplateInterface
{
	public function getId(): string
	{
		return 'default';
	}

	public function getCmsOverrideCss(): string
	{
		return '';
	}

	public function getJsConfig(): array
	{
		return self::baseConfig();
	}

	/**
	 * Базовая конфигурация (таблица, инлайн-стили как раньше).
	 *
	 * @return array<string, string|int>
	 */
	public static function baseConfig(): array
	{
		return array(
			'template' => 'default',
			'colspan' => 9,
			'sorted' => ' sorted',
			'th_manufacturer' => 'th_manufacturer',
			'th_article' => 'th_article',
			'th_name' => 'th_name',
			'th_exist' => 'th_exist',
			'th_time_to_exe' => 'th_time_to_exe',
			'th_info' => 'th_info',
			'th_price' => 'th_price',
			'th_add_to_cart' => 'th_add_to_cart',
			'th_color' => 'th_color',
			'td_manufacturer' => 'td_manufacturer',
			'td_article' => 'td_article',
			'td_name' => 'td_name',
			'td_exist' => 'td_exist',
			'td_time_to_exe' => 'td_time_to_exe',
			'td_info' => 'td_info',
			'td_price' => 'td_price',
			'td_add_to_cart' => 'td_add_to_cart',
			'td_color' => 'td_color',
			'products_table_block_caption' => 'products_table_block_caption',
			'next_page_box' => 'next_page_box',
			'btn_load_more' => 'btn btn-ar btn-primary',
			'fa_arrow_down' => 'fa fa-arrow-down',
			'div_show_data_checked' => 'div_show_data_checked',
			'show_data_class' => 'show_data_class',
			'info_box' => 'info_box',
			'show_hide_button' => 'show_hide_button',
			'hide_row' => 'hide_row',
			'hidden_row_class' => '',
			'bread_crumbs_a' => 'bread_crumbs_a',
			'count_need_minus' => 'count_need_minus',
			'count_need_plus' => 'count_need_plus',
			'hidden' => 'hidden',
			'product_return_icon_value' => 'product-return-icon-value',
			'product_return_icon' => 'product-return-icon',
			'product_return_icon_tooltip' => 'product-return-icon-tooltip',
			'product_return_success' => 'success',
			'product_return_error' => 'error',
			'id_all_table_products' => 'all_table_products',
			'id_products_area' => 'products_area',
			'id_show_data' => 'show_data',
			'id_select_the_groups_margin' => 'select_the_groups_margin',
			'prefix_next_page' => 'next_page_',
			'prefix_show_hide_button' => 'show_hide_button_',
			'prefix_hide_row_by_id' => 'hide_row_',
			'prefix_count_need' => 'count_need_',
			'prefix_product_img' => 'product_img_',
			'prefix_lightbox' => 'lightbox-product-',
			'txt_th_info' => 'Инфо',
			'txt_block_required_ok' => 'Запрошенный артикул',
			'txt_block_required_missing' => 'Запрошенный артикул не найден',
			'txt_block_quick_analogs' => 'Аналоги с быстрой доставкой',
			'txt_block_analogs' => 'Аналоги и заменители',
			'txt_block_search_name' => 'Товары найденные по наименованию',
			'txt_block_possible_replacement' => 'Возможные замены',
			'txt_block_spare_box' => 'В наличии на складе',
			'txt_show_more_rows' => 'Показать еще',
			'txt_hide_rows' => 'Скрыть',
			'txt_empty_after_filter' => 'Товары не найдены',
			'txt_show_more_paging' => ' Показать еще',
			'empty_filter_class' => '',
			'root_class' => '',
			'head_row_class' => '',
			'wrap_offers_head_class' => '',
			'wrap_offers_body_class' => '',
			'section_block_class' => '',
			'section_title_class' => '',
			'style_block_caption_padding_top' => 'padding-top: 70px;',
			'style_block_caption_border_top_none' => 'padding-top: 70px; border-top: 0;',
			'style_block_caption_spare' => 'border-top: 0;',
			'style_next_page_cell' => 'text-align:center; padding:0;',
			'style_next_page_link' => 'margin-top:15px; display:none;',
			'style_time_exe_stock_highlight' => 'background: #fff700; padding: 0px 5px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;',
			'style_info_wrap_margin' => 'margin: 3px 0px;',
			'style_article_link' => 'text-decoration:underline; color:#000;',
			'style_cart_wrap' => 'text-align: center; line-height: 1em;',
			'style_cart_icon_link' => 'font-size: 20px; padding-right: 3px;',
			'style_cart_counter_box' => 'text-align: center; background: #f9f9f9; border: 1px solid #ddd; padding: 2px 1px; border-radius: 3px; font-size: 11px; margin-bottom: 1px;',
			'style_cart_qty_input' => 'text-align:center; width:24px; font-size: 10px; height: 14px; margin: 0px 2px; vertical-align: middle; border: 1px solid #ddd; border-radius: 2px !important; box-shadow: none; -webkit-appearance: none; -moz-appearance: none; appearance: none;',
			'style_thumb_table_float' => 'float: right;',
			'style_thumb_link_wrap' => 'text-align: right;',
			'style_thumb_link' => 'display:none; line-height: 0; border: 1px solid #ddd; padding: 2px; margin-right: 2px; border-radius: 3px; cursor:pointer;',
			'style_thumb_span' => 'width: 40px; height: 28px; display: inline-block; background-size: cover; background-position: center center; cursor:pointer;',
			'style_thumb_exist_cell' => 'width: 45px; text-align: right;',
			'style_used_thumb' => 'width: 20px; height: 17px; border: 1px solid #ddd; border-radius: 4px; margin-left: 5px;',
			'sort_icon_base' => '/content/files/images/',
			'offer_row_class' => '',
		);
	}
}
