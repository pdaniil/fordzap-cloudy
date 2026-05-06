class CalendarHtmlHandler {

    static modes = {
        week : CalendarHtmlHandler.renderWeekMode,
        day : CalendarHtmlHandler.renderDayMode
    };

    static html_time_bar = null;

    static createEl(class_attr) {
        const div = document.createElement('div');
        div.classList.add(class_attr);
        return div;
    }

    static getEl(class_name) {
        return document.querySelector(class_name);
    }

    static getBy(attr, value) {
        return CalendarHtmlHandler.getEl('['+attr+'="'+value+'"]')
    }

    static setHtmlTimeBar() {
        const time_bar_wrapper = CalendarHtmlHandler.createEl('time_bar');
        for (let i = DocpartCalendar.calendar.options.time_from; i <= DocpartCalendar.calendar.options.time_to; i++) {
            const time_cell = CalendarHtmlHandler.createEl('time_cell');
            const  time_value = CalendarHtmlHandler.createEl('time_value');
            time_value.textContent = i + ':00';
            time_cell.append(time_value);
            time_bar_wrapper.append(time_cell);
        }

        CalendarHtmlHandler.html_time_bar =  time_bar_wrapper;
    }

    static setHtmlWeekDays() {
        DocpartCalendar.calendar.arr_days_of_week_short.forEach((day_obj) => {
            const day_cell = CalendarHtmlHandler.createEl('day_wrapper');
            day_cell.textContent = day_obj.textContent;
            CalendarHtmlHandler.getEl('.week_days').append(day_cell);
        });
    }

    static renderTimeBar() {
        CalendarHtmlHandler.setHtmlTimeBar();

        if (CalendarHtmlHandler.getEl('.time_bar') != null)
            CalendarHtmlHandler.getEl('.time_bar').remove();

        CalendarHtmlHandler.getEl('.calendar_grid').prepend(CalendarHtmlHandler.html_time_bar);
    }

    static renderWeekDays() {
        CalendarHtmlHandler.getEl('.week_days').innerHTML = '';
        CalendarHtmlHandler.setHtmlWeekDays();
    }

    static getHtmlDayCell(date_obj, hour, post_type = null) {
        const docpart_cell = CalendarHtmlHandler.createEl('docpart_cell');

        date_obj.setHours(hour);
        date_obj.setMinutes(0);
        date_obj.setSeconds(0);
        date_obj.setSeconds(0);
        date_obj.setMilliseconds(0);

        docpart_cell.setAttribute('time', date_obj.getTime());

        if (post_type == null)
        {
            docpart_cell.setAttribute('id', DocpartCalendar.mode + '_' + date_obj.getTime());
            docpart_cell.addEventListener('mousedown', (event) => {
                let obj = {
                    action : 'create_record',
                    time :  docpart_cell.getAttribute('time'),
                    post_type : DocpartCalendar.calendar.current_post_type,
                    sto_id : sto_id
                };

                if (event.target.classList.contains('docpart_cell'))
                    fetchAjax(obj).then((ans) => {
                        if (ans.status = 200)
                            DocpartEvent.updateGrid();
                    });
            });
        }
        else
        {
            docpart_cell.setAttribute('id', DocpartCalendar.mode + '_' + date_obj.getTime() + '_' + post_type);
            docpart_cell.addEventListener('mousedown', (event) => {
                let obj = {
                    action : 'create_record',
                    time : docpart_cell.getAttribute('time'),
                    post_type : post_type,
                    sto_id : sto_id
                };

                if (event.target.classList.contains('docpart_cell'))
                    fetchAjax(obj).then((ans) => {
                        if (ans.status = 200)
                            DocpartEvent.updateGrid();
                    });
            });
        }

        return docpart_cell;
    }

    static setHtmlDaysGreedWeek() {
        DocpartCalendar.calendar.arr_days_of_week_short.forEach((day_obj) => {
            const docpart_col = CalendarHtmlHandler.createEl('docpart_col');

            for (let i = DocpartCalendar.calendar.options.time_from; i <= DocpartCalendar.calendar.options.time_to; i++)
                docpart_col.append(CalendarHtmlHandler.getHtmlDayCell(day_obj.dateTimeStr, i));

            CalendarHtmlHandler.getEl('.days_greed').append(docpart_col);
        });
    }

    static setHtmlDaysGreedDay() {
        CalendarHtmlHandler.getEl('#days_greed_day_mode').innerHTML = '';
        DocpartCalendar.post_types.forEach((post_obj) => {
            const docpart_col = CalendarHtmlHandler.createEl('docpart_col');
            for (let i = DocpartCalendar.calendar.options.time_from; i <= DocpartCalendar.calendar.options.time_to; i++)
            {
                const cell = CalendarHtmlHandler.getHtmlDayCell(DocpartCalendar.calendar.date_for_day, i, post_obj.id);
                cell.setAttribute('post_id',post_obj.id);
                docpart_col.append(cell);
            }
            CalendarHtmlHandler.getEl('#days_greed_day_mode').append(docpart_col);
        });
        CalendarHtmlHandler.renderRecords();
    }

    static renderPostTypesWeek() {
        CalendarHtmlHandler.getEl('.post_types_switcher').innerHTML = '';
        DocpartCalendar.post_types.forEach((post) => {
            const post_wrapper = CalendarHtmlHandler.createEl('post_type');
            const post_text = CalendarHtmlHandler.createEl('post_text');
            const post_image = CalendarHtmlHandler.createEl('post_image');
            post_wrapper.setAttribute('post_id', post.id);
            post_text.textContent = post.caption;

            post_image.style.background = "url("+encodeURI(post.image)+")";
            post_wrapper.append(post_image);
            post_wrapper.append(post_text);

            post_wrapper.addEventListener('click', () => { DocpartCalendar.setCurrentPostType(post.id) });

            CalendarHtmlHandler.getEl('.post_types_switcher').append(post_wrapper);
        });

        CalendarHtmlHandler.renderCurrentPostType();
    }

    static renderCurrentPostType() {
        const curr_wrapper = CalendarHtmlHandler.getEl('.post_type_active');
        if (curr_wrapper != null)
            curr_wrapper.classList.remove('post_type_active');

        CalendarHtmlHandler.getBy('post_id', DocpartCalendar.calendar.current_post_type).classList.add('post_type_active');
        CalendarHtmlHandler.renderRecords(DocpartCalendar.calendar.current_post_type);
    }

    static renderDaysGreedWeek() {
        CalendarHtmlHandler.getEl('.days_greed').innerHTML = '';
        CalendarHtmlHandler.setHtmlDaysGreedWeek();
    }

    static renderCurrDateWeek() {
        CalendarHtmlHandler.getEl('#curr_date').textContent = DocpartCalendar.calendar.arr_days_of_week[0] + ' - ' + DocpartCalendar.calendar.arr_days_of_week[6];
    }

    static renderWeekMode() {
        CalendarHtmlHandler.removeDayHtml();
        CalendarHtmlHandler.renderCurrDateWeek();
        CalendarHtmlHandler.renderPostTypesWeek();
        CalendarHtmlHandler.renderWeekDays();
        CalendarHtmlHandler.renderDaysGreedWeek();
    }

    static removeWeekHtml() {
        CalendarHtmlHandler.getEl('#header_week_mode').classList.add('hidden');
        CalendarHtmlHandler.getEl('#days_greed_week_mode').classList.add('hidden');
        CalendarHtmlHandler.getEl('#days_greed_day_mode').classList.remove('hidden');
        CalendarHtmlHandler.getEl('#header_day_mode').classList.remove('hidden');
    }

    static removeDayHtml() {
        CalendarHtmlHandler.getEl('#header_week_mode').classList.remove('hidden');
        CalendarHtmlHandler.getEl('#days_greed_week_mode').classList.remove('hidden');
        CalendarHtmlHandler.getEl('#days_greed_day_mode').classList.add('hidden');
        CalendarHtmlHandler.getEl('#header_day_mode').classList.add('hidden');
    }

    static renderPostTypesDayMode() {
        CalendarHtmlHandler.setHtmlPostTypesDay();
    }

    static setHtmlPostTypesDay() {
        CalendarHtmlHandler.getEl('#post_types').innerHTML = '';
        DocpartCalendar.post_types.forEach((post) => {
            const post_wrapper = CalendarHtmlHandler.createEl('post_type');
            const post_text = CalendarHtmlHandler.createEl('post_text');
            const post_image = CalendarHtmlHandler.createEl('post_image');
            post_wrapper.setAttribute('post_id', post.id);
            post_text.textContent = post.caption;

            post_image.style.background = "url("+encodeURI(post.image)+")";
            post_wrapper.append(post_image);
            post_wrapper.append(post_text);

            CalendarHtmlHandler.getEl('#post_types').append(post_wrapper);
        });
    }

    static renderDayMode() {
        CalendarHtmlHandler.removeWeekHtml();
        CalendarHtmlHandler.renderPostTypesDayMode();
        CalendarHtmlHandler.setHtmlDaysGreedDay();
    }

    static removeRecords() {
        document.querySelectorAll('.docpart_event').forEach((el) => {
           el.remove();
        });
    }

	static findClosestCell(targetTime) {
		const cells = document.querySelectorAll('.docpart_cell');
		let closest = null;
		let closestDiff = Infinity;

		cells.forEach(cell => {
		const cellTime = parseInt(cell.getAttribute('time'));
		const diff = Math.abs(cellTime - targetTime);

		if (diff < closestDiff) {
		  closestDiff = diff;
		  closest = cell;
		}

		// Приоритет — если нашли точное совпадение, сразу возвращаем
		if (cellTime === targetTime) {
		  closest = cell;
		  return;
		}
		});

		return closest;
	}


    static getRecordWrapper(record) {
        if (DocpartCalendar.mode == 'week')
		{
			let needed_cell = document.querySelector('#' + DocpartCalendar.mode + '_' + record.time);
			if (needed_cell == null)
				needed_cell = CalendarHtmlHandler.findClosestCell(record.time);
			
			return needed_cell;
			
		}
        else
		{
			return document.querySelector('#' + DocpartCalendar.mode + '_' + record.time + '_' + record.post_id);
		}
            
    }

    static getHtmlRecord(record_obj) {
        const record = CalendarHtmlHandler.createEl('docpart_event');
        const event_content = CalendarHtmlHandler.createEl('event_content');
        const time_content = CalendarHtmlHandler.createEl('event_time_content');

        let  time_hours = new Date(parseInt(record_obj.time));

        time_hours = time_hours.getHours();
        const time_str = time_hours + ':00 - ' + parseInt((parseInt(time_hours) + parseInt(record_obj.duration))) + ':00';

        time_content.textContent = time_str;
        event_content.append(time_content);

        if (record_obj.color != '')
        {
            record.style.background = record_obj.color;
            record.style.color = '#ffffff';
        }

        if (record_obj.worker_fio != '' || record_obj.car_name != '')
        {
            const record_info = CalendarHtmlHandler.createEl('.record_info');
            record_info.textContent = record_obj.worker_fio != '' ? record_obj.worker_fio + ', ' : '';
            record_info.textContent += record_obj.car_name;
            event_content.append(record_info);
        }

        record.append(event_content);

        record.setAttribute('id', record_obj.id);
        record.setAttribute('duration', record_obj.duration);
        record.setAttribute('time', record_obj.time);
        return record;
    }

    static renderRecords(post_type = null) {
        //
        DocpartCalendar.getRecords(post_type).then((res) => {
            CalendarHtmlHandler.removeRecords();
            DocpartCalendar.calendar.records = [];
            res.forEach((record) => {
				console.log(record);
                const record_c = new DocpartEvent(CalendarHtmlHandler.getRecordWrapper(record), record);
                DocpartCalendar.calendar.records.push(record_c);
            });
        });
    }

    static render(mode) {
        CalendarHtmlHandler.renderTimeBar();
        CalendarHtmlHandler.modes[mode]();
    }
}