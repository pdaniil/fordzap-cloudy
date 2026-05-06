<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Период
        </div>
        <div class="panel-body">
		<div class="sticker-form">
			<input type="text" class="form-control" style="max-width: 200px;" id="newText" placeholder="Текст стикера">
			<input type="color" class="form-control" style="max-width: 200px;" id="newColor" value="#ff0000">
			<button class="btn" onclick="addSticker()">Добавить</button>
		</div>

		<table class="stickers-table">
			<thead>
				<tr>
					<th>Текст</th>
					<th>Цвет</th>
					<th>Действие</th>
				</tr>
			</thead>
			<tbody id="stickersBody"></tbody>
		</table>
		</div>
	</div>
</div>
