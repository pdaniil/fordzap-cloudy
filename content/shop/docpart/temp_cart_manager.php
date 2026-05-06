

<!-- Шаблон строки таблицы (будет клонироваться) -->
<template id="shopCartItemTemplate">
    <tr class="shop-cart-table__row">
        <td class="shop-cart-table__cell shop-cart-table__cell--name">
            <span class="shop-cart-item__name"></span>
        </td>
        <td class="shop-cart-table__cell shop-cart-table__cell--price">
            <span class="shop-cart-item__price"></span> ₽
        </td>
        <td class="shop-cart-table__cell shop-cart-table__cell--quantity">
            <span class="shop-cart-item__quantity"></span> шт.
        </td>
		 <td class="shop-cart-table__cell shop-cart-table__cell--total">
            <span class="shop-cart-item__total"></span>  ₽
        </td>
        <td class="shop-cart-table__cell shop-cart-table__cell--actions">
            <button class="shop-cart-item__delete-btn">
                Удалить
            </button>
        </td>
    </tr>
</template>

<!-- Стили -->
<style>
/* Базовые стили для iframe */
body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: transparent;
}

/* Иконка корзины - фиксированная внутри iframe */
.shop-cart-icon {
    position: fixed;
    left: 20px;
    bottom: 20px;
    z-index: 1000;
    cursor: pointer;
    transition: all 0.3s ease;
	display: block !important;
}

.shop-cart-icon:hover {
    transform: translateY(-2px);
}

.shop-cart-icon__wrapper {
    position: relative;
    background: #2c3e50; /* Темно-синий */
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    border: 2px solid #3498db; /* Синяя рамка */
    transition: all 0.3s ease;
}

.shop-cart-icon__wrapper:hover {
    background: #34495e;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    border-color: #2980b9;
}

.shop-cart-icon__svg {
    color: #ecf0f1; /* Светло-серый */
    width: 22px;
    height: 22px;
}

.shop-cart-icon__counter {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e74c3c; /* Красный для счетчика */
    color: white;
    font-size: 11px;
    font-weight: 600;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 1px solid white;
}

/* Модальное окно - позиционируем относительно iframe */
.shop-cart-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1001;
}

.shop-cart-modal--open {
    display: block !important;
}

.shop-cart-modal__overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(44, 62, 80, 0.85); /* Темно-синий с прозрачностью */
    backdrop-filter: blur(1px);
}

.shop-cart-modal__content {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    width: 85%;
    max-width: 750px;
    max-height: 75vh;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    border: 1px solid #bdc3c7;
}

/* Заголовок модалки */
.shop-cart-modal__header {
    background: #2c3e50; /* Темно-синий */
    color: white;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #34495e;
}

.shop-cart-modal__title {
    margin: 0;
    font-size: 18px;
    font-weight: 500;
    letter-spacing: 0.5px;
	color: white;
}

.shop-cart-modal__close-btn {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}

.shop-cart-modal__close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    transform: rotate(90deg);
}

.shop-cart-modal__close-btn svg {
    width: 12px;
    height: 12px;
}

/* Тело модалки */
.shop-cart-modal__body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

/* Таблица */
.shop-cart-table {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 20px;
    border: 1px solid #dfe6e9;
    border-radius: 4px;
    background: #fff;
}

.shop-cart-table__wrapper {
    width: 100%;
    border-collapse: collapse;
    min-width: 550px;
}

.shop-cart-table__header {
    background: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 1;
    border-bottom: 2px solid #3498db; /* Синяя линия */
}

.shop-cart-table__header-row {
    height: 40px;
}

.shop-cart-table__header-cell {
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    font-size: 13px;
    text-transform: none;
    letter-spacing: 0.3px;
    font-family: inherit;
    border-bottom: 1px solid #dfe6e9;
}

.shop-cart-table__header-cell--name {
    width: 40%;
    padding-left: 16px;
}

.shop-cart-table__header-cell--price,
.shop-cart-table__header-cell--quantity {
    width: 20%;
}

.shop-cart-table__header-cell--actions {
    width: 20%;
    text-align: center;
}

.shop-cart-table__body {
    background: white;
}

.shop-cart-table__row {
    height: 50px;
    border-bottom: 1px solid #f1f2f6;
    transition: background-color 0.15s ease;
}

.shop-cart-table__row:hover {
    background-color: #f8f9fa;
}

.shop-cart-table__cell {
    padding: 12px;
    color: #2c3e50;
    font-size: 14px;
    vertical-align: middle;
}

.shop-cart-table__cell--name {
    padding-left: 16px;
    font-weight: 500;
}

.shop-cart-table__cell--price {
    color: #2c3e50;
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.shop-cart-table__cell--quantity {
    color: #7f8c8d;
}

.shop-cart-table__cell--actions {
    text-align: center;
    padding: 8px;
}

.shop-cart-table__empty-row {
    text-align: center;
    height: 120px;
}

.shop-cart-table__empty-cell {
    padding: 40px 20px;
    color: #95a5a6;
    font-size: 14px;
    font-style: italic;
}

/* Кнопка удаления */
.shop-cart-item__delete-btn {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s ease;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.shop-cart-item__delete-btn:hover {
    background: #c0392b;
    box-shadow: 0 2px 4px rgba(231, 76, 60, 0.2);
}

/* Панель управления */
.shop-cart-controls {
    border-top: 1px solid #dfe6e9;
    padding-top: 20px;
    background: #f8f9fa;
    margin: -20px;
    margin-top: 0;
    padding: 20px;
}

.shop-cart-controls__wrapper {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.shop-cart-controls__btn {
    padding: 10px 20px;
    border: 1px solid;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 140px;
    font-family: inherit;
    letter-spacing: 0.3px;
}

.shop-cart-controls__btn--primary {
    background: #3498db; /* Основной синий */
    color: white;
    border-color: #2980b9;
}

.shop-cart-controls__btn--primary:hover {
    background: #2980b9;
    border-color: #1c6ea4;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(52, 152, 219, 0.25);
}

.shop-cart-controls__btn--secondary {
    background: white;
    color: #2c3e50;
    border-color: #bdc3c7;
}

.shop-cart-controls__btn--secondary:hover {
    background: #f8f9fa;
    border-color: #95a5a6;
    transform: translateY(-1px);
}

.shop-cart-controls__btn--tertiary {
    background: transparent;
    color: #7f8c8d;
    border-color: #dfe6e9;
}

.shop-cart-controls__btn--tertiary:hover {
    background: #f8f9fa;
    border-color: #bdc3c7;
    color: #2c3e50;
}

/* Адаптивность */
@media (max-width: 768px) {
    .shop-cart-icon {
        left: 10px;
        bottom: 10px;
    }
    
    .shop-cart-icon__wrapper {
        width: 46px;
        height: 46px;
    }
    
    .shop-cart-icon__svg {
        width: 20px;
        height: 20px;
    }
    
    .shop-cart-icon__counter {
        font-size: 10px;
        min-width: 16px;
        height: 16px;
        top: -3px;
        right: -3px;
    }
    
    .shop-cart-modal__content {
        width: 95%;
        max-height: 85vh;
        top: 50%;
        transform: translate(-50%, -50%);
    }
    
    .shop-cart-table {
        min-width: 100%;
    }
    
    .shop-cart-table__wrapper {
        min-width: 100%;
        display: block;
        overflow-x: auto;
    }
    
    .shop-cart-controls__wrapper {
        flex-direction: column;
    }
    
    .shop-cart-controls__btn {
        min-width: 100%;
        margin-bottom: 8px;
    }
    
    .shop-cart-controls__btn:last-child {
        margin-bottom: 0;
    }
}

/* Для очень маленьких экранов */
@media (max-width: 480px) {
    .shop-cart-modal__content {
        width: 100%;
        height: 100%;
        max-height: 100%;
        border-radius: 0;
        top: 0;
        transform: translateX(-50%);
    }
    
    .shop-cart-modal__body {
        padding: 15px;
    }
    
    .shop-cart-table__cell {
        padding: 8px 6px;
        font-size: 13px;
    }
    
    .shop-cart-table__header-cell {
        padding: 8px 6px;
        font-size: 12px;
    }
}
</style>

<!-- Скрипт -->
<script>
class ShopCart {
    constructor() {
        this.endpoint = '/content/shop/order_process/ajax_orders_items_temp.php';
        this.initElements();
        this.bindEvents();
        this.loadCartData();
    }

    initElements() {
        // Основные элементы
        this.cartIcon = document.getElementById('shopCartIcon');
        this.cartCounter = document.getElementById('shopCartCounter');
        this.cartModal = document.getElementById('shopCartModal');
        this.tableBody = document.getElementById('shopCartTableBody');
        this.itemTemplate = document.getElementById('shopCartItemTemplate');
        
        // Кнопки
        this.addToOrderBtn = document.getElementById('shopCartAddToOrder');
        this.continueSearchBtn = document.getElementById('shopCartContinueSearch');
        this.modalCloseBtn = document.getElementById('shopCartModalClose');
        this.cartCloseBtn = document.getElementById('shopCartClose');
        
        // Данные корзины
        this.cartItems = [];
        this.totalItems = 0;
    }

    bindEvents() {
        // Открытие/закрытие модалки
        this.cartIcon.addEventListener('click', () => this.openModal());
        this.modalCloseBtn.addEventListener('click', () => this.closeModal());
        this.cartCloseBtn.addEventListener('click', () => this.closeModal());
        this.cartModal.addEventListener('click', (e) => {
            if (e.target.classList.contains('shop-cart-modal__overlay')) {
                this.closeModal();
            }
        });

        // Действия с корзиной
        this.addToOrderBtn.addEventListener('click', () => this.addToOrder());
        this.continueSearchBtn.addEventListener('click', () => this.continueSearch());

        // Обработка удаления через делегирование
        this.tableBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('shop-cart-item__delete-btn')) {
                const row = e.target.closest('.shop-cart-table__row');
                const itemId = row.dataset.itemId;
                this.removeItem(itemId);
            }
        });
    }

    async loadCartData() {
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_cart',
					order_id : sessionStorage.getItem('cp_order_id')
                })
            });
            
            if (!response.ok) throw new Error('Ошибка загрузки данных');
            
            const data = await response.json();
            
            if (data.success) {
                this.cartItems = data.items || [];
                this.updateCartDisplay();
            }
        } catch (error) {
            console.error('Ошибка загрузки корзины:', error);
        }
    }

    updateCartDisplay() {
        // Обновляем счетчик
        this.totalItems = this.cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
        this.cartCounter.textContent = this.totalItems;
        
        // Обновляем таблицу
        this.renderCartTable();
    }

    renderCartTable() {
        this.tableBody.innerHTML = '';
        
        if (this.cartItems.length === 0) {
            this.tableBody.innerHTML = `
                <tr class="shop-cart-table__empty-row">
                    <td colspan="4" class="shop-cart-table__empty-cell">Корзина пуста</td>
                </tr>
            `;
            return;
        }
        
        this.cartItems.forEach(item => {
            const row = this.itemTemplate.content.cloneNode(true);
            const rowElement = row.querySelector('.shop-cart-table__row');
            
            rowElement.dataset.itemId = item.id || item.product_id;
            row.querySelector('.shop-cart-item__name').textContent = item.t2_manufacturer + ' ' + item.t2_article + ' ' + item.t2_name || 'Без названия';
            row.querySelector('.shop-cart-item__price').textContent = item.price || 0;
            row.querySelector('.shop-cart-item__quantity').textContent = item.count_need || 1;
            row.querySelector('.shop-cart-item__total').textContent = item.price * item.count_need || 0;
            
            this.tableBody.appendChild(row);
        });
    }

    async addItem(aid) {
		
		//1. По списку учетных объектов определяем, в где находится объект товара (Запрошенные/Аналоги)
		var AID_Object = Products.All[aid];
		
		//2. Получаем сам объект товара
		var Product = new Object;
		if(AID_Object.isRequired == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.Required.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
				var Article = Products.Required.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isSearchName == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
				var Article = Products.SearchName.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.SearchName.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isAnalogs == true)
		{
			//Ищем объект товара в списке аналогов
			for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
				var Article = Products.Analogs.ProductsTypes[i].article;
				
				//Массив объектов товаров:
				var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isQuickAnalogs == true)
		{
			//Ищем объект товара в списке быстрых аналогов
			for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
				var Article = Products.Quick_Analogs.ProductsTypes[i].article;
				
				//Массив объектов товаров:
				var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isPossibleReplacement == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
				var Article = Products.PossibleReplacement.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isSpare_Box == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
				var Article = Products.Spare_Box.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}
		
		//2.1 Добавляем пометку о количестве
		if(document.getElementById("count_need_"+aid)){
			var count_need = parseInt(document.getElementById("count_need_"+aid).value);
			Product['count_need'] = count_need;
		}else{
			Product['count_need'] = Product['min_order'];
		}

		//2.2 Заменяем синоним на имя производителя переданное поставщиком
		if(Product['manufacturer_transferred']){
			var manufacturer_tmp = Product['manufacturer'];
			Product['manufacturer'] = Product['manufacturer_transferred'];
			Product['manufacturer_transferred'] = manufacturer_tmp;
		}

		//3. Данные в корзину можно класть сразу целым перечнем - поэтому приводим к массиву
		var product_objects = new Array;
		product_objects.push(Product);
		
		log('Объект добавленного в корзину товара:');
		log(Product);
		
        try {
            const response = await fetch("/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_orders_items_add_to_cart.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'add_item',
                    order_id : sessionStorage.getItem('cp_order_id'),
					product_objects: JSON.stringify(product_objects)
                })
            });
            
            if (!response.ok) throw new Error('Ошибка добавления товара');
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadCartData();
                return true;
            }
			else {
				alert(data.message);
			}
            return false;
        } catch (error) {
            console.error('Ошибка добавления товара:', error);
            return false;
        }
    }

    async removeItem(itemId) {
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'remove_item',
                    item_id: itemId
                })
            });
            
            if (!response.ok) throw new Error('Ошибка удаления товара');
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadCartData();
                return true;
            }
            return false;
        } catch (error) {
            console.error('Ошибка удаления товара:', error);
            return false;
        }
    }

    openModal() {
        this.cartModal.classList.add('shop-cart-modal--open');
        document.body.style.overflow = 'hidden';
    }

    closeModal() {
        this.cartModal.classList.remove('shop-cart-modal--open');
        document.body.style.overflow = '';
    }

    async addToOrder() {
        try {
			
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'add_to_order',
					order_id : sessionStorage.getItem('cp_order_id')
                })
            });
            
            if (!response.ok) throw new Error('Ошибка создания заказа');
            
            const data = await response.json();
            
           // Обработчик успешного добавления товара
			if (data.success) {
				window.parent.location = window.parent.location;
			} else {
				alert(data.message || 'Ошибка при создании заказа');
			}
        } catch (error) {
            console.error('Ошибка создания заказа:', error);
            alert('Произошла ошибка при создании заказа');
        }
    }

    continueSearch() {
        this.closeModal();
        // Здесь можно добавить логику для возврата к поиску
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Инициализация корзины при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.shopCart = new ShopCart();
    
    // Экспортируем методы для использования в других скриптах
    window.addToCart = (itemData) => window.shopCart.addItem(itemData);
    window.openCart = () => window.shopCart.openModal();
    window.closeCart = () => window.shopCart.closeModal(); 
});
</script>