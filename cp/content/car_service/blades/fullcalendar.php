<!--<script src="http://docpart/lib/fullcalendar/moment.min.js"></script>
<script src="http://docpart/lib/fullcalendar/fullcalendar.min.js"></script>
<script src="http://docpart/lib/fullcalendar/lang/ru.js"></script>
<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Планировщик
		</div>
		<div class="panel-body">
			<div id="calendar">
			</div>
		</div>
	</div>
</div>-->
<style>
    .fc-toolbar {
        background: #f7f8f9;
    }

    .fc-toolbar > div {
        padding: 12px 10px 0 10px;
    }

    .fc-toolbar h2 {
        font-size: 16px;
        margin-top: 6px;
        margin-bottom: 0;
        white-space: nowrap;
        color: rgb(101, 101, 101);
    }

    .fc-event {
        font-size: 1em;
    }

    .ui-draggable-dragging {
        width: 167px;
    }

    .slider {
        width: 100% !important;
    }

    #frame-preloader {
        position: absolute;
        left: 50%;
        top: 159px;
        margin-left: -16px;
    }

    .slider-div .tooltip {
        display: none !important;
    }

    .sidebar {
        width: 300px;
    }

    .sidebar h3 {
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        color: #7b8997;
        margin: 15px 15px;
    }

    .employee, #myEvents li {
        z-index: 2;
    }

    .employee-item {
        background-color: #ffffff !important;
        border-color: #138EF7 !important;
        color: #138EF7;
    }

    .employee-item .fc-title, .employee-item .desc {
        margin-left: 25px;
    }

    .employee-item:hover .fc-title {
        color: #138EF7;
    }

    .employee-item::before {
        font-family: 'Glyphicons Halflings';
        font-style: normal;
        font-weight: 400;
        content: "\e008";
        position: absolute;
        background-color: #138EF7;
        padding: 0 5px;
        line-height: 21px;
        color: #ffffff;
        top: -1px;
        left: -1px;
    }

    .overflow-sidebar {
        overflow: auto;
        height: 100%;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        position: absolute;
    }
    @media (min-width: 767px) {
        .pt-page#content {
           
        }
    }
</style>


<div class="modal pop" id="event-info-dlg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog small">
        <div class="modal-content">
            <div class="modal-body" style="font-size:18px;">
                <p class="event-dt"></p>
                <form id="edit-event-form" action="/calendar/event/update">
                    <input type="hidden" name="Event[id]" id="Event_id">
                    <input type="hidden" name="Event[title]" id="Event_title">
                    <div class="event-time-datepickers">
                        <input maxlength="10" type="datetime-local" class="date-mini datepicker-2 form-control" name="Event[start]" id="Event_start">
                        <input maxlength="10" type="datetime-local" class="date-mini datepicker-2 form-control" name="Event[end]" id="Event_end">
                    </div>
                </form>
                <strong><p class="event-title bold"></p></strong>
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0);" class="btn btn-success ajax-button" data-form="#edit-event-form">Сохранить</a>
                <a id="event-order-link" href="javascript:void(0);" class="remove-event-link ajaxlink btn btn-info">Открыть
                    заказ-наряд</a>
                <a href="javascript:void(0);" class="remove-event-link ajaxpostlink btn btn-danger" data-no-hash="true">Удалить</a>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>