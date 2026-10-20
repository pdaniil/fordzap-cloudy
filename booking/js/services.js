// ===================== УПРАВЛЕНИЕ УСЛУГАМИ =====================
let activeCategoryId = null; // ID категории, которая сейчас раскрыта

function loadServices(stoId) {
    if (!stoId) return;
    servicesContainer.innerHTML = '<div>Загрузка...</div>';
    fetch(apiBase + '?action=get_services&sto_id=' + stoId)
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.status === 200 && data.data.categories) {
                categoriesData = data.data.categories;
                renderServices(categoriesData);
                activeCategoryId = null;
            } else {
                servicesContainer.innerHTML = '<div class="error">Ошибка загрузки услуг: ' + (data.message || 'неизвестная') + '</div>';
            }
        })
        .catch(err => {
            servicesContainer.innerHTML = '<div class="error">Ошибка соединения: ' + err.message + '</div>';
        });
}

function renderServices(categories) {
    let html = '';
    categories.forEach((cat, index) => {
        const catId = 'cat-' + index;
        html += `<div class="category-block" data-category-id="${catId}" data-category-name="${cat.name}">`;
        html += `<div class="category-title" onclick="toggleCategory('${catId}')">${cat.name}</div>`;
        html += `<div class="category-services">`;
        cat.services.forEach(service => {
            const price = Number(service.price) || 0;
            const priceDisplay = price > 0 ? `~${price.toFixed(2)}` : '–';
            const isSelected = selectedServices.some(s => s.id == service.id);
            html += `<span class="service-item ${isSelected ? 'selected' : ''}" data-id="${service.id}" data-name="${service.name}" data-price="${price}" onclick="selectService(this)">${service.name} (${priceDisplay} ₽)</span>`;
        });
        html += `</div></div>`;
    });
    servicesContainer.innerHTML = html;
    document.querySelectorAll('.category-block').forEach(block => {
        block.classList.remove('active', 'search-active');
    });
    updateNextButton();
}

function toggleCategory(catId) {
    // Если кликнули на уже активную – сворачиваем
    if (activeCategoryId === catId) {
        document.querySelectorAll('.category-block').forEach(block => {
            block.classList.remove('active');
        });
        activeCategoryId = null;
        return;
    }
    // Сворачиваем все
    document.querySelectorAll('.category-block').forEach(block => {
        block.classList.remove('active');
    });
    // Раскрываем нужную
    const target = document.querySelector(`.category-block[data-category-id="${catId}"]`);
    if (target) {
        target.classList.add('active');
        activeCategoryId = catId;
    }
    // Сбрасываем поиск
    document.getElementById('serviceSearch').value = '';
    document.querySelectorAll('.category-block').forEach(block => {
        block.classList.remove('search-active');
        block.style.display = 'inline-block';
        block.querySelectorAll('.service-item').forEach(item => {
            item.style.display = 'inline-block';
        });
    });
}

function filterServices() {
    const query = document.getElementById('serviceSearch').value.toLowerCase().trim();
    document.querySelectorAll('.category-block').forEach(block => {
        block.classList.remove('active', 'search-active');
    });
    if (query === '') {
        document.querySelectorAll('.category-block').forEach(block => {
            block.style.display = 'inline-block';
            block.classList.remove('search-active');
            block.querySelectorAll('.service-item').forEach(item => {
                item.style.display = 'inline-block';
            });
        });
        activeCategoryId = null;
        return;
    }
    document.querySelectorAll('.category-block').forEach(block => {
        const services = block.querySelectorAll('.service-item');
        let found = false;
        services.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            if (name.includes(query)) {
                item.style.display = 'inline-block';
                found = true;
            } else {
                item.style.display = 'none';
            }
        });
        if (found) {
            block.style.display = 'inline-block';
            block.classList.add('search-active');
        } else {
            block.style.display = 'none';
        }
    });
}

function selectService(el) {
    const id = parseInt(el.dataset.id);
    const name = el.dataset.name;
    const price = parseFloat(el.dataset.price) || 0;
    const existing = selectedServices.find(s => s.id === id);
    if (existing) {
        selectedServices = selectedServices.filter(s => s.id !== id);
        el.classList.remove('selected');
    } else {
        selectedServices.push({ id, name, price, count: 1 });
        el.classList.add('selected');
    }
    renderSelectedServices();
    updateNextButton();

    // Очищаем поиск и сворачиваем всё
    document.getElementById('serviceSearch').value = '';
    document.querySelectorAll('.category-block').forEach(block => {
        block.classList.remove('active', 'search-active');
        block.style.display = 'inline-block';
        block.querySelectorAll('.service-item').forEach(item => {
            item.style.display = 'inline-block';
        });
    });
    activeCategoryId = null;
}

function renderSelectedServices() {
    let html = '';
    let total = 0;
    selectedServices.forEach((s, index) => {
        const price = Number(s.price) || 0;
        const priceDisplay = price > 0 ? `~${price.toFixed(2)}` : '–';
        total += price;
        html += `<tr>
            <td>${s.name}</td>
            <td>${priceDisplay}</td>
            <td><span class="remove-btn" data-index="${index}">✕</span></td>
        </tr>`;
    });
    selectedServicesBody.innerHTML = html;
    totalSpan.textContent = total.toFixed(2);

    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = parseInt(this.dataset.index);
            const removed = selectedServices[idx];
            selectedServices.splice(idx, 1);
            document.querySelectorAll('.service-item').forEach(el => {
                if (parseInt(el.dataset.id) === removed.id) {
                    el.classList.remove('selected');
                }
            });
            renderSelectedServices();
            updateNextButton();
        });
    });
}

function updateNextButton() {
    const nextBtn = document.getElementById('next-step-2');
    if (nextBtn) nextBtn.disabled = (selectedServices.length === 0);
}

// При смене СТО – сброс услуг
stoSelect.addEventListener('change', function() {
    selectedServices = [];
    renderSelectedServices();
    categoriesData = [];
    activeCategoryId = null;
    if (currentStep === 2) {
        loadServices(this.value);
    }
    updateNextButton();
});