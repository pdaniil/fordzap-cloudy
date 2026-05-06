<?php ?>
<!-- ======= СТИЛИ ======= -->
<style>
:root{
    --bg:#f7f8fb;
    --panel:#ffffff;
    --muted:#778;
    --border:#e6e7ee;
    --accent:#1677ff;
    --accent-2:#5aa0ff;
    --text:#1f2328;
    --ok:#0ca678;
    --danger:#d9480f;
    --shadow:0 10px 24px rgba(31,35,40,.05);
    --radius:14px;
}
*{box-sizing:border-box}


.card{
    background:var(--panel);
    border:1px solid var(--border);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
}
h2,h3{margin:0 0 12px}
.section{padding:18px 20px;margin-bottom:18px}
.grid{
    display:grid;
    grid-template-columns: 42% 58%;
    gap:16px;
}
@media (max-width:980px){ .grid{grid-template-columns:1fr} }

.service-radio{
    display:flex; gap:10px; align-items:flex-start;
    width:100%; padding:12px; border:1px solid var(--border);
    border-radius:12px; background:#fafbff; margin-bottom:10px;
    cursor:pointer; transition:.2s ease;
}
.service-radio:hover{box-shadow:0 6px 18px rgba(0,0,0,.06)}
.service-radio input{margin-top:3px}
.service-meta .name{font-weight:700}
.service-meta .schedule{font-size:12px;color:var(--muted);opacity:.85;margin-top:2px}
.service-meta .address{font-size:13px;color:#3b3f46;margin-top:4px}

.map-wrap{height:330px;border-radius:12px;overflow:hidden;border:1px solid var(--border)}
#yandex-map{height:100%;width:100%}

.sto-block{display:none}
.sto-block.active{display:block}

.block-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.block-title .hint{color:var(--muted);font-size:12px}

.time-box{
    padding:16px; border:1px dashed var(--border);
    border-radius:12px; background:#fbfcff;
    color:#5b5e66;
	display: flex;
	justify-content: center;
}

.selected-table table{
    width:100%; border-collapse:collapse; overflow:hidden; border-radius:12px;
    border:1px solid var(--border);
}
.selected-table thead th{
    background:#f2f6ff; font-weight:700; text-align:left; padding:10px; border-bottom:1px solid var(--border)
}
.selected-table td{
    padding:10px; border-bottom:1px solid var(--border); vertical-align:top;
}
.selected-table tr:last-child td{border-bottom:none}
.price-badge{font-weight:700}
.badge-wp{color:#854d0e;background:#fef3c7;border:1px solid #fde68a;padding:3px 8px;border-radius:999px;font-size:12px}
.badge-na{color:#b42318;background:#fee4e2;border:1px solid #fecdca;padding:3px 8px;border-radius:999px;font-size:12px}

.reco-list{display:flex;flex-direction:column;gap:8px}
.reco-item{
    display:flex; gap:10px; align-items:flex-start;
    padding:10px; border:1px solid var(--border); border-radius:10px; background:#fff;
}

.actions{display:flex; gap:10px; align-items:center}
.btn{
    padding:10px 14px; border-radius:10px; border:1px solid var(--border);
    background:#fff; cursor:pointer; transition:.2s;
}
.btn.primary{background:var(--accent); border-color:var(--accent); color:#fff}
.btn:hover{transform:translateY(-1px)}
.btn:active{transform:translateY(0)}

.search{
    display:flex; align-items:center; gap:8px;
    background:#fff; border:1px solid var(--border); border-radius:12px; padding:8px 12px; margin-bottom:12px
}
.search input{
    border:none; outline:none; width:100%; font-size:14px; background:transparent
}
.group{margin-bottom:16px}
.group-title{font-weight:800; font-size:15px; margin:12px 0 10px}
.group-grid{display:grid; grid-template-columns:repeat(auto-fit, minmax(290px,1fr)); gap:10px}
.service-item{
    position:relative;
    display:flex; gap:10px; align-items:flex-start;
    border:1px solid var(--border); background:#fff; border-radius:12px; padding:10px;
}
.service-item.inactive{background:#f8f8f8; color:#98a2b3}
.service-item .stickers{position:absolute; top:8px; right:65px; display:flex; gap:6px}
.sticker{
    font-size:11px; color:#fff; padding:3px 6px; border-radius:999px; line-height:1
}

.total{
    margin-top:10px; display:flex; justify-content:flex-end; gap:12px; align-items:center;
    font-size:15px;
}
.total .sum{font-weight:900}

.muted{color:var(--muted)}
hr.sep{border:none;border-top:1px solid var(--border); margin:12px 0}


input[type=checkbox] {
	cursor: pointer;
}

  .time-record-container {
    max-width: 400px;
  }

  .time-record-display {
    border: 1px solid #ccc;
    padding: 12px;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    text-align: center;
    font-size: 16px;
  }

  /* Overlay */
  .calendar-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease forwards;
  }

  .calendar-modal {
    background: #fff;
    border-radius: 10px;
    width: 90%;
    max-width: 800px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: row;
    gap: 20px;
    animation: scaleIn 0.3s ease forwards;
	flex-wrap: wrap;
  }
	
	.sep_wrappers {
		display: grid;
		grid-template-columns: 1fr 1fr;
		grid-gap: 5px;
	}
	
  /* Мобильная адаптация */
  @media(max-width: 1000px) {
    .calendar-modal {
      flex-direction: column;
      width: 95%;
      max-height: 90vh;
      overflow-y: auto;
    }
	
	.sep_wrappers {
		grid-template-columns: 1fr;
	}
  }

  .calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }

  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
  }

  .calendar-grid div {
    padding: 10px;
    text-align: center;
    border-radius: 4px;
    font-size: 14px;
  }

  .calendar-grid .disabled {
    color: #bbb;
  }

  .calendar-grid .available {
    background: #e3f2fd;
    cursor: pointer;
    transition: background 0.2s;
  }

  .calendar-grid .available:hover {
    background: #90caf9;
  }

  .slots-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
  }

  .slots-container h5 {
    margin-bottom: 10px;
  }

  .slots-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .modal-actions {
    text-align: right;
    margin-top: 15px;
  }

  .btn-slot {
    padding: 8px 12px;
    border: 1px solid #2196f3;
    background: #fff;
    color: #2196f3;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .btn-slot:hover {
    background: #2196f3;
    color: #fff;
  }

  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
	
	.calendar-grid .today {
	  background: #fff3cd;
	  border: 1px solid #ffc107;
	  font-weight: bold;
	}

	.calendar-grid .selected {
	  background: #2196f3;
	  color: #fff;
	  font-weight: bold;
	}
	
  @keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }  
	
	
	.user_wrapper {
		display: flex;
		flex-direction: column;
		grid-gap: 15px;
	}
	
	.user_wrapper div {
		display: flex;
		flex-direction: column;
		grid-gap: 5px;
	}
	.confirm-button-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

	.btn-confirm {
		background: linear-gradient(135deg, #4CAF50, #45A049);
		color: #fff;
		font-size: 20px;
		font-weight: bold;
		padding: 15px 40px;
		border: none;
		border-radius: 12px;
		cursor: pointer;
		transition: all 0.3s ease;
		box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
	}

	.btn-confirm:hover {
		background: linear-gradient(135deg, #45A049, #4CAF50);
		transform: scale(1.05);
		box-shadow: 0 8px 18px rgba(0, 0, 0, 0.3);
	}

	.btn-confirm:active {
		transform: scale(0.98);
	}
/* Общий стиль */
.selected-table table td.service-cell {
    vertical-align: top;
}
.service-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.service-title {
    font-weight: 700;
}
.service-desc {
    font-size: 13px;
    color: #666;
    opacity: 0.8;
}

/* Мобильная версия */
@media(max-width: 768px) {
    .selected-table table thead {
        display: none; /* убираем шапку таблицы */
    }
    .selected-table table tr {
        display: grid;
        grid-template-columns: auto 1fr auto;
        grid-template-areas: 
            "check title price"
            "check desc price";
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .selected-table td {
        border: none;
        padding: 6px;
    }
    .selected-table td:first-child {
        grid-area: check;
        align-self: center;
    }
    .selected-table td.service-cell {
        grid-area: title;
    }
    .selected-table .service-desc {
        grid-area: desc;
        font-size: 12px;
        color: rgba(0,0,0,0.6);
        margin-top: 2px;
    }
    .selected-table td.price-cell {
        grid-area: price;
        text-align: right;
        font-weight: bold;
    }
}

</style>