<div class="car-modal-overlay" id="addCarModal">
  <div class="car-modal">
    <div class="car-modal__header">
      <h2 class="car-modal__title">Информация об авто</h2>
      <button class="car-modal__close-btn">&times;</button>
    </div>
    
    <div class="car-modal__body">
      <form class="car-form" id="addCarForm">
		<input type="hidden" name="car_id" id="carId" value="">
        <div class="car-form__group">
          <label for="carCaption" class="car-form__label">Название для себя *</label>
          <input 
            type="text" 
            id="carCaption" 
            name="caption" 
            class="car-form__input" 
            placeholder="Мой основной автомобиль" 
            required
          >
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="carMark" class="car-form__label">Марка *</label>
            <input 
              type="text" 
              id="carMark" 
              name="marka" 
              class="car-form__input" 
              placeholder="Toyota" 
              required
            >
          </div>

          <div class="car-form__group">
            <label for="carModel" class="car-form__label">Модель *</label>
            <input 
              type="text" 
              id="carModel" 
              name="model" 
              class="car-form__input" 
              placeholder="Camry" 
              required
            >
          </div>
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="manufactureYear" class="car-form__label">Год выпуска *</label>
            <select id="manufactureYear" name="year" class="car-form__select" required>
              <option value="">Выберите год</option>
            </select>
          </div>

          <!--<div class="car-form__group car-form__group--with-unit">
            <label for="mileage" class="car-form__label">Пробег *</label>
            <input 
              type="number" 
              id="mileage" 
              name="mileage" 
              class="car-form__input" 
              placeholder="0" 
              min="0" 
              required
            >
            <span class="car-form__unit">км</span>
          </div>-->
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="bodyType" class="car-form__label">Тип кузова</label>
            <select id="bodyType" name="body_type" class="car-form__select">
              <option value="">Выберите тип кузова</option>
              <option value="sedan">Седан</option>
              <option value="hatchback">Хэтчбек</option>
              <option value="station">Универсал</option>
              <option value="suv">Внедорожник</option>
              <option value="crossover">Кроссовер</option>
              <option value="coupe">Купе</option>
              <option value="cabriolet">Кабриолет</option>
              <option value="minivan">Минивэн</option>
              <option value="pickup">Пикап</option>
            </select>
          </div>

          <div class="car-form__group">
            <label for="engineValue" class="car-form__label">Объем двигателя</label>
            <input 
              type="text" 
              id="engineValue" 
              name="engine_value" 
              class="car-form__input" 
              placeholder="2.0 л"
            >
          </div>
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="fuelType" class="car-form__label">Тип топлива</label>
            <select id="fuelType" name="fuel_type" class="car-form__select">
              <option value="">Выберите тип топлива</option>
              <option value="pet">Бензин</option>
              <option value="dis">Дизель</option>
              <option value="hybrid">Гибрид</option>
              <option value="elect">Электричество</option>
              <option value="gas">Газ</option>
            </select>
          </div>

          <div class="car-form__group">
            <label for="transmission" class="car-form__label">Тип КПП</label>
            <select id="transmission" name="transmission" class="car-form__select">
              <option value="">Выберите КПП</option>
              <option value="mkpp">Механическая</option>
              <option value="akpp">Автоматическая</option>
              <option value="robot">Робот</option>
              <option value="variator">Вариатор</option>
            </select>
          </div>
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="vin" class="car-form__label">VIN *</label>
            <input 
              type="text" 
              id="vin" 
              name="vin" 
              class="car-form__input" 
              placeholder="Введите VIN номер" 
              required
            >
          </div>

          <div class="car-form__group">
            <label for="frame" class="car-form__label">FRAME</label>
            <input 
              type="text" 
              id="frame" 
              name="frame" 
              class="car-form__input" 
              placeholder="Номер кузова"
            >
          </div>
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="color" class="car-form__label">Цвет</label>
            <input 
              type="text" 
              id="color" 
              name="color" 
              class="car-form__input" 
              placeholder="Черный"
            >
          </div>

          <div class="car-form__group">
            <label for="country" class="car-form__label">Страна сборки</label>
            <input 
              type="text" 
              id="country" 
              name="country" 
              class="car-form__input" 
              placeholder="Япония"
            >
          </div>
        </div>

        <div class="car-form__row">
          <div class="car-form__group">
            <label for="wheel" class="car-form__label">Расположение руля</label>
            <select id="wheel" name="wheel" class="car-form__select">
              <option value="">Выберите расположение</option>
              <option value="left">Левый</option>
              <option value="right">Правый</option>
            </select>
          </div>

          <div class="car-form__group">
            <label for="license_plate" class="car-form__label">Гос номер</label>
            <input 
              type="text" 
              id="license_plate" 
              name="license_plate" 
              class="car-form__input" 
              placeholder="А123БВ777"
            >
          </div>
        </div>

        <div class="car-form__group">
          <label for="note" class="car-form__label">Примечание</label>
          <textarea 
            id="note" 
            name="note" 
            class="car-form__textarea" 
            placeholder="Дополнительная информация об автомобиле"
            rows="3"
          ></textarea>
        </div>

        <input type="hidden" name="active" value="1">
        <input type="hidden" name="user_id" value="<?=$customer_id;?>">
      </form>
    </div>
    
    <div class="car-modal__footer">
      <button type="button" class="car-btn car-btn--secondary car-modal__cancel-btn">Закрыть</button>
      <button type="button" class="car-btn car-btn--primary car-modal__submit-btn">Сохранить</button>
    </div>
  </div>
</div>

<style>
/* Overlay */
.car-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

/* Modal container */
.car-modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  animation: car-modal-appear 0.3s ease-out;
}

@keyframes car-modal-appear {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Header */
.car-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e5e5;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.car-modal__title {
  margin: 0;
  font-size: 1.5rem;
  color: #333;
  font-weight: 600;
}

.car-modal__close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.car-modal__close-btn:hover {
  color: #333;
  background: #f5f5f5;
  border-radius: 50%;
}

/* Body */
.car-modal__body {
  padding: 20px;
}

/* Form styles */
.car-form__group {
  margin-bottom: 20px;
  position: relative;
}

.car-form__row {
  display: flex;
  gap: 15px;
}

.car-form__row .car-form__group {
  flex: 1;
}

.car-form__label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.car-form__input,
.car-form__select,
.car-form__textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
  font-family: inherit;
}

.car-form__input:focus,
.car-form__select:focus,
.car-form__textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.car-form__input::placeholder {
  color: #999;
}

.car-form__textarea {
  resize: vertical;
  min-height: 80px;
}

/* Group with unit */
.car-form__group--with-unit .car-form__input {
  padding-right: 50px;
}

.car-form__unit {
  position: absolute;
  right: 12px;
  top: 38px;
  color: #666;
  font-size: 14px;
}

/* Footer */
.car-modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e5e5e5;
  background: #f9f9f9;
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
  position: sticky;
  bottom: 0;
  background: white;
}

/* Buttons */
.car-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  min-width: 120px;
}

.car-btn--primary {
  background: #007bff;
  color: white;
}

.car-btn--primary:hover {
  background: #0056b3;
  transform: translateY(-1px);
}

.car-btn--secondary {
  background: #6c757d;
  color: white;
}

.car-btn--secondary:hover {
  background: #545b62;
  transform: translateY(-1px);
}

.car-btn:active {
  transform: translateY(0);
}

/* Validation states */
.car-form__input:invalid:not(:focus):not(:placeholder-shown),
.car-form__select:invalid:not(:focus) {
  border-color: #dc3545;
}

.car-form__input:valid:not(:focus):not(:placeholder-shown),
.car-form__select:valid:not(:focus) {
  border-color: #28a745;
}

/* Responsive */
@media (max-width: 768px) {
  .car-form__row {
    flex-direction: column;
    gap: 0;
  }
  
  .car-modal {
    margin: 20px;
    width: calc(100% - 40px);
  }
  
  .car-modal__footer {
    flex-direction: column-reverse;
  }
  
  .car-btn {
    min-width: auto;
    width: 100%;
  }
}

@media (max-width: 480px) {
  .car-modal__header {
    padding: 15px;
  }
  
  .car-modal__body {
    padding: 15px;
  }
  
  .car-modal__footer {
    padding: 15px;
  }
  
  .car-modal__title {
    font-size: 1.3rem;
  }
}
</style>

<script>
// Заполнение списка годов
function carModalPopulateYearSelect() {
  const yearSelect = document.getElementById('manufactureYear');
  const currentYear = new Date().getFullYear();
  
  yearSelect.innerHTML = '<option value="">Выберите год</option>';
  
  for (let year = currentYear; year >= currentYear - 50; year--) {
    const option = document.createElement('option');
    option.value = year;
    option.textContent = year;
    yearSelect.appendChild(option);
  }
}

// Закрытие модального окна
function carModalClose() {
  const modal = document.getElementById('addCarModal');
  modal.style.display = 'none';
  document.getElementById('addCarForm').reset();
}

// Очистка формы
function carModalClearForm() {
	const form = document.getElementById('addCarForm');
	form.reset();
	document.getElementById('carId').value = '';  

	// Сбрасываем выпадающие списки к первому option
	const selects = form.querySelectorAll('select');
	selects.forEach(select => {
		select.selectedIndex = 0;
	});
}

function getSelectedCarID() {
	return $('#user_car').val();
}

// Заполнение формы данными автомобиля
function fillCarForm(carData) {
  if (!carData) return;
  
  console.log('Заполняем форму данными:', carData);
  
  // Заполняем поля формы
  document.getElementById('carId').value = carData.id || '';
  document.getElementById('carCaption').value = carData.caption || '';
  document.getElementById('carMark').value = carData.marka || '';
  document.getElementById('carModel').value = carData.model || '';
  //document.getElementById('mileage').value = carData.mileage || '0';
  document.getElementById('engineValue').value = carData.engine_value || '';
  document.getElementById('vin').value = carData.vin || '';
  document.getElementById('frame').value = carData.frame || '';
  document.getElementById('color').value = carData.color || '';
  document.getElementById('country').value = carData.country || '';
  document.getElementById('license_plate').value = carData.license_plate || '';
  document.getElementById('note').value = carData.note || '';
  
  // Заполняем выпадающие списки
  if (carData.year) {
    document.getElementById('manufactureYear').value = carData.year;
  }
  
  if (carData.body_type) {
    document.getElementById('bodyType').value = carData.body_type;
  }
  
  if (carData.fuel_type) {
    document.getElementById('fuelType').value = carData.fuel_type;
  }
  
  if (carData.transmission) {
    document.getElementById('transmission').value = carData.transmission;
  }
  
  if (carData.wheel) {
    document.getElementById('wheel').value = carData.wheel;
  }
}

// Открытие модального окна
function carModalOpen(new_car) {
	const car_id = getSelectedCarID();
	if (car_id > 0 && !new_car) {
		setCarData(car_id);
	} else {
		// Если это новое авто, очищаем форму
		carModalClearForm();
	}
	
	const modal = document.getElementById('addCarModal');
	modal.style.display = 'flex';
	carModalPopulateYearSelect();
}

//Отображаем все данные текущего автомобиля
function setCarData(car_id) {
	postAjax({action: 'get_car_data_by_id', car_id : car_id} , false, 'renderCarData');
}

function renderCarData(answer) {
	if (answer && answer.data) {
		fillCarForm(answer.data);
	}
} 


// Обработка отправки формы
function carModalHandleSubmit() {
  const form = document.getElementById('addCarForm');
  if (form.checkValidity()) {
    const formData = new FormData(form);
    const carData = {
		car_id: formData.get('car_id'),
		caption: formData.get('caption'),
		marka: formData.get('marka'),
		model: formData.get('model'),
		year: formData.get('year'),
		//mileage: formData.get('mileage'),
		body_type: formData.get('body_type'),
		engine_value: formData.get('engine_value'),
		fuel_type: formData.get('fuel_type'),
		vin: formData.get('vin'),
		frame: formData.get('frame'),
		color: formData.get('color'),
		country: formData.get('country'),
		wheel: formData.get('wheel'),
		transmission: formData.get('transmission'),
		note: formData.get('note'),
		license_plate: formData.get('license_plate'),
		active: formData.get('active'),
		user_id: formData.get('user_id')
    };
    
    console.log('Данные автомобиля:', carData);
    
    postAjax({action: 'proccess_car_data', 
		car_id: formData.get('car_id'),
		caption: formData.get('caption'),
		marka: formData.get('marka'),
		model: formData.get('model'),
		year: formData.get('year'),
		//mileage: formData.get('mileage'),
		body_type: formData.get('body_type'),
		engine_value: formData.get('engine_value'),
		fuel_type: formData.get('fuel_type'),
		vin: formData.get('vin'),
		frame: formData.get('frame'),
		color: formData.get('color'),
		country: formData.get('country'),
		wheel: formData.get('wheel'),
		transmission: formData.get('transmission'),
		note: formData.get('note'),
		license_plate: formData.get('license_plate'),
		active: formData.get('active'),
		user_id: formData.get('user_id')
	}, false, 'render_data_after_car_adding');
    
    carModalClose();
  } else {
    form.reportValidity();
  }
}

function render_data_after_car_adding() {
	get_cars_info_by_user_id();
}

function delete_car() {
	if (confirm('Автомобиль будет удален. Продолжить?')) {
		postAjax({action: 'delete_car_by_id', id : document.querySelector('#user_car').value}, false, 'get_cars_info_by_user_id');
	}
}

// Инициализация модального окна
function carModalInit() {
	carModalClose();
  const closeBtn = document.querySelector('.car-modal__close-btn');
  const cancelBtn = document.querySelector('.car-modal__cancel-btn');
  const submitBtn = document.querySelector('.car-modal__submit-btn');
  
  closeBtn.addEventListener('click', carModalClose);
  cancelBtn.addEventListener('click', carModalClose);
  submitBtn.addEventListener('click', carModalHandleSubmit);
  
  // Закрытие по клику на overlay
  document.getElementById('addCarModal').addEventListener('click', function(e) {
    if (e.target === this) {
      carModalClose();
    }
  });
  
  // Закрытие по ESC
  document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('addCarModal');
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      carModalClose();
    }
  });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', carModalInit);
</script>  