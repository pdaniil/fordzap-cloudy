// ===================== ПОДТВЕРЖДЕНИЕ =====================
function updateConfirmation() {
    const sto = stoSelect.options[stoSelect.selectedIndex]?.text || '—';
    const datetime = selectedDateTimeInput.value || '—';
    let servicesHtml = '';
    let total = 0;
    selectedServices.forEach(s => {
        const price = Number(s.price) || 0;
        total += price; // count всегда 1
        const priceDisplay = price > 0 ? `~${price.toFixed(2)}` : '–';
        servicesHtml += `<tr><td>${s.name}</td><td>${priceDisplay}</td><td>${price.toFixed(2)}</td></tr>`;
    });
    confirmationData.innerHTML = `
        <table class="summary-table">
            <tr><td><strong>СТО:</strong></td><td>${sto}</td></tr>
            <tr><td><strong>Дата и время:</strong></td><td>${datetime}</td></tr>
            <tr><td colspan="2"><strong>Услуги:</strong></td></tr>
            <tr><td colspan="2">
                <table class="selected-services-table" style="width:100%;">
                    <thead><tr><th>Услуга</th><th>Цена</th><th>Сумма</th></tr></thead>
                    <tbody>${servicesHtml}</tbody>
                    <tfoot><tr><td colspan="2" style="text-align:right;"><strong>Итого:</strong></td><td><strong>${total.toFixed(2)} ₽</strong></td></tr></tfoot>
                </table>
            </td></tr>
        </table>
    `;
}

// ===================== АВТОЗАПОЛНЕНИЕ АВТО =====================
carSelect.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected && selected.value) {
        carModel.value = selected.dataset.model || '';
        carPlate.value = selected.dataset.plate || '';
        carVin.value = selected.dataset.vin || '';
        carYear.value = selected.dataset.year || '';
        carMileage.value = selected.dataset.mileage || '';
    } else {
        carModel.value = '';
        carPlate.value = '';
        carVin.value = '';
        carYear.value = '';
        carMileage.value = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (carSelect.options.length > 1) {
        const selectedOpt = carSelect.options[carSelect.selectedIndex];
        if (selectedOpt && selectedOpt.value) {
            carModel.value = selectedOpt.dataset.model || '';
            carPlate.value = selectedOpt.dataset.plate || '';
            carVin.value = selectedOpt.dataset.vin || '';
            carYear.value = selectedOpt.dataset.year || '';
            carMileage.value = selectedOpt.dataset.mileage || '';
        }
    }
    if (currentStep === 2) {
        const nextBtn = document.getElementById('next-step-2');
        if (nextBtn) nextBtn.disabled = (selectedServices.length === 0);
    }
});

// ===================== ОТПРАВКА ФОРМЫ =====================
document.getElementById('submitBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const stoId = stoSelect.value;
    const postId = -1; // автоматически
    const workerId = -1; // автоматически
    const datetime = selectedDateTimeInput.value;
    const name = clientName.value.trim();
    const phone = clientPhone.value.trim();
    const email = clientEmail.value.trim();
    const model = carModel.value.trim();
    const plate = carPlate.value.trim();
    const vin = carVin.value.trim();
    const year = parseInt(carYear.value) || 0;
    const mileage = parseInt(carMileage.value) || 0;

    if (!stoId) { messageDiv.className = 'error'; messageDiv.textContent = 'Выберите СТО'; return; }
    if (!datetime) { messageDiv.className = 'error'; messageDiv.textContent = 'Выберите дату и время'; return; }
    if (!name || !phone) { messageDiv.className = 'error'; messageDiv.textContent = 'Укажите имя и телефон'; return; }
    if (selectedServices.length === 0) { messageDiv.className = 'error'; messageDiv.textContent = 'Добавьте хотя бы одну услугу'; return; }

    const servicesData = selectedServices.map(s => ({ id: s.id, count: 1 })); // всегда 1
    const payload = {
        sto_id: stoId,
        post_id: postId,
        worker_id: workerId,
        datetime: datetime,
        client_name: name,
        client_phone: phone,
        client_email: email,
        car_model: model,
        car_plate: plate,
        car_vin: vin,
        car_year: year,
        car_mileage: mileage,
        services: servicesData
    };

    messageDiv.className = '';
    messageDiv.textContent = 'Отправка...';
    document.getElementById('submitBtn').disabled = true;

    fetch(apiBase + '?action=book', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(data => {
        if (data.status === 200) {
            messageDiv.className = 'success';
            messageDiv.textContent = '✅ Запись успешно создана! Номер записи: ' + data.data.record_id;
        } else {
            messageDiv.className = 'error';
            messageDiv.textContent = '❌ Ошибка: ' + (data.message || 'Неизвестная ошибка');
        }
        document.getElementById('submitBtn').disabled = false;
    })
    .catch(err => {
        messageDiv.className = 'error';
        messageDiv.textContent = '❌ Ошибка соединения: ' + err.message;
        document.getElementById('submitBtn').disabled = false;
    });
});