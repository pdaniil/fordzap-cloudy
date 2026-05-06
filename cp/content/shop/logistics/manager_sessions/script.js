let sessionActive = false;
let sessionStartTime;
let timerInterval;
async function fetchRequest(action, data) {
    const baseUrl = document.getElementById('hiddenBaseUrl').dataset.baseUrl;
    const response = await fetch(`${baseUrl}/manager_sessions/session_handler.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action, ...data })
    });
    if (!response.ok) {
        throw new Error('Network response was not ok ' + response.statusText);
    }
    return await response.json();
}
async function checkSessionStatus(managerId) {
    const result = await fetchRequest('checkSessionStatus', { manager_id: managerId });
    if (result.status === 'session_active') {
        sessionActive = result.session_id;
        sessionStartTime = new Date(result.time_start * 1000);
        document.getElementById('sessionStart').style.display = 'block';
        document.getElementById('sessionStartTime').textContent = sessionStartTime.toLocaleString();
        startTimer();
        toggleButtons();
        document.getElementById('status').textContent = '';
    } else if (result.status === 'no_active_session') {
        sessionActive = false;
        clearInterval(timerInterval);
        toggleButtons();
        document.getElementById('status').textContent = 'Сессия не активна.';
    } else {
        console.error(result.message);
    }
}
function startTimer() {
    let elapsedTime = Math.floor((Date.now() - sessionStartTime.getTime()) / 1000);
    timerInterval = setInterval(() => {
        elapsedTime++;
        const hours = Math.floor(elapsedTime / 3600);
        const minutes = Math.floor((elapsedTime % 3600) / 60);
        const seconds = elapsedTime % 60;
        const timeTextElement = document.getElementById('timeText');
        timeTextElement.textContent = `${hours} ч ${minutes} мин ${seconds} сек`;
        setTimeout(() => {
            timeTextElement.classList.remove('updating');
        }, 500);
    }, 1000);
}
function toggleButtons() {
    const openSessionBtn = document.getElementById('openSessionBtn');
    const closeSessionBtn = document.getElementById('closeSessionBtn');
    if (sessionActive) {
        openSessionBtn.style.display = 'none';
        closeSessionBtn.style.display = 'inline-block';
    } else {
        openSessionBtn.style.display = 'inline-block';
        closeSessionBtn.style.display = 'none';
    }
}
document.getElementById('openSessionBtn').addEventListener('click', async () => {
    const managerId = document.querySelector('#manager_id').value;
		const officeId = document.querySelector('#office_id').value;
    const result = await fetchRequest('openSession', { manager_id: managerId, office_id: officeId });
    if (result.status === 'session_opened') {
        checkSessionStatus(managerId);
    }
});
document.getElementById('closeSessionBtn').addEventListener('click', async () => {
    const sessionId = sessionActive && document.getElementById('sessionStartTime').textContent ? sessionActive : null;
    const result = await fetchRequest('closeSession', { session_id: sessionId });
    if (result.status === 'session_closed') {
        sessionActive = false;
        clearInterval(timerInterval);
        toggleButtons();
        document.getElementById('status').textContent = 'Сессия закрыта.';
		const curent_balance = document.querySelector('#curent_balance').value;
		const notify_result = await fetchRequest('notifyAdmin', { session_id: sessionId, curent_balance : curent_balance });
		console.log(sessionId, notify_result);
    }
});
document.addEventListener('DOMContentLoaded', () => {
    const managerId = document.querySelector('#manager_id').value; 
    checkSessionStatus(managerId);
});
