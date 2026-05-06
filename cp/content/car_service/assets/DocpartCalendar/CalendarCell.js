class DocpartEvent {
    constructor(wrapper, record_data) {
        this.wrapper = wrapper;
        this.block = CalendarHtmlHandler.getHtmlRecord(record_data);
        this.record = record_data;

        this.block.style.height = 89 * record_data.duration + (record_data.duration - 1) + "px";

        this.block_event_change = false;

        this.appendEvent();

        if (this.block) {
            this.c_client_x = 0;
            this.c_client_y = 0;
            this.c_offset_x = 0;
            this.c_offset_y = 0;
            this.initFunctions();
            this.init();
        }
    }

    // Метод для установки обработчиков событий на элемент
    init() {
        this.block.addEventListener('mousemove', this.prepareEvents.bind(this));
    }

    initFunctions() {
        this.onBlockMove = function (event) {
            let target = event.currentTarget;

            // Вычисление новых координат
            let current_offset_x = this.c_offset_x + (event.clientX - this.c_client_x);
            let current_offset_y = this.c_offset_y + (event.clientY - this.c_client_y);

            // Установка новых координат блока
            target.style.left = current_offset_x + 'px';
            target.style.top = current_offset_y + 'px';
        };

        this.onBlockMoveHandler = this.onBlockMove.bind(this);

        this.onBlockMouseDown = function (event) {
            this.block_event_change = true;

            let target = event.currentTarget;
            target.classList.add('move_block');
            target.style.left = target.offsetLeft + 'px';
            target.style.top = target.offsetTop + 'px';

            target.style.height = "89px";
            target.style.zIndex = "9999";
            target.style.border = "1px solid black";

            // Сохранение начальных координат
            this.c_client_x = event.clientX;
            this.c_client_y = event.clientY;
            this.c_offset_x = target.offsetLeft;
            this.c_offset_y = target.offsetTop;

            // Добавление обработчика для перемещения блока
            target.addEventListener('mousemove', this.onBlockMoveHandler);
            this.block_event_change = true;
        };

        this.onBlockMouseDownHandler = this.onBlockMouseDown.bind(this);

        this.onBlockMouseUp = function (event) {
            let target = event.currentTarget;
            target.classList.remove('move_block');

            // Удаление обработчика перемещения
            target.removeEventListener('mousemove',this.onBlockMoveHandler);
            this.resetPosition(target);

            // Добавление блока в новую позицию
            this.appendTarget(event.clientX, event.clientY, target);
            this.block_event_change = false;
        };

        this.onBlockMouseUpHandler = this.onBlockMouseUp.bind(this);

        this.showInfoRecord = function (event) {
            location = url + 'sto_record?id=' + this.record.id;
            //Если вдруг понадобится модалка
            /*
            CalendarHtmlHandler.getEl('.modal_record_info').classList.remove('hidden');
            const id = this.record.id;
            const date_record = new Date(parseInt(this.record.time));
            const date_str =  date_record.getDate() + '.' + date_record.getMonth() + '.' + date_record.getFullYear();
            CalendarHtmlHandler.getEl('#time_value').textContent = 'Запись на ' + date_str;
            CalendarHtmlHandler.getEl('#car_value').textContent = this.record.car_name;

            CalendarHtmlHandler.getEl('#services_value').textContent = this.record.car_name;

            CalendarHtmlHandler.getEl('#update_record').onclick = function () {
                location = url + 'sto_record?id=' + id;
            }
            */

        }

        this.showInfoRecordHandler = this.showInfoRecord.bind(this);

        this.resizeBlockWhileMouseMove = function (event) {
            /*const diff = event.target.offsetHeight - event.offsetY;
            if (diff < 5){
                event.target.style.height = event.target.offsetHeight + 5 + 'px';
            }
            else if (diff > 20 && event.target.offsetHeight - 10 >= 89)
                event.target.style.height = event.target.offsetHeight - 10 + 'px';
            */
            this.block_event_change = true;
        }

        this.resizeBlockWhileMouseMoveHandler = this.resizeBlockWhileMouseMove.bind(this);

        this.resizeBlock = function (event) {
            this.block.style.outline = "10px solid #fff0";
            this.block.addEventListener('mousemove', this.resizeBlockWhileMouseMoveHandler);
        }

        this.resizeBlockHandler = this.resizeBlock.bind(this);

        this.resizeBlockEnd = function (event) {
            this.block.removeEventListener('mousemove', this.resizeBlockWhileMouseMoveHandler);
            this.block.classList.remove('resize_event');
            this.setNewHeightRecord();
            this.block_event_change = false;
        }
        this.resizeBlockEndHandler = this.resizeBlockEnd.bind(this);

    }

    appendEvent() {
        if (DocpartCalendar.mode == 'week' && DocpartCalendar.calendar.current_post_type == this.record.post_id)
            this.wrapper.append(this.block);
        else
            this.wrapper.append(this.block);
    }

    prepareEvents(event) {
        if (event.target.offsetHeight - event.offsetY < 25 && !this.block_event_change && event.target.classList.contains('docpart_event'))
            this.resizeEvent();
        else if (event.offsetY < 20 && !this.block_event_change && event.target.classList.contains('docpart_event'))
            this.replaceEvent();
        else if (!this.block_event_change)
            this.infoEvent();

    }


    replaceEvent() {
        this.deleteInfoEvent();
        this.deleteResizeEvent();

        this.block.addEventListener('mousedown', this.onBlockMouseDownHandler);
        this.block.addEventListener('mouseup', this.onBlockMouseUpHandler);
        this.block.style.cursor = 'grabbing';
    }

    deleteReplaceEvent() {
        this.block.removeEventListener('mousedown', this.onBlockMouseDownHandler);
        this.block.removeEventListener('mouseup', this.onBlockMouseUpHandler);
    }

    resizeEvent() {
        this.deleteReplaceEvent();
        this.deleteInfoEvent();
        this.block.addEventListener('mousedown', this.resizeBlockHandler);
        this.block.addEventListener('mouseup', this.resizeBlockEndHandler);
        //this.block.style.cursor = 'ns-resize';
        this.block.classList.add('resize_event');
    }

    deleteResizeEvent() {
        this.block.removeEventListener('mousedown', this.resizeBlockHandler);
        this.block.removeEventListener('mouseup', this.resizeBlockEndHandler);
        this.block.classList.remove('resize_event');
    }

    infoEvent() {
        this.deleteReplaceEvent();
        this.deleteResizeEvent();

        this.block.addEventListener('click', this.showInfoRecordHandler);

        this.block.style.cursor = 'pointer';
    }

    deleteInfoEvent() {
        this.block.removeEventListener('click', this.showInfoRecordHandler);
    }

    // Метод для сброса позиции блока
    resetPosition(target) {
        this.c_client_x = 0;
        this.c_client_y = 0;
        target.style.left = '';
        target.style.top = '';
    }

    setNewHeightRecord() {
        const factor = Math.ceil(this.block.offsetHeight / 89);
        this.block.style.height = 89 * factor + (factor - 1) + 'px';
        this.record.duration = factor;

        this.updateData().then((res) => {
            DocpartEvent.updateGrid();
        });
    }

    // Проверка, является ли элемент целевым контейнером
    checkWrapper(wrapper) {
        if (wrapper.classList.contains('docpart_cell'))
            return wrapper;
        else if (wrapper.parentNode.classList.contains('docpart_cell'))
            return wrapper.parentNode
        else
            return this.wrapper;
    }

    // Метод для добавления блока в целевой контейнер
    appendTarget(x, y, target) {
        let wrapper = document.elementFromPoint(x, y);
        wrapper = this.checkWrapper(wrapper);
        wrapper.append(target);
        this.wrapper = wrapper;

        this.updateData().then((res) => {
            DocpartEvent.updateGrid();
        });

        this.block_event_change = false;
    }

    saveTime() {
        this.record.time = this.wrapper.getAttribute('time');
    }

    savePost() {
        if (DocpartCalendar.mode == "week")
            this.record.post_id = DocpartCalendar.calendar.current_post_type;
        else
            this.record.post_id = this.wrapper.getAttribute('post_id');

    }

    async updateData() {
        this.saveTime();
        this.savePost();
        const obj = {
            action: "update_record",
            record : this.record
        };

        const res = await fetchAjax(obj);
    }

    static  updateGrid() {
        if (DocpartCalendar.mode == 'week')
            CalendarHtmlHandler.renderRecords(DocpartCalendar.calendar.current_post_type);
        else
            CalendarHtmlHandler.renderRecords();
    }
}


