<style>

.col-lg-12 {
}
.panel-body {

}
.docpart_calendar {

}



/* TOOLBAR */
.docpart_calendar .docpart_calendar__toolbar_header {
    display: flex;
    justify-content: space-around;
    height: 90px;
    font-size: 16px;
    align-items: center;
    background: aliceblue;
    border: 1px solid  #6aabe6;
}

.docpart_calendar .docpart_calendar__toolbar_header .dates {
    display: flex;
    grid-gap: 5px;
}

.docpart_calendar .docpart_calendar__toolbar_header .dates .switcher_date_wrapper {
    display: flex;
    grid-gap: 5px;
}
.docpart_calendar .docpart_calendar__toolbar_header .dates .switcher_date_wrapper .switch_prev_date {
    cursor: pointer;
}

.docpart_calendar .docpart_calendar__toolbar_header .dates .switcher_date_wrapper .switch_next_date {
    cursor: pointer;
}

.docpart_calendar .docpart_calendar__toolbar_header .dates .curr_date {
    width: 250px;
    text-align: center;
}

.docpart_calendar .docpart_calendar__toolbar_header .switch_mode {
    display: flex;
    grid-gap: 10px;
}

.docpart_calendar .docpart_calendar__toolbar_header .switch_mode div {
    cursor: pointer;
}

.docpart_calendar .docpart_calendar__toolbar_header .switch_mode .switch_to_list {

}

.docpart_calendar .docpart_calendar__toolbar_header .switch_mode .switch_to_list {

}

.docpart_calendar .docpart_calendar__toolbar_header .switch_mode .switch_to_week {

}

.docpart_calendar .docpart_calendar__toolbar_header .switch_mode .switch_to_day {

}

/* TOOLBAR END */




/*-----------------------------------------------------------------*/




/* HEADER (DAYS,POST TYPES)*/

.docpart_calendar .docpart_calendar__main_content {

}

.docpart_calendar .docpart_calendar__main_content .header_content {
    position: sticky;
    top: 0;
    background: white;
    z-index: 499;
    width: fit-content;
}

.docpart_calendar .docpart_calendar__main_content .header_content .week_mode {
}

.docpart_calendar .docpart_calendar__main_content .header_content .week_mode .week_days_wrapper {
    display: flex;
    width: fit-content;
}

.docpart_calendar .docpart_calendar__main_content .post_types_switcher {
    display: flex;
    position: sticky;
    left: 0;
    width: fit-content;
    border-top: 1px solid #6aabe6;
    border-left: 1px solid #6aabe6;
    border-bottom: 1px solid #6aabe6;
}

.docpart_calendar .docpart_calendar__main_content .post_types_switcher .post_type {
    cursor: pointer;
    height: fit-content;
    width: fit-content;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #6aabe6;
    font-size: 16px;
    padding: 5px 5px;
    grid-gap: 5px;
    border-right: 1px solid #6aabe6;
    background: #fff;
}

.docpart_calendar .docpart_calendar__main_content .post_types_switcher .post_type:hover {
    color: white;
    background: #a1bddef2;
}

.header_content .day_mode #post_types {
    display: flex;
}
#header_day_mode {
    display: flex;
    width: fit-content;
}
#header_day_mode .empty_day {
    position: sticky;
    left: 0;
    z-index: 500;
    background: #b5d0e9;
    width: 46px;
    border-top: 1px solid #b5d0e9;
}
.header_content .day_mode #post_types .post_type {
    width: 280px;
    height: fit-content;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    font-size: 16px;
    padding: 5px 5px;
    grid-gap: 5px;
    border-left: 1px solid #b5d0e9;
    color: #b5d9f2;
}

.header_content .day_mode #post_types .post_type .post_image {
    width: 45px;
    height: 45px;
}
.post_type_active {
    color: white !important;
    background: #a1bddef2 !important;
    cursor: default !important;
}
.post_type_active:hover {
    color: #6aabe6;
}
.post_type .post_image {
    width: 50px;
    height: 50px;
    background-size: contain !important;
}

.docpart_calendar .docpart_calendar__main_content .header_content .week_mode .week_days {
    display: flex;
    width: fit-content;
}
.docpart_calendar .docpart_calendar__main_content .header_content .week_mode .week_days div:nth-child(1n) {
    border-left: 1px solid #b5d0e9;
}
.docpart_calendar .docpart_calendar__main_content .header_content .week_mode .empty_day {
    position: sticky;
    left: 0;
    z-index: 500;
    background: #b5d0e9;
    width: 46px;
    border-top: 1px solid #b5d0e9;
}

.docpart_calendar .docpart_calendar__main_content .header_content .week_mode .week_days .day_wrapper {
    width: 280px;
    height: 45px;
    font-size: 18px;
    text-align: center;
    color: #b5d9f2;
    border-top: 1px solid #b5d0e9;
}
.docpart_calendar .docpart_calendar__main_content .header_content .week_mode .week_days .day_wrapper:last-child {
    border-right: 1px solid #b5d0e9;
}
.docpart_calendar .docpart_calendar__main_content .header_content .post_type:last-child {
    border-right: 1px solid #b5d0e9;
}
.docpart_calendar .docpart_calendar__main_content .header_content .day_mode {

}

.docpart_calendar .docpart_calendar__main_content .header_content .day_mode .posts_types {

}

.docpart_calendar .docpart_calendar__main_content .header_content .day_mode .posts_types .post_type {

}

/*HEADER (DAYS,POST TYPES) END*/



/*-----------------------------------------------------------------*/



/* DAYS/TIME GREED */
.docpart_calendar .docpart_calendar__main_content .calendar_body {
    overflow-y: auto;
    height: 750px;
    display: flex;
    flex-direction: column;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body::-webkit-scrollbar {
    background: white;
    height: 10px;
    width: 10px;
    position: fixed;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body::-webkit-scrollbar-thumb {
    background: #bcd0e4;
    cursor: grab;
    border-radius: 10px;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body::-webkit-scrollbar-thumb:active {
    cursor: grabbing;
}

.docpart_calendar .docpart_calendar__main_content .time_bar {
    width: fit-content;
    display: flex;
    flex-direction: column;
    height: fit-content;
    position: sticky;
    left: 0;
    background: white;
    z-index: 498;
}

.docpart_calendar .docpart_calendar__main_content .time_bar .time_cell {
    height: 90px;
    width: 45px;
    border-bottom: 1px solid #b5d0e9;
}

.docpart_calendar .docpart_calendar__main_content .time_bar .time_cell:nth-child(odd) {
    background: #f7f8f9;
}

.docpart_calendar .docpart_calendar__main_content .time_bar .time_cell .time_cell_value {

}

.docpart_calendar .docpart_calendar__main_content .time_bar .time_cell:first-child {
    border-top: 1px solid #b5d0e9;
}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid {
    /*
    overflow-x: auto;
    background: #b5d0e9;
    width: fit-content;
    height: fit-content;
    max-width: 100%;
    */
    display: flex;
    width: fit-content;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid::-webkit-scrollbar {
    background: white;
    height: 10px;
    width: 10px;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid::-webkit-scrollbar-thumb {
    background: #bcd0e4;
    cursor: grab;
    border-radius: 10px;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid::-webkit-scrollbar-thumb:active {
    cursor: grabbing;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .time_bar {

}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .time_bar .time_cell:nth-child(odd) {

}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .time_bar .time_cell .time_value {
    text-align: right;
    padding-right: 4px;
    padding-top: 2px;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid .week_mode {

}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed {
    display: flex;
    align-items:flex-start;
    flex-wrap: nowrap;
    width: min-content;
    position: relative;
}


.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col {
    width: 280px;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:nth-child(5n - 4) .docpart_cell {
    background: #f1f5fb;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:nth-child(5n - 3) .docpart_cell {
    background: #deddf7;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:nth-child(5n - 2) .docpart_cell {
    background: #fdd4e1;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:nth-child(5n - 1) .docpart_cell {
    background:#ece6df;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:nth-child(5n) .docpart_cell {
    background:#dbf7e0;
}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell {
    width: 100%;
    height: 90px;
    border-left: 1px solid #b5d0e9;
    border-bottom: 1px solid #b5d0e9;
    cursor: pointer;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell:hover {
    background: grey;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:last-child .docpart_cell{

}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell:first-child {

}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col:nth-last-child(-n+1) {

}



/*DAYS/TIME GREED END*/


/*events*/
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell .docpart_event {
    background: #7abbe8;
    height: 89px;
    position: relative;
}

.resize_event {
    border: 1px solid rgba(0, 0, 0, 0.42);
    overflow: hidden;
    resize: vertical;
}

.movable {
    height: 89px;
    z-index: 9999;
    box-shadow: 2px 2px 2px black;
}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell .docpart_event {
    padding-top: 21px;
}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell .docpart_event .event_content {
    width: 90%;
    min-height: 45px;
    padding-left: 5px;
}
.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell .docpart_event .event_content .event_time_content {

}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell .docpart_event event_content .event_title {
    width: 90%;
}

.docpart_calendar .docpart_calendar__main_content .calendar_body .calendar_grid  .days_greed .docpart_col .docpart_cell .docpart_event event_content .event_body {
    width: 90%;
}


.move_block {
    position: absolute !important;
    width: 250px !important;
   /* height: 80px !important;*/
}
/*events end*/

/*modal*/
.modal_record_info {
    position: fixed;
    width: 100%;
    height: 100%;
    background: #808080c4;
    z-index: 99999;
    top: 0;
    left: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal_record_info .modal_record_content {
    background: white;
    width: 50%;
    max-width: 500px;
    height: fit-content;
    padding: 15px;
}

.modal_record_info .modal_record_content .modal_record_title {
    font-size: 18px;
    text-align: center;
    font-weight: 600;
    margin-bottom: 15px;
}
.modal_row_property {
    display: flex;
    justify-content: flex-start;
    grid-gap: 20px;
    font-size: 16px;
}
.modal_row_property_title {
    font-weight: 600;
}

.modal_row_property_value {
}
/*-----------------------------------------------------------------*/
</style>