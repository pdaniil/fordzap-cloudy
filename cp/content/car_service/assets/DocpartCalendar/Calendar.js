class DocpartCalendar {

    constructor(options = null) {
        this.options = options;
        this.date_for_week = new Date();
        this.date_for_day = new Date();

        this.arr_days_of_week = null;
        this.arr_days_of_week_short = null;
        this.current_post_type = DocpartCalendar.post_types[0].id;

        this.records = [];
    }

    static modes = {
        week : DocpartCalendar.weekMode,
        day : DocpartCalendar.dayMode
    };

    static mode = 'week';

    static post_types = null;

    static months_trim = {
        0 : 'Янв',
        1 : 'Фев',
        2 : 'Мар',
        3 : 'Апр',
        4 : 'Мая',
        5 : 'Июн',
        6 : 'Июл',
        7 : 'Авг',
        8 : 'Сен',
        9 : 'Окт',
        10 : 'Ноября',
        11 : 'Дек'
    }

    static months = {
        0 : 'Января',
        1 : 'Февраля',
        2 : 'Марта',
        3 : 'Апреля',
        4 : 'Мая',
        5 : 'Июня',
        6 : 'Июля',
        7 : 'Августа',
        8 : 'Сентября',
        9 : 'Октября',
        10 : 'Ноября',
        11 : 'Декабря'
    }

    static days_of_week = {
        0 : 'Вс',
        1 : 'Пн',
        2 : 'Вт',
        3 : 'Ср',
        4 : 'Чт',
        5 : 'Пт',
        6 : 'Сб'
    }

    static day_of_week = {
        0 : 'Воскресенье',
        1 : 'Понедельник',
        2 : 'Вторник',
        3 : 'Среда',
        4 : 'Четверг',
        5 : 'Пятница',
        6 : 'Суббота'
    }

    static calendar = null;

    static loadOptionsFromCookie() {
        const current_mode = getCookie('current_mode');
        const current_day_data = getCookie('current_day_data_calendar');
        const current_week_data = getCookie('current_week_data_calendar');

        return {
            current_mode : current_mode,
            current_day_data : current_day_data,
            current_week_data : current_week_data
        };
    }

    static async init(options = null) {
        await DocpartCalendar.setPostTypes();

        let time = await DocpartCalendar.getWorkTime();
        if (options == null)
            options = {};

        options.time_from = Math.round(time.time_from);
        options.time_to = Math.round(time.time_to);
        options.operating_mode = JSON.parse(time.operating_mode);

        if (DocpartCalendar.calendar == null)
        {
            options = Object.assign(options, DocpartCalendar.loadOptionsFromCookie());
            DocpartCalendar.calendar = new DocpartCalendar(options);
        }

        DocpartCalendar.weekMode(true);
        DocpartCalendar.setCurrentPostType(DocpartCalendar.post_types[0].id);

        return DocpartCalendar.calendar;
    }

    static async getRecords(post_type = null) {
        let time_to = 0;
        let time_from = 0;
        if (DocpartCalendar.mode == 'week') {
            time_from = DocpartCalendar.calendar.arr_days_of_week_short[0].dateTimeStr.getTime();
            let curr_time = new Date(time_from);
            time_from = curr_time.setHours(1);

            time_to = DocpartCalendar.calendar.arr_days_of_week_short[DocpartCalendar.calendar.arr_days_of_week_short.length - 1].dateTimeStr.getTime();
            curr_time = new Date(time_to);
            time_to = curr_time.setHours(23);
        }
        else
        {
            const time_str = DocpartCalendar.calendar.date_for_day.getTime();
            const curr_time = new Date(time_str);
            time_from = curr_time.setHours(1);
            time_to = curr_time.setHours(23);
        }
        let res = null;
        if (post_type == null)
            res =  await fetchAjax({action: 'get_records', sto_id : sto_id, time_to: time_to, time_from: time_from});
        else
            res =  await fetchAjax({action: 'get_records_by_post', sto_id : sto_id, time_to: time_to, time_from: time_from, post_type : post_type});
        return res.records;
    }


    static addDay() {
        DocpartCalendar.calendar.date_for_day.setDate( DocpartCalendar.calendar.date_for_day.getDate() + 1);
        if (DocpartCalendar.calendar.date_for_day.getDay() == 1)
        {
            DocpartCalendar.calendar.date_for_week.setDate(DocpartCalendar.calendar.date_for_week.getDate() + 9);
        }

        DocpartCalendar.setDayByDate();
    }

    static reduceDay() {
        DocpartCalendar.calendar.date_for_day.setDate( DocpartCalendar.calendar.date_for_day.getDate() - 1);
        if (DocpartCalendar.calendar.date_for_day.getDay() == 0)
        {
            DocpartCalendar.calendar.date_for_week.setDate(DocpartCalendar.calendar.date_for_week.getDate() - 2);
        }

        DocpartCalendar.setDayByDate();
    }

    static addWeek() {
        DocpartCalendar.calendar.date_for_week.setDate(DocpartCalendar.calendar.date_for_week.getDate() + 2);
        DocpartCalendar.setWeekByDate();
    }

    static reduceWeek() {
        DocpartCalendar.calendar.date_for_week.setDate(DocpartCalendar.calendar.date_for_week.getDate() - 9);
        DocpartCalendar.setWeekByDate();
    }

    static syncDateForDay() {
        const day_now = DocpartCalendar.calendar.date_for_day.getDay();
        DocpartCalendar.calendar.date_for_day.setDate( DocpartCalendar.calendar.date_for_week.getDate() + day_now - 1);
        DocpartCalendar.calendar.date_for_day.setMonth( DocpartCalendar.calendar.date_for_week.getMonth());
    }

    static setWeekByDate() {
        let c_date = DocpartCalendar.calendar.date_for_week;
        setCookie('current_week_data_calendar', c_date.getTime(), {'max-age' : 3600});
        c_date.setDate(c_date.getDate() - c_date.getDay() + 1);

        DocpartCalendar.syncDateForDay();

        let i = 0;
        DocpartCalendar.calendar.arr_days_of_week = [];
        DocpartCalendar.calendar.arr_days_of_week_short = [];
        while (i < 7)
        {
            i++;

            DocpartCalendar.calendar.arr_days_of_week.push(c_date.getDate()+ ' ' + DocpartCalendar.months[c_date.getMonth()])

            const day_obj = {
                textContent : DocpartCalendar.days_of_week[c_date.getDay()] + ', ' + c_date.getDate()+ ' ' + DocpartCalendar.months_trim[c_date.getMonth()],
                dateTimeStr : new Date(c_date.getFullYear(), c_date.getMonth(), c_date.getDate(), c_date.getDay())
            };

            if (!DocpartCalendar.calendar.options.operating_mode.includes(String(i)))
            {
                c_date.setDate(c_date.getDate() + 1);
                continue;
            }

            DocpartCalendar.calendar.arr_days_of_week_short.push(day_obj)
            c_date.setDate(c_date.getDate() + 1);
        }
        CalendarHtmlHandler.renderWeekMode();
    }

    static setDayByDate() {
        let c_date = DocpartCalendar.calendar.date_for_day;
        setCookie('current_day_data_calendar', c_date.getTime(), {'max-age' : 3600});
        CalendarHtmlHandler.getEl('.curr_date').textContent = c_date.getDate() + ' ' +  DocpartCalendar.months[c_date.getMonth()] + ', ' + DocpartCalendar.day_of_week[c_date.getDay()];
        CalendarHtmlHandler.renderDayMode();
    }

    static async getWorkTime() {
        let res =  await fetchAjax({action: 'get_work_time', id : sto_id});
        return res.time;
    }

    static async setPostTypes() {
        let res =  await fetchAjax({action: 'get_post_types'});
        DocpartCalendar.post_types = res.posts;
    }

    static setCurrentPostType(id) {
        DocpartCalendar.calendar.current_post_type = id;
        CalendarHtmlHandler.renderCurrentPostType( );
    }

    static listenersWeekMode() {

        CalendarHtmlHandler.getEl('.switch_prev_date').removeEventListener('click', DocpartCalendar.reduceDay);
        CalendarHtmlHandler.getEl('.switch_next_date').removeEventListener('click', DocpartCalendar.addDay);

        CalendarHtmlHandler.getEl('.switch_prev_date').addEventListener('click', DocpartCalendar.reduceWeek);
        CalendarHtmlHandler.getEl('.switch_next_date').addEventListener('click', DocpartCalendar.addWeek);

    }

    static listenersDayMode() {
        CalendarHtmlHandler.getEl('.switch_prev_date').removeEventListener('click', DocpartCalendar.reduceWeek);
        CalendarHtmlHandler.getEl('.switch_next_date').removeEventListener('click', DocpartCalendar.addWeek);

        CalendarHtmlHandler.getEl('.switch_prev_date').addEventListener('click', DocpartCalendar.reduceDay);
        CalendarHtmlHandler.getEl('.switch_next_date').addEventListener('click', DocpartCalendar.addDay);
    }

    static weekMode(render = null) {
        setCookie('current_mode', 'week', {'max-age': 3600});
        DocpartCalendar.setWeekByDate();
        if (render == null)
            DocpartCalendar.reduceWeek();
        DocpartCalendar.listenersWeekMode();

        CalendarHtmlHandler.render('week');
    }

    static dayMode() {
        setCookie('current_mode', 'day', {'max-age': 3600});
        DocpartCalendar.setDayByDate();
        DocpartCalendar.listenersDayMode();
        CalendarHtmlHandler.render('day');
    }

    static changeMode(mode) {
        DocpartCalendar.mode = mode;
        DocpartCalendar.modes[mode]();
    }
}