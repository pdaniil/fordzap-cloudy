<?php
?>
<style>
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
  }

  /* Мобильная адаптация */
  @media(max-width: 768px) {
    .calendar-modal {
      flex-direction: column;
      width: 95%;
      max-height: 90vh;
      overflow-y: auto;
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
  
  
  .select2-container {
	  width: 300px !important;
  }
  
</style>