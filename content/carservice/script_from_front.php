<?php ?>
<!-- ======= ДАННЫЕ ДЛЯ JS ======= -->
<script>
window.__SERVICES__ = <?= json_encode($js_services_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.__MAP_POINTS__ = <?= json_encode($map_points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<!-- ======= Яндекс.Карты (вставь свой API-ключ) ======= -->
<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=c801dcc3-dd07-47cd-b483-10b6e190ba8e"></script>

<!-- ======= ЛОГИКА JS ======= -->
<script>
(function(){
    const money = v => (typeof v === 'number' && isFinite(v)) ? (Math.round(v).toLocaleString('ru-RU') + ' ₽') : '—';

    function computePriceText(price, wp){
        if (String(wp) === '1' || !(parseFloat(price) > 0)) return 'Цену рассчитает мастер';
        return money(parseFloat(price));
    }

    function getStoRoot(stoId){
        return document.getElementById('sto_'+stoId);
    }

    function setActiveSto(stoId){
        document.querySelectorAll('.sto-block').forEach(el => el.classList.remove('active'));
        const root = getStoRoot(stoId);
        if (root) root.classList.add('active');
        // Сброс поиска и показа таблицы
        const wrap = document.getElementById('services_table_wrap_'+stoId);
        if (wrap) { wrap.style.display = 'none'; }
        const btn = document.querySelector('.add-service-btn[data-sto="'+stoId+'"]');
        if (btn) { btn.style.display = 'inline-block'; }
    }

    function updateTotal(stoId){
        const tbody = document.getElementById('selected_tbody_'+stoId);
        let sum = 0;
        tbody.querySelectorAll('tr').forEach(tr=>{
            const price = parseFloat(tr.getAttribute('data-price') || 'NaN');
            const wp = tr.getAttribute('data-wp') === '1';
            if (!wp && (price > 0)) sum += price;
        });
        document.getElementById('total_sum_'+stoId).textContent = money(sum);
    }

    function ensureSelectedRowEvents(stoId){
        const tbody = document.getElementById('selected_tbody_'+stoId);
        tbody.querySelectorAll('.selected-check').forEach(cb=>{
            cb.onchange = function(){
                const id = this.getAttribute('data-id');
                // Снять галочку в каталоге, если чек снят
                const catalogCb = document.querySelector('.svc-check[data-sto="'+stoId+'"][data-id="'+id+'"]');
                const recoCb = document.querySelector('.reco-check[data-sto="'+stoId+'"][data-id="'+id+'"]');
                if (!this.checked){
                    // Удаляем строку
                    const tr = tbody.querySelector('tr[data-id="'+id+'"]');
                    if (tr) tr.remove();
                    if (catalogCb) catalogCb.checked = false;
                    if (recoCb) recoCb.checked = false;
                    updateTotal(stoId);
                } else {
                    // если вдруг чек в выбранных включили обратно — включим в каталоге/реко
                    if (catalogCb) catalogCb.checked = true;
                    if (recoCb) recoCb.checked = true;
                    updateTotal(stoId);
                }
            };
        });
    }

    function addSelected(stoId, payload) {
		const tbody = document.getElementById('selected_tbody_' + stoId);
		const exists = tbody.querySelector('tr[data-id="' + payload.id + '"]');
		if (exists) return; // уже добавлена

		const tr = document.createElement('tr');
		tr.setAttribute('data-id', payload.id);
		tr.setAttribute('data-price', payload.price ?? '');
		tr.setAttribute('data-wp', payload.wp ? '1' : '0');

		tr.innerHTML = `
			<td><input type="checkbox" class="selected-check" data-id="${payload.id}" checked></td>
			<td class="service-cell">
				<div class="service-info">
					<strong class="service-title">${payload.caption}</strong>
					<div class="service-desc">${payload.desc || ''}</div>
					${payload.stickers || ''}
				</div>
			</td>
			<td class="price-cell">${computePriceText(payload.price, payload.wp)}</td>
		`;

		tbody.appendChild(tr);
		ensureSelectedRowEvents(stoId);
		updateTotal(stoId);
	}


    function removeSelected(stoId, id){
        const tbody = document.getElementById('selected_tbody_'+stoId);
        const tr = tbody.querySelector('tr[data-id="'+id+'"]');
        if (tr) tr.remove();
        updateTotal(stoId);
    }

    // Включение каталога (кнопка "Добавить услугу")
    function showServicesCatalog(stoId){
        const wrap = document.getElementById('services_table_wrap_'+stoId);
        const btn  = document.querySelector('.add-service-btn[data-sto="'+stoId+'"]');
        if (wrap) wrap.style.display = 'block';
        if (btn)  btn.style.display = 'none';
        // сброс поисковой строки
        const input = wrap.querySelector('.service-search');
        if (input){
            input.value = '';
            // показать все услуги
            wrap.querySelectorAll('.service-item').forEach(it=> it.style.display = 'flex');
            wrap.querySelectorAll('.group').forEach(g=> g.style.display = '');
        }
    }

    // Поиск по каталогу конкретного СТО
    function attachSearch(stoId){
        const input = document.querySelector('.service-search[data-sto="'+stoId+'"]');
        if (!input) return;
        input.oninput = function(){
            const q = this.value.trim().toLowerCase();
            const wrap = document.getElementById('services_table_wrap_'+stoId);
            const items = wrap.querySelectorAll('.service-item');
            items.forEach(item=>{
                const name = (item.getAttribute('data-name') || '').toLowerCase();
                item.style.display = (!q || name.includes(q)) ? 'flex' : 'none';
            });
            // Скрывать группы без видимых элементов
            wrap.querySelectorAll('.group').forEach(g=>{
                const visible = Array.from(g.querySelectorAll('.service-item')).some(it => it.style.display !== 'none');
                g.style.display = visible ? '' : 'none';
            });
        };
    }

    // Стикеры из серверного HTML (переносим при добавлении)
    function getStickerHTMLFromItem(el){
        const st = el.querySelector('.stickers');
        return st ? st.outerHTML : '';
    }

    // Каталог: обработчики галочек
    function attachCatalogChecks(stoId){
        const wrap = document.getElementById('services_table_wrap_'+stoId);
        if (!wrap) return;
        wrap.querySelectorAll('.svc-check').forEach(cb=>{
            cb.onchange = function(){
                const id = this.getAttribute('data-id');
                const caption = this.getAttribute('data-caption') || '';
                const desc = this.getAttribute('data-desc') || '';
                const price = this.getAttribute('data-price');
                const wp = this.getAttribute('data-wp') === '1';
                const itemEl = this.closest('.service-item');
                const stickers = getStickerHTMLFromItem(itemEl);

                if (this.checked){
                    addSelected(stoId, {id, caption, desc, price: price? parseFloat(price): NaN, wp, stickers});
                    // Скрыть каталог, показать только выбранные
                    const wrap = document.getElementById('services_table_wrap_'+stoId);
                    if (wrap) wrap.style.display = 'none';
                    const btn  = document.querySelector('.add-service-btn[data-sto="'+stoId+'"]');
                    if (btn)  btn.style.display = 'inline-block';
                } else {
                    removeSelected(stoId, id);
                }
            };
        });
    }

    // Рекомендованные: обработчики
    function attachRecoChecks(stoId){
        const list = document.getElementById('reco_list_'+stoId);
        if (!list) return;
        list.querySelectorAll('.reco-check').forEach(cb=>{
            cb.onchange = function(){
                const id = this.getAttribute('data-id');
                const caption = this.getAttribute('data-caption') || '';
                const desc = this.getAttribute('data-desc') || '';
                const price = this.getAttribute('data-price');
                const wp = this.getAttribute('data-wp') === '1';
                const itemEl = this.closest('.reco-item');
                const stickers = getStickerHTMLFromItem(itemEl);

                if (this.checked){
                    addSelected(stoId, {id, caption, desc, price: price? parseFloat(price): NaN, wp, stickers});
                } else {
                    removeSelected(stoId, id);
                }
                // чек в каталоге синхронизируем
                const catalogCb = document.querySelector('.svc-check[data-sto="'+stoId+'"][data-id="'+id+'"]');
                if (catalogCb) catalogCb.checked = this.checked;
            };
        });
    }

    // Кнопки "Добавить услугу"
    function attachAddButtons(){
        document.querySelectorAll('.add-service-btn').forEach(btn=>{
            btn.onclick = function(){
                const stoId = this.getAttribute('data-sto');
                showServicesCatalog(stoId);
            };
        });
    }

    // Радио-переключение СТО + карта
    function attachStoRadios(map){
        document.querySelectorAll('input[name="car_service"]').forEach(r=>{
            r.onchange = function(){
                const stoId = this.value;
                setActiveSto(stoId);
                const lat = parseFloat(this.dataset.lat);
                const lng = parseFloat(this.dataset.lng);
                if (map && !isNaN(lat) && !isNaN(lng)){
                    map.setCenter([lat, lng], 14);
                }
            };
        });
    }

    // Инициализация карты
   function initMapAndUI(){
    if (!window.ymaps){ 
        console.warn("Yandex Maps не загружен"); 
        initUI(null); 
        return; 
    }

    ymaps.ready(function(){
        const points = window.__MAP_POINTS__ || [];
        console.log("MAP POINTS:", points);

        // Определяем центр карты
        const first = document.querySelector('input[name="car_service"]:checked');
        let center = [55.751244, 37.618423]; // Москва по умолчанию
        if(first && first.dataset.lat && first.dataset.lng){
            center = [parseFloat(first.dataset.lat), parseFloat(first.dataset.lng)];
        }

        const map = new ymaps.Map('yandex-map', { center: center, zoom: 12 });

        // Добавляем точки на карту
        points.forEach(p=>{
            if(p.lat && p.lng){
                const lat = parseFloat(p.lat);
                const lng = parseFloat(p.lng);
                if(!isNaN(lat) && !isNaN(lng)){
                    const pm = new ymaps.Placemark([lat, lng], { 
                        balloonContent: `<strong>${p.name || ''}</strong><br>${p.address || ''}`
                    });
                    map.geoObjects.add(pm);
                } else {
                    console.warn("Некорректные координаты точки:", p);
                }
            } else {
                console.warn("Точка без lat/lng:", p);
            }
        });

        attachStoRadios(map);
        initUI(map);
    });
}  

    // Инициализация UI (после карты/без неё)
    function initUI(_map){
        // Поиск + чекбоксы каталога/реко
        Object.keys(window.__SERVICES__ || {}).forEach(stoId=>{
            attachSearch(stoId);
            attachCatalogChecks(stoId);
            attachRecoChecks(stoId);
        });
        attachAddButtons();

        // Начальное активное СТО
        const first = document.querySelector('input[name="car_service"]:checked');
        if (first) setActiveSto(first.value);
    }

    // GO
    initMapAndUI();
})();



</script>