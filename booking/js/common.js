// ===================== ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ =====================
let currentStep = 1;
let selectedServices = [];
let selectedDate = null;
let selectedSlot = null;
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let categoriesData = [];

// DOM элементы
const stoSelect = document.getElementById('sto_id');
const postSelect = document.getElementById('post_id'); // не используется, но сохраняем
const workerSelect = document.getElementById('worker_id'); // не используется
const servicesContainer = document.getElementById('servicesContainer');
const selectedServicesBody = document.getElementById('selectedServicesBody');
const totalSpan = document.getElementById('totalAmount');
const calendarContainer = document.getElementById('calendarContainer');
const calendarGrid = document.getElementById('calendarGrid');
const calendarMonth = document.getElementById('calendarMonth');
const slotsList = document.getElementById('slotsList');
const selectedDateTimeInput = document.getElementById('selectedDateTime');
const messageDiv = document.getElementById('message');
const clientName = document.getElementById('clientName');
const clientPhone = document.getElementById('clientPhone');
const clientEmail = document.getElementById('clientEmail');
const carSelect = document.getElementById('carSelect');
const carModel = document.getElementById('carModel');
const carPlate = document.getElementById('carPlate');
const carVin = document.getElementById('carVin');
const carYear = document.getElementById('carYear');
const carMileage = document.getElementById('carMileage');
const confirmationData = document.getElementById('confirmationData');

// ===================== НАВИГАЦИЯ ПО ШАГАМ =====================
function showStep(step) {
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    const stepMap = {1:'step-sto',2:'step-services',4:'step-datetime',5:'step-confirm'};
    // Если шаг 3 пропущен, перенаправляем на 4
    if (step === 3) step = 4;
    document.getElementById(stepMap[step]).classList.add('active');
    currentStep = step;
    if (step === 5) updateConfirmation();
    if (step === 2 && categoriesData.length === 0) {
        loadServices(stoSelect.value);
    }
    const nextBtn = document.getElementById('next-step-2');
    if (nextBtn) {
        nextBtn.disabled = (selectedServices.length === 0);
    }
}

// Автоматический переход, если только одно СТО
document.addEventListener('DOMContentLoaded', function() {
    if (typeof stoListCount !== 'undefined' && stoListCount === 1) {
        // Выбираем единственное СТО (уже выбрано по умолчанию) и переходим на услуги
        showStep(2);
        // Загружаем услуги для этого СТО
        loadServices(stoSelect.value);
    }
});

// Обработчики кнопок
document.getElementById('next-step-1').addEventListener('click', function() {
    if (!stoSelect.value) { alert('Выберите СТО'); return; }
    showStep(2);
});
document.getElementById('prev-step-2').addEventListener('click', function() { showStep(1); });
document.getElementById('next-step-2').addEventListener('click', function() {
    if (selectedServices.length === 0) {
        alert('Добавьте хотя бы одну услугу');
        return;
    }
    showStep(4); // сразу на дату и время
});
document.getElementById('prev-step-4').addEventListener('click', function() { showStep(2); });
document.getElementById('next-step-4').addEventListener('click', function() {
    if (!selectedDateTimeInput.value) {
        alert('Выберите дату и время');
        return;
    }
    showStep(5);
});
document.getElementById('prev-step-5').addEventListener('click', function() { showStep(4); });