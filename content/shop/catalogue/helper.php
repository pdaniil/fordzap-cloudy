<?php

/**
 * Класс для рендеринга блока товара
 * Поддерживает все 7 типов отображения (1–7)
 */
class ProductBlockRenderer
{
    /**
     * Основной метод – определяет тип и вызывает соответствующий метод рендеринга
     *
     * @param array $product Данные товара
     */
    public function render(array $product)
    {
        global $db_link;

        // Подготовка изображения (общая для всех типов)
        $this->prepareImage($product, $db_link);

        // Добавление бэкенд-класса для типов 2 и 3
        if ($product['product_block_type'] == 2 || $product['product_block_type'] == 3) {
            $product['main_class_of_block'] .= ' backend_product_box';
        }

        // Вызов соответствующего метода в зависимости от типа
        switch ($product['product_block_type']) {
            case 1:
                $this->renderType1($product);
                break;
            case 2:
                $this->renderType2($product);
                break;
            case 3:
                $this->renderType3($product);
                break;
            case 4:
                $this->renderType4($product);
                break;
            case 5:
                $this->renderType5($product);
                break;
            case 6:
                $this->renderType6($product);
                break;
            case 7:
                $this->renderType7($product);
                break;
            default:
                // fallback – выводим как тип 1
                $this->renderType1($product);
        }
    }

    // ---------- Методы для каждого типа блока ----------

    /**
     * Тип 1: Отображение блоков товаров одной категории для покупателя
     */
    protected function renderType1($product)
    {
        $this->renderCommonHeader($product);
        $this->renderCustomerPrices($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
    }

    /**
     * Тип 2: Отображение блоков товаров одной категории для менеджера каталога
     */
    protected function renderType2($product)
    {
        $this->renderCommonHeader($product);
        $this->renderManagerCheckbox($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
    }

    /**
     * Тип 3: Отображение блоков товаров одной категории для кладовщика
     */
    protected function renderType3($product)
    {
        $this->renderCommonHeader($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
        // Виджет быстрого редактирования выводится после основного блока
        $this->renderStorekeeperQuickEdit($product);
    }

    /**
     * Тип 4: Отображение блоков товаров для покупателя при поиске по наименованию
     */
    protected function renderType4($product)
    {
        $this->renderCommonHeader($product);
        $this->renderCustomerPrices($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
    }

    /**
     * Тип 5: Блоки на главной, сопутствующие, похожие товары
     */
    protected function renderType5($product)
    {
        $this->renderCommonHeader($product);
        $this->renderCustomerPrices($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
    }

    /**
     * Тип 6: Страница закладок (особое поведение кнопки "Удалить")
     */
    protected function renderType6($product)
    {
        $this->renderCommonHeader($product, true); // true = тип закладок
        $this->renderCustomerPrices($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
    }

    /**
     * Тип 7: Страница сравнений (особое поведение кнопки "Удалить")
     */
    protected function renderType7($product)
    {
        $this->renderCommonHeader($product, false, true); // true = тип сравнений
        $this->renderCustomerPrices($product);
        $this->renderButtons($product);
        $this->closeBlock($product);
    }

    // ---------- Общие вспомогательные методы ----------

    /**
     * Подготавливает URL изображения (модифицирует переданный массив)
     */
    private function prepareImage(&$product, $db_link)
    {
        $im = trim(str_replace('/content/files/images/products_images/', '', $product['image']));
        if (!empty($im)) {
            $sql = "SELECT `id` FROM `shop_products_images` WHERE `file_name` = ? LIMIT 1;";
            $query = $db_link->prepare($sql);
            $query->execute(array($im));
            $record = $query->fetch();
            $product['image'] = '/content/shop/catalogue/watermark.php?image_id=' . $record['id'];
        } else {
            $product['image'] = '/content/files/images/no_image.png';
        }
    }

    /**
     * Выводит общую "шапку" блока: изображение, название, стикеры, оценки, закладки, сравнения, админку
     *
     * @param array $product
     * @param bool  $isBookmarksPage  флаг, что мы на странице закладок (тип 6)
     * @param bool  $isComparePage    флаг, что мы на странице сравнений (тип 7)
     */
    private function renderCommonHeader($product, $isBookmarksPage = false, $isComparePage = false)
    {
        global $DP_Content;
        ?>
        <div class="<?php echo htmlspecialchars($product['main_class_of_block']); ?>">

            <?php if ($this->isImageVisible($product['main_class_of_block'])): ?>
                <?php $this->renderImage($product); ?>
            <?php endif; ?>
			
			 <div class="product_div_manufacturer">
				<a href="/parts/<?=htmlspecialchars($product['manufacturer']);?>/<?=htmlspecialchars($product['article']);?>/" target="_blank">
					<?php echo htmlspecialchars($product['manufacturer']); ?>
					<?php echo htmlspecialchars($product['article']); ?>
					<i class="fa fa-search"></i>
				</span>
			</div>
			
            <!-- Наименование -->
            <div class="product_div_name">
                <a title="<?php echo htmlspecialchars($product['caption']); ?>"
                   href="<?php echo htmlspecialchars($product['product_url']); ?>"
                   <?php echo ($DP_Content->main_flag) ? '' : 'target="_blank"'; ?>>
                    <div class="product_div_caption">
                        <?php echo htmlspecialchars($product['caption']); ?>
                    </div>
                </a>
            </div>

            <?php $this->renderStickers($product); ?>
            <?php $this->renderMarks($product); ?>
            <?php $this->renderBookmark($product, $isBookmarksPage); ?>
            <?php $this->renderCompare($product, $isComparePage); ?>
            <?php $this->renderAdmin($product); ?>
        <?php
    }

    /**
     * Закрывает блок (закрывающий тег)
     */
    private function closeBlock($product)
    {
        echo '</div>';
    }

    /**
     * Проверяет, нужно ли показывать изображение
     */
    private function isImageVisible($mainClass)
    {
        return (strpos($mainClass, 'product_div_tile') !== false ||
                strpos($mainClass, 'product_div_list_photo') !== false);
    }

    /**
     * Выводит HTML изображения
     */
    private function renderImage($product)
    {
        global $DP_Content;
        ?>
        <div class="product_div_image_wrap">
            <a title="<?php echo htmlspecialchars($product['caption']); ?>"
               href="<?php echo htmlspecialchars($product['product_url']); ?>"
               <?php echo ($DP_Content->main_flag) ? '' : 'target="_blank"'; ?>>
                <?php if (!empty($product['image']) && $product['image'] != '/content/files/images/products_images/') : ?>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>"
                         alt="<?php echo htmlspecialchars($product['caption']); ?>"
                         onerror="this.src='/content/files/images/no_image.png'"
                         border="0" />
                <?php else : ?>
                    <img src="/content/files/images/no_image.png"
                         alt="<?php echo htmlspecialchars($product['caption']); ?>"
                         border="0" />
                <?php endif; ?>
            </a>
        </div>
        <?php
    }

    /**
     * Выводит стикеры
     */
    private function renderStickers($product)
    {
        if (empty($product['stickers'])) return;
        ?>
        <div class="stickers">
        <?php foreach ($product['stickers'] as $sticker) : ?>
            <div <?php if ($sticker['description'] != '') echo 'title="' . htmlspecialchars($sticker['description']) . '"'; ?>
                 class="sticker <?php echo htmlspecialchars($sticker['class_css']); ?>"
                 style="background-color:<?php echo htmlspecialchars($sticker['color_background']); ?>;
                        color: <?php echo htmlspecialchars($sticker['color_text']); ?>;">
                <?php if ($sticker['href'] != '') : ?>
                    <a target="_blank"
                       style="color:<?php echo htmlspecialchars($sticker['color_text']); ?>;
                              border-bottom:1px dotted <?php echo htmlspecialchars($sticker['color_text']); ?>;"
                       href="<?php echo htmlspecialchars($sticker['href']); ?>">
                <?php endif; ?>
                    <?php echo htmlspecialchars($sticker['value']); ?>
                <?php if ($sticker['href'] != '') : ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Выводит блок оценок
     */
    private function renderMarks($product)
    {
        ?>
        <div title="Оценки товара, проголосовало пользователей: <?php echo $product['marks_count']; ?>"
             class="product_div_marks"
             rel="popover" data-html="true" data-toggle="popover"
             data-placement="bottom"
             data-content="<div><i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <?php echo $product['mark_1']; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i>  <?php echo $product['mark_2']; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i>  <?php echo $product['mark_3']; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i>  <?php echo $product['mark_4']; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i>  <?php echo $product['mark_5']; ?></div>">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <i class="fa <?php echo ($i + 1 <= $product['mark']) ? 'fa-star' : 'fa-star-o'; ?> em-primary"></i>
            <?php endfor; ?>
            <span class="product_div_marks_count hidden"><?php echo $product['marks_count']; ?></span>
        </div>
        <?php
    }

    /**
     * Выводит кнопку закладок
     *
     * @param array $product
     * @param bool  $isBookmarksPage true – на странице закладок (тип 6)
     */
    private function renderBookmark($product, $isBookmarksPage = false)
    {
        $in_bookmarks = false;
        if (isset($_COOKIE['bookmarks'])) {
            $bookmarks = json_decode($_COOKIE['bookmarks'], true);
            if (is_array($bookmarks) && array_search($product['id'], $bookmarks) !== false) {
                $in_bookmarks = true;
            }
        }

        if ($in_bookmarks) {
            if ($isBookmarksPage) {
                // Страница закладок – кнопка "Удалить"
                ?>
                <div class="product_div_bookmark">
                    <a href="javascript:void(0);"
                       onclick="removeBookmark(<?php echo $product['id']; ?>, this);"
                       title="Удалить из закладок">
                        <i class="fa fa-remove"></i>
                        <span>Удалить</span>
                    </a>
                </div>
                <?php
            } else {
                // Остальные страницы – ссылка на страницу закладок
                ?>
                <div class="product_div_bookmark">
                    <a href="javascript:void(0);"
                       onclick="location = '/shop/zakladki';"
                       title="Перейти в закладки">
                        <i class="fa fa-bookmark"></i>
                        <span>В закладках</span>
                    </a>
                </div>
                <?php
            }
        } else {
            // Добавление в закладки
            ?>
            <div class="product_div_bookmark">
                <a href="javascript:void(0);"
                   onclick="addToBookmarks(<?php echo $product['id']; ?>, this);"
                   title="Добавить в закладки">
                    <i class="fa fa-bookmark-o"></i>
                    <span>В закладки</span>
                </a>
            </div>
            <?php
        }
    }

    /**
     * Выводит кнопку сравнения
     *
     * @param array $product
     * @param bool  $isComparePage true – на странице сравнений (тип 7)
     */
    private function renderCompare($product, $isComparePage = false)
    {
        $in_compare = false;
        if (isset($_COOKIE['compare'])) {
            $compare = json_decode($_COOKIE['compare'], true);
            if (is_array($compare) && array_search($product['id'], $compare) !== false) {
                $in_compare = true;
            }
        }

        if ($in_compare) {
            if ($isComparePage) {
                // Страница сравнений – кнопка "Удалить"
                ?>
                <div class="product_div_compare">
                    <a href="javascript:void(0);"
                       onclick="removeCompare(<?php echo $product['id']; ?>, this);"
                       title="Удалить из сравнений">
                        <i class="fa fa-remove"></i>
                        <span>Удалить</span>
                    </a>
                </div>
                <?php
            } else {
                // Остальные страницы – ссылка на страницу сравнений
                ?>
                <div class="product_div_compare">
                    <a href="javascript:void(0);"
                       onclick="location = '/shop/sravneniya';"
                       title="На страницу сравненения товаров">
                        <i class="glyphicon glyphicon-duplicate"></i>
                        <span>В сравнениях</span>
                    </a>
                </div>
                <?php
            }
        } else {
            // Добавление к сравнению
            ?>
            <div class="product_div_compare">
                <a href="javascript:void(0);"
                   onclick="addToCompare(<?php echo $product['id']; ?>, this);"
                   title="Добавить к сравнению">
                    <i class="fa fa-copy fa-flip-horizontal"></i>
                    <span>К сравнению</span>
                </a>
            </div>
            <?php
        }
    }

    /**
     * Выводит ссылку для администратора (если залогинен)
     */
    private function renderAdmin($product)
    {
        if (DP_User::isAdmin()) {
            global $DP_Config;
            ?>
            <div class="product_div_admin">
                <a href="<?php echo $DP_Config->domain_path . $DP_Config->backend_dir; ?>/shop/catalogue/products/product?category_id=<?php echo $product['category_id']; ?>&product_id=<?php echo $product['id']; ?>"
                   target="_blank">
                    <i class="fa fa-external-link"></i>
                </a>
            </div>
            <?php
        }
    }

    /**
     * Выводит цены, наличие и скрытый блок для корзины (для покупательских типов)
     */
    private function renderCustomerPrices($product)
    {
        /*if (isset($product['exist_info_variant'])) {
            echo '<div class="product_div_exist_info">' . $product['exist_info_variant'] . '</div>';
        }*/
        if (isset($product['price']) && $product['price'] != '') {
            echo '<div class="product_div_price">' . $product['price'] . '</div>';
        }
        if (isset($product['price_crossed_out']) && $product['price_crossed_out'] != '') {
            echo '<div class="product_div_price_crossed_out">' . $product['price_crossed_out'] . '</div>';
        }
        if (isset($product['price_from_to']) && $product['price_from_to'] != '') {
            echo '<div class="product_div_price_from_to">' . $product['price_from_to'] . '</div>';
        }

        // Скрытый блок для корзины
        if (isset($product['cart_suggestion']) && isset($product['storage_data'][$product['cart_suggestion']])) {
            $s = $product['storage_data'][$product['cart_suggestion']];
            ?>
            <div id="product_object_<?php echo $product['id']; ?>" style="display:none">
                <div id="<?php echo $product['cart_suggestion']; ?>"
                     product_id="<?php echo $product['id']; ?>"
                     office_id="<?php echo $s['office_id']; ?>"
                     storage_id="<?php echo $s['storage_id']; ?>"
                     storage_record_id="<?php echo $s['record_id']; ?>"
                     price="<?php echo $s['customer_price']; ?>"
                     time_to_exe="<?php echo $s['time_to_exe']; ?>"
                     exist="<?php echo $s['exist']; ?>"
                     check_hash="<?php echo $s['check_hash']; ?>">
                </div>
            </div>
            <?php
        }
    }

    /**
     * Выводит чекбокс для менеджера каталога (тип 2)
     */
    private function renderManagerCheckbox($product)
    {
        ?>
        <div class="product_checkbox_div">
            <input class="product_checkbox"
                   product_id="<?php echo $product['id']; ?>"
                   product_caption="<?php echo str_replace(array('"', "'"), '', $product['caption']); ?>"
                   type="checkbox"
                   id="product_checkbox_<?php echo $product['id']; ?>" />
        </div>
        <?php
    }

    /**
     * Выводит кнопки article_button и main_action_div
     */
    private function renderButtons($product)
    {
        ?>
        <div class="main_action_div"><?php echo $product['button']; ?></div>
        <?php
    }

    /**
     * Выводит виджет быстрого редактирования для кладовщика (тип 3)
     */
    private function renderStorekeeperQuickEdit($product)
    {
        ?>
        <div class="product_price_quick_edit work_quick_edit"
             product_id="<?php echo $product['id']; ?>"
             id="work_quick_edit_<?php echo $product['id']; ?>">
        </div>
        <?php
    }
}


function printProductBlock($product)
{
    $renderer = new ProductBlockRenderer();
    $renderer->render($product);
}