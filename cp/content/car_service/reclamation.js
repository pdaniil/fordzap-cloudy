/* =============================================================
 * CwReclamation — модуль «Рекламация (претензия)»
 * v1.0
 *  - без перерисовки всей модалки (сохраняется скролл)
 *  - выпадающий список для выбора типа требования
 *  - автоматический расчет даты
 * ============================================================= */
(function () {
    'use strict';

    var CONFIG = {
        API_URL:  '/cp/content/car_service/ajax_reclamation.php',
        PRINT_URL:'/cp/content/car_service/print_reclamation.php',
        // Список типовых требований из закона о защите прав потребителей
        CLAIM_OPTIONS: [
            'Безвозмездно устранить недостатки выполненной работы',
            'Соразмерно уменьшить цену выполненной работы',
            'Безвозмездно повторно выполнить работу',
            'Возместить расходы на устранение недостатков силами третьих лиц',
            'Возместить причиненные убытки'
        ]
    };

    // ---------- CSS (стили в темной теме, как в модуле акта приёма) ----------
    function injectCSS() {
        if (document.getElementById('cw-rec-styles')) return;
        var css = `
            .cw-rec-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 99999; display: flex; align-items: stretch; justify-content: center; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
            .cw-rec-modal { background: #1E1E2D; color: #fff; width: 100%; max-width: 720px; display: flex; flex-direction: column; overflow: hidden; border-radius: 0; }
            @media(min-width: 768px) { .cw-rec-overlay { align-items: center; padding: 20px; } .cw-rec-modal { border-radius: 16px; max-height: 92vh; border: 1px solid #3A3A50; } }
            .cw-rec-header { padding: 14px 18px; border-bottom: 1px solid #2E2E42; display: flex; align-items: center; justify-content: space-between; background: #1E1E2D; flex-shrink: 0; }
            .cw-rec-title { font-size: 16px; font-weight: 700; }
            .cw-rec-close { background: transparent; border: none; color: #A0A0B0; font-size: 26px; line-height: 1; cursor: pointer; padding: 0 4px; }
            .cw-rec-close:hover { color: #fff; }
            .cw-rec-body { flex: 1 1 auto; overflow-y: auto; padding: 14px 18px 22px; }
            .cw-rec-footer { padding: 12px 18px; border-top: 1px solid #2E2E42; display: flex; gap: 10px; background: #1E1E2D; flex-shrink: 0; padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)); }
            .cw-rec-btn { flex: 1; padding: 12px; border-radius: 10px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: .15s; }
            .cw-rec-btn-print { background: #4B5563; color: #fff; }
            .cw-rec-btn-print:hover { background: #374151; }
			.cw-rec-btn-blank { background: #6B7280; color: #fff; }
			.cw-rec-btn-blank:hover { background: #4B5563; }
            .cw-rec-btn-save { background: #3B82F6; color: #fff; }
            .cw-rec-btn-save:hover { background: #2563eb; }
            .cw-rec-btn-save:disabled { opacity: .55; cursor: default; }
            .cw-rec-field { margin-bottom: 14px; }
            .cw-rec-label { display: block; font-size: 13px; font-weight: 600; color: #A0A0B0; margin-bottom: 6px; }
            .cw-rec-input, .cw-rec-textarea, .cw-rec-select { width: 100%; padding: 10px 14px; border: 1px solid #3A3A50; border-radius: 10px; font-size: 14px; background: #2B2B3B; color: #FFFFFF; outline: none; font-family: inherit; }
            .cw-rec-textarea { min-height: 90px; resize: vertical; }
            .cw-rec-input:focus, .cw-rec-textarea:focus, .cw-rec-select:focus { border-color: #3B82F6; box-shadow: 0 0 0 2px rgba(59,130,246,.3); }
            .cw-rec-toast { position: fixed; left: 50%; bottom: 30px; transform: translateX(-50%); background: #10B981; color: #fff; padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; z-index: 100000; box-shadow: 0 4px 18px rgba(0,0,0,.4); animation: cwRecFade .25s ease-out; }
            .cw-rec-toast.err { background: #EF4444; }
            @keyframes cwRecFade { from { opacity: 0; transform: translate(-50%, 10px); } to { opacity: 1; transform: translate(-50%, 0); } }
        `;
        var s = document.createElement('style');
        s.id = 'cw-rec-styles';
        s.textContent = css;
        document.head.appendChild(s);
    }

    // ---------- Состояние ----------
    var S = null;

    function defaultState(options) {
        return {
            options: options,
            mode: options.containerId ? 'inline' : 'modal',
            carId: options.carId || 0,
            recordId: options.recordId || 0,
            car: null,
            client: null,
            reclamation: {
                claim_type: '',      // Тип требования
                defect_desc: '',     // Описание дефекта
                additional_info: '', // Дополнительная информация
                claim_date: ''       // Дата требования
            },
            loaded: false
        };
    }

    // ---------- Утилиты ----------
    function esc(s) { if (s === null || s === undefined) return ''; return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    function toast(msg, isError) { var t = document.createElement('div'); t.className = 'cw-rec-toast' + (isError ? ' err' : ''); t.textContent = msg; document.body.appendChild(t); setTimeout(function () { t.remove(); }, 2200); }

    // ---------- Рендеринг ----------
    function render(container) {
        var carHtml = '';
        if (S.car) {
            var c = S.car;
            carHtml =
                '<div style="background:#2B2B3B; border:1px solid #3A3A50; border-radius:12px; padding:12px 14px; margin-bottom:14px; font-size:13px; line-height:1.45;">' +
                '<b>' + esc(((c.marka || '') + ' ' + (c.model || '')).trim()) + '</b><br>' +
                '<span style="color:#A0A0B0;">VIN:</span> <b>' + esc(c.vin || '—') + '</b> · ' +
                '<span style="color:#A0A0B0;">Гос.номер:</span> <b>' + esc(c.license_plate || '—') + '</b>' +
                '</div>';
        }

        var claimOptionsHtml = CONFIG.CLAIM_OPTIONS.map(function (opt) {
            var selected = S.reclamation.claim_type === opt ? ' selected' : '';
            return '<option value="' + esc(opt) + '"' + selected + '>' + esc(opt) + '</option>';
        }).join('');

        var html =
            (S.mode === 'modal' ? '<div class="cw-rec-overlay"><div class="cw-rec-modal">' : '') +
            '<div class="cw-rec-header">' +
            '<div class="cw-rec-title">📄 Рекламация (претензия)</div>' +
            (S.mode === 'modal' ? '<button type="button" class="cw-rec-close" data-action="close">×</button>' : '') +
            '</div>' +
            '<div class="cw-rec-body">' +
            carHtml +
            // Поля формы
            '<div class="cw-rec-field">' +
            '<label class="cw-rec-label">Дата составления претензии</label>' +
            '<input type="date" class="cw-rec-input" data-field="claim_date" value="' + esc(S.reclamation.claim_date) + '">' +
            '</div>' +
            '<div class="cw-rec-field">' +
            '<label class="cw-rec-label">Суть требования <span style="color:#f97316;">*</span></label>' +
            '<select class="cw-rec-select" data-field="claim_type">' +
            '<option value="">— Выберите требование —</option>' +
            claimOptionsHtml +
            '</select>' +
            '</div>' +
            '<div class="cw-rec-field">' +
            '<label class="cw-rec-label">Описание выявленных недостатков <span style="color:#f97316;">*</span></label>' +
            '<textarea class="cw-rec-textarea" data-field="defect_desc" placeholder="Подробно опишите, какие именно недостатки были обнаружены после ремонта...">' + esc(S.reclamation.defect_desc) + '</textarea>' +
            '</div>' +
            '<div class="cw-rec-field">' +
            '<label class="cw-rec-label">Дополнительная информация</label>' +
            '<textarea class="cw-rec-textarea" data-field="additional_info" placeholder="Например: приложенные документы, особые обстоятельства...">' + esc(S.reclamation.additional_info) + '</textarea>' +
            '</div>' +
            '</div>' +
            '<div class="cw-rec-footer">' +
			'<button type="button" class="cw-rec-btn cw-rec-btn-print" data-action="print">🖨️ Печать</button>' +
			'<button type="button" class="cw-rec-btn cw-rec-btn-save" data-action="save">💾 Сохранить</button>' +
			'</div>' +
            (S.mode === 'modal' ? '</div></div>' : '');

        container.innerHTML = html;

        // Привязка событий к полям
        container.querySelectorAll('[data-field]').forEach(function (el) {
            el.addEventListener('input', function () {
                S.reclamation[this.dataset.field] = this.value;
            });
        });
    }

    // ---------- Загрузка и сохранение ----------
    function load() {
        return fetch(CONFIG.API_URL + '?action=get&car_id=' + S.carId + '&record_id=' + S.recordId, {
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.status) throw new Error((res && res.message) || 'Ошибка загрузки');
                S.car = res.car || null;
                S.client = res.client || null;
                if (res.reclamation) {
                    Object.assign(S.reclamation, res.reclamation);
                }
                // Если дата не заполнена, ставим сегодняшнюю
                if (!S.reclamation.claim_date) {
                    S.reclamation.claim_date = new Date().toISOString().split('T')[0];
                }
                S.loaded = true;
            });
    }

    function save() {
        // Простая валидация
        if (!S.reclamation.claim_type || !S.reclamation.defect_desc) {
            toast('Заполните обязательные поля', true);
            return;
        }

        var btn = S._container.querySelector('[data-action="save"]');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Сохранение...'; }

        var payload = {
            action: 'save',
            car_id: S.carId,
            record_id: S.recordId,
            claim_type: S.reclamation.claim_type,
            defect_desc: S.reclamation.defect_desc,
            additional_info: S.reclamation.additional_info,
            claim_date: S.reclamation.claim_date
        };

        var body = new URLSearchParams();
        Object.keys(payload).forEach(function (k) { body.append(k, payload[k]); });

        fetch(CONFIG.API_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (r) { return r.json(); })
            .then(function (res) {
                if (btn) { btn.disabled = false; btn.textContent = '💾 Сохранить'; }
                if (res && res.status) {
                    toast('Рекламация сохранена');
                } else {
                    toast((res && res.message) || 'Ошибка сохранения', true);
                }
            }).catch(function () {
                if (btn) { btn.disabled = false; btn.textContent = '💾 Сохранить'; }
                toast('Ошибка соединения', true);
            });
    }

    // ---------- События ----------
    function bindEvents() {
        var c = S._container;
        c.addEventListener('click', function (e) {
            var t = e.target;
            if (t.closest('[data-action="close"]')) { close(); return; }
            if (t.closest('[data-action="print"]')) {
                var url = CONFIG.PRINT_URL + '?car_id=' + S.carId + '&record_id=' + S.recordId;
                window.open(url, '_blank');
                return;
            }
			if (t.closest('[data-action="print-blank"]')) {
				var urlBlank = CONFIG.PRINT_URL + '?blank=1&car_id=' + S.carId +
							   (S.recordId ? '&record_id=' + S.recordId : '');
				window.open(urlBlank, '_blank');
				return;
			}
            if (t.closest('[data-action="save"]')) { save(); return; }
        });
    }

    // ---------- Публичное API ----------
    function open(options) {
        injectCSS();
        options = options || {};
        if (!options.carId) { alert('Не указан автомобиль'); return; }
        if (S && S._container) close();

        S = defaultState(options);
        S.mode = 'modal';
        var host = document.createElement('div');
        host.id = 'cw-rec-host';
        document.body.appendChild(host);
        S._container = host;
        host.innerHTML = '<div class="cw-rec-overlay"><div class="cw-rec-modal"><div class="cw-rec-body"><div style="padding:40px;text-align:center;color:#A0A0B0;">Загрузка...</div></div></div></div>';

        load().then(function () { render(host); bindEvents(); })
            .catch(function (e) {
                host.innerHTML = '<div class="cw-rec-overlay"><div class="cw-rec-modal"><div class="cw-rec-body"><div style="padding:40px;text-align:center;color:#EF4444;">' + esc(e.message || 'Ошибка загрузки') + '</div></div></div></div>';
            });
    }

    function close() {
        if (S && S._container && S.mode === 'modal') S._container.remove();
        S = null;
    }

    window.CwReclamation = {
        open: open,
        close: close,
        config: CONFIG
    };
})();