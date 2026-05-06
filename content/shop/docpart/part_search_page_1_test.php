<?php
/**
 * Скрипт для страницы поиска автозапчастей по артикулу, вариант "Группировка по товарам"
 *
 * Как работает сортировка при первоначальной загрузке страницы:
 * Внутри группы позиции сортируются по цене, затем позиции с доставкой 0-2дн. поднимаются выше остальных.
 * Внешняя сортировка происходит по сроку первых позиций в группе.
 * Внутрення сортировка происходит внутри каждой группы позиции по выбранному полю, затем позиции с сроком доставки, который настраевается ниже, поднимаются выше остальных.
 * При смене сортировки внешняя сортировка происходит по первым позициям в группе.
 *
 * Шаблоны вёрстки: каталог part_search_page_1/layout/ (см. PartSearchPage1TemplateResolver).
 * Переключение: $DP_Config->part_search_page_1_template = 'default' | 'modern'; если не задано — 'default'.
 * Стили CMS подключаются одним файлом шаблона; нестандартные шаблоны добавляют только блок style с переопределениями (getCmsOverrideCss), без отдельных link.
 *
 * @see PartSearchPage1Entities
 */

defined('_ASTEXE_') or die('No access');

require_once __DIR__ . '/part_search_page_1/PartSearchPage1Markup.php';
require_once __DIR__ . '/part_search_page_1/PartSearchPage1Entities.php';

$__psp1_template = PartSearchPage1TemplateResolver::resolve();
$__psp1_override_css = $__psp1_template->getCmsOverrideCss();
if ($__psp1_override_css !== '') {
	$__psp1_tid = preg_replace('/[^a-z0-9_-]/i', '', $__psp1_template->getId());
	echo '<style type="text/css" id="psp1-layout-override-' . htmlspecialchars($__psp1_tid, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";
	echo $__psp1_override_css . "\n";
	echo '</style>' . "\n";
}
unset($__psp1_template, $__psp1_override_css, $__psp1_tid);
?>



<script>
<?php
require __DIR__ . '/part_search_page_1/emit_markup.inc.php';
require __DIR__ . '/part_search_page_1/01_init.inc.php';
require __DIR__ . '/part_search_page_1/02_bind_result.inc.php';
require __DIR__ . '/part_search_page_1/03_result_review.inc.php';
require __DIR__ . '/part_search_page_1/04_product_row_html.inc.php';
require __DIR__ . '/part_search_page_1/05_show_hide.inc.php';
require __DIR__ . '/part_search_page_1/06_sort.inc.php';
require __DIR__ . '/part_search_page_1/07_cart.inc.php';
?>
</script>

<?php
	if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' )
	{
		include($_SERVER["DOCUMENT_ROOT"] . '/content/shop/docpart/temp_cart_manager.php');
	}
?>
