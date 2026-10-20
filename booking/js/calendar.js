// ===================== КАЛЕНДАРЬ И СЛОТЫ =====================
function toggleCalendar() {
    if (calendarContainer.style.display === 'block') {
        calendarContainer.style.display = 'none';
    } else {
        calendarContainer.style.display = 'block';
        renderCalendar(currentYear, currentMonth);
    }
}

function renderCalendar(year, month) {
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    today.setHours(0,0,0,0);

    calendarMonth.textContent = new Date(year, month).toLocaleString('ru', { month: 'long', year: 'numeric' });

    let gridHtml = '';
    const weekDays = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
    let startOffset = (firstDay === 0) ? 6 : firstDay - 1;
    for (let i = 0; i < 42; i++) {
        if (i < 7) {
            gridHtml += `<div class="day disabled" style="font-weight:bold;">${weekDays[i]}</div>`;
            continue;
        }
        const dayNum = i - startOffset - 7;
        if (dayNum < 1 || dayNum > daysInMonth) {
            gridHtml += `<div class="day disabled"></div>`;
            continue;
        }
        const dateObj = new Date(year, month, dayNum);
        const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6;
        const isPast = dateObj < today;
        let classes = 'day';
        if (isPast || isWeekend) classes += ' disabled';
        else classes += ' available';
        gridHtml += `<div class="${classes}" data-date="${year}-${String(month+1).padStart(2,'0')}-${String(dayNum).padStart(2,'0')}">${dayNum}</div>`;
    }
    calendarGrid.innerHTML = gridHtml;

    document.querySelectorAll('.calendar-grid .day.available').forEach(el => {
        el.addEventListener('click', function() {
            const dateStr = this.dataset.date;
            selectedDate = dateStr;
            document.querySelectorAll('.calendar-grid .day').forEach(d => d.classList.remove('selected'));
            this.classList.add('selected');
            loadSlots(dateStr);
        });
    });
}

function loadSlots(dateStr) {
    const stoId = stoSelect.value;
    const postId = -1; // автоматически
    if (!stoId) {
        slotsList.innerHTML = '<div style="color:red;">Выберите СТО</div>';
        return;
    }
    slotsList.innerHTML = '<div>Загрузка...</div>';
    fetch(apiBase + `?action=get_slots&sto_id=${stoId}&post_id=${postId}&date=${dateStr}`)
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.status === 200 && data.data.slots) {
                const slots = data.data.slots;
                if (slots.length === 0) {
                    slotsList.innerHTML = '<div>Нет свободных слотов</div>';
                } else {
                    let html = '';
                    slots.forEach(slot => {
                        html += `<button class="slot-btn" data-time="${slot}">${slot}</button>`;
                    });
                    slotsList.innerHTML = html;
                    document.querySelectorAll('.slot-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                            this.classList.add('selected');
                            selectedSlot = this.dataset.time;
                            selectedDateTimeInput.value = `${dateStr} ${selectedSlot}`;
                        });
                    });
                }
            } else {
                slotsList.innerHTML = '<div>Ошибка загрузки слотов: ' + (data.message || '') + '</div>';
            }
        })
        .catch(err => {
            slotsList.innerHTML = '<div>Ошибка соединения: ' + err.message + '</div>';
        });
}

document.getElementById('prevMonthBtn').addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar(currentYear, currentMonth);
});
document.getElementById('nextMonthBtn').addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    renderCalendar(currentYear, currentMonth);
});