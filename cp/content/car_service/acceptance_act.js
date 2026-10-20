/* =============================================================
 * CwAcceptanceAct — модуль «Акт приёма ТС»
 * v1.2
 *  - без перерисовки всей модалки (сохраняется скролл)
 *  - выпадающий список «Дефекты ЛКП»
 *  - улучшенный индикатор уровня топлива (E / \ \ 1/2 / / F)
 * ============================================================= */
(function () {
    'use strict';

    var ASSETS = '/content/carservice/admin_console/assets/';

    var CONFIG = {
        API_URL:  '/cp/content/car_service/ajax_acceptance_act.php',
        PRINT_URL:'/cp/content/car_service/print_acceptance_act.php',
        IMAGES: {
            left:  ASSETS + 'car_left.jpg',
            right: ASSETS + 'car_right.jpg',
            front: ASSETS + 'car_front.jpg',
            rear:  ASSETS + 'car_rear.jpg',
            top:   ASSETS + 'car_top.jpg'
        },
        VIEWS: [
            { key: 'left',  label: 'Лев'   },
            { key: 'right', label: 'Прав'  },
            { key: 'front', label: 'Перед' },
            { key: 'rear',  label: 'Зад'   },
            { key: 'top',   label: 'Верх'  }
        ],
        DAMAGE_TYPES: {
            dent:    { code: 'В', color: '#FF3B30', label: 'Вмятина'  },
            chip:    { code: 'С', color: '#007AFF', label: 'Скол'     },
            crack:   { code: 'Т', color: '#FF9500', label: 'Трещина'  },
            scratch: { code: 'Ц', color: '#CC73E1', label: 'Царапина' }
        },
        PAINT_DEFECTS_OPTIONS: [
            'Отсутствуют',
            'Сколы',
            'Царапины',
            'Вмятины',
            'Потёртости',
            'Отслоение лака',
            'Коррозия',
            'Требуется покраска'
        ],
        EQUIPMENT_PRESETS: [
            'Домкрат', 'Запасное колесо', 'Балонный ключ',
            'Аптечка', 'Огнетушитель', 'Знак аварийной остановки',
            'Магнитола', 'Коврики', 'Компрессор'
        ],
        FUEL_LABELS: ['Пусто', '1/4', '1/2', '3/4', 'Полный']
    };

    /* ---------- CSS ---------- */
    function injectCSS() {
        if (document.getElementById('cw-aa-styles')) return;
        var css = `
            .cw-aa-overlay {
                position: fixed; inset: 0; background: rgba(0,0,0,.6);
                z-index: 99999; display: flex; align-items: stretch;
                justify-content: center; padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .cw-aa-modal {
                background: #1E1E2D; color: #fff; width: 100%; max-width: 720px;
                display: flex; flex-direction: column; overflow: hidden;
                border-radius: 0;
            }
            @media(min-width: 768px) {
                .cw-aa-overlay { align-items: center; padding: 20px; }
                .cw-aa-modal { border-radius: 16px; max-height: 92vh; border: 1px solid #3A3A50; }
            }
            .cw-aa-header {
                padding: 14px 18px; border-bottom: 1px solid #2E2E42;
                display: flex; align-items: center; justify-content: space-between;
                background: #1E1E2D; flex-shrink: 0;
            }
            .cw-aa-title { font-size: 16px; font-weight: 700; }
            .cw-aa-close {
                background: transparent; border: none; color: #A0A0B0;
                font-size: 26px; line-height: 1; cursor: pointer; padding: 0 4px;
            }
            .cw-aa-close:hover { color: #fff; }
            .cw-aa-body { flex: 1 1 auto; overflow-y: auto; padding: 14px 18px 22px; }
            .cw-aa-footer {
                padding: 12px 18px; border-top: 1px solid #2E2E42;
                display: flex; gap: 10px; background: #1E1E2D; flex-shrink: 0;
                padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            }
            .cw-aa-btn {
                flex: 1; padding: 12px; border-radius: 10px; border: none;
                font-size: 14px; font-weight: 600; cursor: pointer;
                font-family: inherit; transition: .15s;
            }
            .cw-aa-btn-print { background: #4B5563; color: #fff; }
            .cw-aa-btn-print:hover { background: #374151; }
            .cw-aa-btn-save { background: #3B82F6; color: #fff; }
            .cw-aa-btn-save:hover { background: #2563eb; }
            .cw-aa-btn-save:disabled { opacity: .55; cursor: default; }

            .cw-aa-car-info {
                background: #2B2B3B; border: 1px solid #3A3A50; border-radius: 12px;
                padding: 12px 14px; margin-bottom: 14px; font-size: 13px; line-height: 1.45;
            }
            .cw-aa-car-info b { color: #fff; }
            .cw-aa-car-info span { color: #A0A0B0; }

            .cw-aa-tabs {
                display: flex; gap: 4px; background: #2B2B3B; padding: 4px;
                border-radius: 12px; margin-bottom: 12px; overflow-x: auto;
            }
            .cw-aa-tabs::-webkit-scrollbar { display: none; }
            .cw-aa-tab {
                flex: 1 0 auto; min-width: 62px; padding: 9px 10px; border: none;
                background: transparent; color: #A0A0B0; border-radius: 9px;
                font-size: 13px; font-weight: 600; cursor: pointer;
                font-family: inherit; transition: .15s;
            }
            .cw-aa-tab.active { background: #3A3A50; color: #fff; }

            .cw-aa-types {
                display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap;
            }
            .cw-aa-type {
                flex: 1 1 0; min-width: 68px; padding: 9px 8px; border-radius: 9px;
                border: 2px solid #3A3A50; background: #2B2B3B; color: #D1D5DB;
                font-size: 12px; font-weight: 600; cursor: pointer;
                font-family: inherit; transition: .15s; text-align: center;
            }
            .cw-aa-type .code {
                display: inline-block; width: 20px; height: 20px; line-height: 20px;
                border-radius: 6px; margin-right: 4px; text-align: center;
                font-weight: 800; color: #fff; font-size: 11px;
            }
            .cw-aa-type.active { background: #313146; border-color: #fff; color: #fff; }

			.cw-aa-image-wrap {
				background: #fff; border-radius: 12px;
				overflow: hidden; margin-bottom: 16px; user-select: none;
				touch-action: manipulation;
			}
			.cw-aa-image-inner {
				position: relative;
				display: block;
				width: 100%;
				line-height: 0;
				font-size: 0;
			}
			.cw-aa-image-inner img {
				display: block; width: 100%; height: auto; pointer-events: none;
			}
			.cw-aa-markers-layer {
				position: absolute; inset: 0; cursor: crosshair;
			}
            .cw-aa-marker {
                position: absolute; width: 26px; height: 26px;
                transform: translate(-50%, -50%) rotate(45deg);
                border-radius: 6px; display: flex; align-items: center;
                justify-content: center; cursor: pointer;
                box-shadow: 0 1px 4px rgba(0,0,0,.4);
                border: 2px solid rgba(255,255,255,.85);
            }
            .cw-aa-marker span {
                transform: rotate(-45deg); color: #fff; font-weight: 800;
                font-size: 12px; line-height: 1;
            }
            .cw-aa-hint {
                text-align: center; color: #6B7280; font-size: 12px;
                margin: -8px 0 14px;
            }

            .cw-aa-field { margin-bottom: 14px; }
            .cw-aa-label {
                display: block; font-size: 13px; font-weight: 600;
                color: #A0A0B0; margin-bottom: 6px;
            }

            .cw-aa-input, .cw-aa-textarea {
                width: 100%; padding: 10px 14px; border: 1px solid #3A3A50;
                border-radius: 10px; font-size: 14px; background: #2B2B3B;
                color: #FFFFFF; outline: none; font-family: inherit;
                box-sizing: border-box;
            }
            .cw-aa-textarea { min-height: 70px; resize: vertical; }
            .cw-aa-input:focus, .cw-aa-textarea:focus {
                border-color: #3B82F6; box-shadow: 0 0 0 2px rgba(59,130,246,.3);
            }

            /* ---------- Селект-дропдаун (Дефекты ЛКП) ---------- */
            .cw-aa-paint-block { position: relative; }
            .cw-aa-select {
                width: 100%; padding: 10px 40px 10px 14px;
                border: 1px solid #3A3A50; border-radius: 10px;
                font-size: 14px; background: #2B2B3B; color: #FFFFFF;
                cursor: pointer; position: relative; min-height: 42px;
                display: flex; align-items: center; user-select: none;
                transition: border-color .15s, box-shadow .15s;
                box-sizing: border-box;
            }
            .cw-aa-select:hover { border-color: #4B5563; }
            .cw-aa-select.open {
                border-color: #3B82F6;
                box-shadow: 0 0 0 2px rgba(59,130,246,.3);
            }
            .cw-aa-select-value {
                overflow: hidden; text-overflow: ellipsis;
                white-space: nowrap; flex: 1;
            }
            .cw-aa-select-value.placeholder { color: #6B7280; }
            .cw-aa-select-arrow {
                position: absolute; right: 14px; top: 50%;
                transform: translateY(-50%); color: #A0A0B0;
                font-size: 11px; transition: transform .2s;
                pointer-events: none;
            }
            .cw-aa-select.open .cw-aa-select-arrow {
                transform: translateY(-50%) rotate(180deg);
            }
            .cw-aa-dropdown {
                position: absolute; left: 0; right: 0;
                background: #2B2B3B; border: 1px solid #3A3A50;
                border-radius: 10px; margin-top: 4px;
                z-index: 200; max-height: 260px; overflow-y: auto;
                box-shadow: 0 8px 24px rgba(0,0,0,.55);
                display: none;
            }
            .cw-aa-dropdown.open { display: block; }
            .cw-aa-dropdown label {
                display: flex; align-items: center; gap: 10px;
                padding: 10px 14px; cursor: pointer; font-size: 14px;
                color: #D1D5DB; margin: 0;
                border-bottom: 1px solid #2E2E42;
                user-select: none;
            }
            .cw-aa-dropdown label:last-child { border-bottom: none; }
            .cw-aa-dropdown label:hover { background: #3A3A50; color: #fff; }
            .cw-aa-dropdown input[type="checkbox"] {
                width: 18px; height: 18px; accent-color: #3B82F6;
                cursor: pointer; flex-shrink: 0; pointer-events: none;
            }

            /* ---------- Индикатор топлива ---------- */
            .cw-aa-fuel {
                display: flex; align-items: center; justify-content: center;
                gap: 4px; padding: 14px 10px;
                background: #2B2B3B; border: 1px solid #3A3A50;
                border-radius: 10px;
            }
            .cw-aa-fuel-seg {
                width: 20px; height: 30px;
                border: none; background: #3A3A50;
                cursor: pointer; border-radius: 3px;
                transition: background .15s; padding: 0; flex-shrink: 0;
            }
            .cw-aa-fuel-seg.left  { transform: skewX(-18deg); }
            .cw-aa-fuel-seg.right { transform: skewX(18deg); }
            .cw-aa-fuel-seg:hover { background: #4B5563; }
            .cw-aa-fuel-seg.active { background: #10B981; }
            .cw-aa-fuel-seg.active:hover { background: #059669; }
            .cw-aa-fuel-label {
                font-size: 14px; font-weight: 800;
                color: #A0A0B0; padding: 0 4px; line-height: 1;
            }
            .cw-aa-fuel-label.mid {
                font-size: 10px; color: #6B7280;
                font-weight: 700; padding: 0 2px;
            }
            .cw-aa-fuel-value {
                font-size: 12px; color: #10B981;
                font-weight: 700; margin-left: 10px;
                min-width: 52px; text-align: left;
            }

            /* ---------- Комплектность (tokenfield) ---------- */
            .cw-aa-tokenfield {
                display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 10px;
                background: #2B2B3B; border: 1px solid #3A3A50; border-radius: 10px;
                min-height: 44px; box-sizing: border-box;
            }
            .cw-aa-tokenfield input {
                flex: 1 1 90px; min-width: 90px; background: transparent;
                border: none; color: #fff; outline: none; font-size: 14px;
                font-family: inherit; padding: 4px;
            }
            .cw-aa-token {
                display: inline-flex; align-items: center; gap: 6px;
                background: #3A3A50; color: #fff; padding: 4px 6px 4px 12px;
                border-radius: 12px; font-size: 13px;
            }
            .cw-aa-token .x {
                background: transparent; border: none; color: #A0A0B0;
                font-size: 16px; line-height: 1; cursor: pointer; padding: 0 4px;
            }
            .cw-aa-token .x:hover { color: #fff; }
            .cw-aa-presets {
                margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;
            }
            .cw-aa-preset {
                background: transparent; border: 1px dashed #4B5563;
                color: #A0A0B0; font-size: 12px; padding: 4px 10px;
                border-radius: 12px; cursor: pointer; font-family: inherit;
            }
            .cw-aa-preset:hover { border-color: #3B82F6; color: #fff; }

            .cw-aa-toast {
                position: fixed; left: 50%; bottom: 30px; transform: translateX(-50%);
                background: #10B981; color: #fff; padding: 10px 20px;
                border-radius: 30px; font-size: 14px; font-weight: 600;
                z-index: 100000; box-shadow: 0 4px 18px rgba(0,0,0,.4);
                animation: cwAaFade .25s ease-out;
            }
            .cw-aa-toast.err { background: #EF4444; }
            @keyframes cwAaFade {
                from { opacity: 0; transform: translate(-50%, 10px); }
                to   { opacity: 1; transform: translate(-50%, 0); }
            }
        `;
        var s = document.createElement('style');
        s.id = 'cw-aa-styles';
        s.textContent = css;
        document.head.appendChild(s);
    }

    /* ---------- state ---------- */
    var S = null;

    function defaultState(options) {
        return {
            options: options,
            mode: options.containerId ? 'inline' : 'modal',
            carId: options.carId || 0,
            recordId: options.recordId || 0,
            serviceId: options.serviceId || 0,
            car: null,
            act: {
                damages: [],
                paint_defects: '',      // строка с разделителем ', '
                special_notes: '',
                equipment: [],
                fuel_level: 0,
                mileage: null
            },
            activeView: 'left',
            activeDamageType: 'dent',
            paintDropdownOpen: false,
            loaded: false
        };
    }

    /* ---------- helpers ---------- */
    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    function toast(msg, isError) {
        var t = document.createElement('div');
        t.className = 'cw-aa-toast' + (isError ? ' err' : '');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 2200);
    }
    function paintDefectsArr() {
        if (!S.act.paint_defects) return [];
        return S.act.paint_defects.split(',').map(function(s){return s.trim();}).filter(Boolean);
    }
    function paintDefectsSet(arr) {
        S.act.paint_defects = arr.join(', ');
    }

    /* ---------- render: полное окно (только при открытии) ---------- */
    function render(container) {
        var v = S.activeView;
        var imgSrc = CONFIG.IMAGES[v] || '';

        var tabsHtml = CONFIG.VIEWS.map(function (vw) {
            return '<button type="button" class="cw-aa-tab' + (vw.key === v ? ' active' : '') +
                   '" data-view="' + vw.key + '">' + vw.label + '</button>';
        }).join('');

        var typesHtml = Object.keys(CONFIG.DAMAGE_TYPES).map(function (k) {
            var t = CONFIG.DAMAGE_TYPES[k];
            return '<button type="button" class="cw-aa-type' +
                   (k === S.activeDamageType ? ' active' : '') +
                   '" data-type="' + k + '">' +
                   '<span class="code" style="background:' + t.color + '">' + t.code + '</span>' +
                   t.label + '</button>';
        }).join('');

        var carHtml = '';
        if (S.car) {
            var c = S.car;
            carHtml =
                '<div class="cw-aa-car-info">' +
                  '<b>' + esc(((c.marka || '') + ' ' + (c.model || '')).trim()) + '</b><br>' +
                  '<span>VIN:</span> <b>' + esc(c.vin || '—') + '</b> · ' +
                  '<span>Гос.номер:</span> <b>' + esc(c.license_plate || '—') + '</b> · ' +
                  '<span>Год:</span> <b>' + esc(c.year || '—') + '</b>' +
                '</div>';
        }
  
        var html =
            (S.mode === 'modal'
                ? '<div class="cw-aa-overlay"><div class="cw-aa-modal">'
                : '') +
              '<div class="cw-aa-header">' +
                '<div class="cw-aa-title">📋 Акт приёма ТС</div>' +
                (S.mode === 'modal'
                    ? '<button type="button" class="cw-aa-close" data-action="close">×</button>'
                    : '') +
              '</div>' +
              '<div class="cw-aa-body">' +
                carHtml +
                '<div class="cw-aa-tabs">' + tabsHtml + '</div>' +
                '<div class="cw-aa-types">' + typesHtml + '</div>' +
                '<div class="cw-aa-image-wrap" data-view="' + v + '">' +
				  '<div class="cw-aa-image-inner">' +
					'<img src="' + imgSrc + '" alt="">' +
					'<div class="cw-aa-markers-layer"></div>' +
				  '</div>' +
				'</div>' +
                '<div class="cw-aa-hint">Тапните по изображению, чтобы поставить метку. Тап по метке — удалить.</div>' +

                /* ЛКП: дропдаун */
                '<div class="cw-aa-field">' +
                  '<label class="cw-aa-label">Дефекты ЛКП</label>' +
                  '<div class="cw-aa-paint-block">' +
                    '<div class="cw-aa-select" data-paint-toggle>' +
                      '<span class="cw-aa-select-value placeholder">Выберите...</span>' +
                      '<span class="cw-aa-select-arrow">▼</span>' +
                    '</div>' +
                    '<div class="cw-aa-dropdown" data-paint-dropdown></div>' +
                  '</div>' +
                '</div>' +

                '<div class="cw-aa-field">' +
                  '<label class="cw-aa-label">Особые отметки</label>' +
                  '<textarea class="cw-aa-textarea" data-field="special_notes" ' +
                    'placeholder="Особые отметки...">' + esc(S.act.special_notes) + '</textarea>' +
                '</div>' +

                '<div class="cw-aa-field">' +
                  '<label class="cw-aa-label">Уровень топлива</label>' +
                  '<div class="cw-aa-fuel-inner"></div>' +
                '</div>' +

                '<div class="cw-aa-field">' +
                  '<label class="cw-aa-label">Пробег, км</label>' +
                  '<input type="number" class="cw-aa-input" data-field="mileage" min="0" ' +
                    'placeholder="Введите пробег..." ' +
                    'value="' + (S.act.mileage != null ? esc(S.act.mileage) : '') + '">' +
                '</div>' +

                '<div class="cw-aa-field">' +
                  '<label class="cw-aa-label">Комплектность</label>' +
                  '<div class="cw-aa-equipment-inner"></div>' +
                '</div>' +
              '</div>' +
              '<div class="cw-aa-footer">' +
                '<button type="button" class="cw-aa-btn cw-aa-btn-print" data-action="print">🖨️ Печать</button>' +
                '<button type="button" class="cw-aa-btn cw-aa-btn-save" data-action="save">💾 Сохранить</button>' +
              '</div>' +
            (S.mode === 'modal' ? '</div></div>' : '');

        container.innerHTML = html;

        // текстовые поля — привязываем один раз
        var mileageInput = container.querySelector('[data-field="mileage"]');
        if (mileageInput) {
            var onMileage = function () {
                var n = parseInt(this.value, 10);
                S.act.mileage = isNaN(n) ? null : n;
            };
            mileageInput.addEventListener('input', onMileage);
            mileageInput.addEventListener('blur',  onMileage);
        }
        var notesEl = container.querySelector('[data-field="special_notes"]');
        if (notesEl) {
            notesEl.addEventListener('input', function () {
                S.act.special_notes = this.value;
            });
        }

        // подкомпоненты
        renderMarkers();
        renderPaint();
        renderFuel();
        renderEquipment();
    }

    /* ---------- подкомпоненты (точечно) ---------- */

    function renderMarkers() {
        var layer = S._container.querySelector('.cw-aa-markers-layer');
        if (!layer) return;
        var v = S.activeView;
        var html = '';
        S.act.damages.forEach(function (m, idx) {
            if (m.view !== v) return;
            var t = CONFIG.DAMAGE_TYPES[m.type] || CONFIG.DAMAGE_TYPES.dent;
            html +=
                '<div class="cw-aa-marker" data-idx="' + idx + '" ' +
                'style="left:' + m.x + '%;top:' + m.y + '%;background:' + t.color + ';">' +
                '<span>' + t.code + '</span></div>';
        });
        layer.innerHTML = html;
    }

    function renderPaint() {
        var block = S._container.querySelector('.cw-aa-paint-block');
        if (!block) return;

        var selected = paintDefectsArr();

        var $val = block.querySelector('.cw-aa-select-value');
        if ($val) {
            if (selected.length === 0) {
                $val.textContent = 'Выберите...';
                $val.classList.add('placeholder');
            } else {
                $val.textContent = selected.join(', ');
                $val.classList.remove('placeholder');
            }
        }

        var $dd = block.querySelector('[data-paint-dropdown]');
        if ($dd) {
            var html = CONFIG.PAINT_DEFECTS_OPTIONS.map(function (opt) {
                var checked = selected.indexOf(opt) !== -1 ? ' checked' : '';
                return '<label data-paint-opt="' + esc(opt) + '">' +
                       '<input type="checkbox"' + checked + ' tabindex="-1">' +
                       '<span>' + esc(opt) + '</span>' +
                       '</label>';
            }).join('');
            $dd.innerHTML = html;
        }
    }

    function renderFuel() {
        var wrap = S._container.querySelector('.cw-aa-fuel-inner');
        if (!wrap) return;
        var lvl = parseInt(S.act.fuel_level) || 0;
        var label = CONFIG.FUEL_LABELS[lvl] || 'Пусто';

        wrap.innerHTML =
            '<div class="cw-aa-fuel">' +
              '<span class="cw-aa-fuel-label">E</span>' +
              '<button type="button" class="cw-aa-fuel-seg left'   + (lvl >= 1 ? ' active' : '') + '" data-fuel="1"></button>' +
              '<button type="button" class="cw-aa-fuel-seg left'   + (lvl >= 2 ? ' active' : '') + '" data-fuel="2"></button>' +
              '<span class="cw-aa-fuel-label mid">1/2</span>' +
              '<button type="button" class="cw-aa-fuel-seg right'  + (lvl >= 3 ? ' active' : '') + '" data-fuel="3"></button>' +
              '<button type="button" class="cw-aa-fuel-seg right'  + (lvl >= 4 ? ' active' : '') + '" data-fuel="4"></button>' +
              '<span class="cw-aa-fuel-label">F</span>' +
              '<span class="cw-aa-fuel-value">' + esc(label) + '</span>' +
            '</div>';
    }

    function renderEquipment() {
        var wrap = S._container.querySelector('.cw-aa-equipment-inner');
        if (!wrap) return;

        var eqHtml = S.act.equipment.map(function (tag, idx) {
            return '<span class="cw-aa-token" data-eq-idx="' + idx + '">' +
                   esc(tag) +
                   '<button type="button" class="x" data-eq-remove="' + idx + '">×</button>' +
                   '</span>';
        }).join('');

        var presetsHtml = CONFIG.EQUIPMENT_PRESETS.map(function (p) {
            return '<button type="button" class="cw-aa-preset" data-eq-preset="' +
                   esc(p) + '">+ ' + esc(p) + '</button>';
        }).join('');

        wrap.innerHTML =
            '<div class="cw-aa-tokenfield" data-tokenfield>' + eqHtml +
              '<input type="text" placeholder="Введите и нажмите Enter" data-eq-input>' +
            '</div>' +
            '<div class="cw-aa-presets">' + presetsHtml + '</div>';
    }

    /* ---------- события (глобальный делегированный слушатель) ---------- */
    function bindEvents() {
        var c = S._container;

        c.addEventListener('click', function (e) {
            var t = e.target;

            // клик ВНЕ блока ЛКП — закрываем dropdown
            if (!t.closest('.cw-aa-paint-block') && S.paintDropdownOpen) {
                closePaintDropdown();
            }

            if (t.closest('[data-action="close"]')) { close(); return; }

            if (t.closest('[data-action="print"]')) {
                var url = CONFIG.PRINT_URL + '?car_id=' + S.carId +
                          (S.recordId ? '&record_id=' + S.recordId : '');
                window.open(url, '_blank');
                return;
            }

            if (t.closest('[data-action="save"]')) { save(); return; }

            // табы
            var tab = t.closest('.cw-aa-tab');
            if (tab) {
                S.activeView = tab.dataset.view;
                c.querySelectorAll('.cw-aa-tab').forEach(function (b) {
                    b.classList.toggle('active', b.dataset.view === S.activeView);
                });
                var img = c.querySelector('.cw-aa-image-wrap img');
                if (img) img.src = CONFIG.IMAGES[S.activeView] || '';
                renderMarkers();
                return;
            }

            // тип повреждения
            var ty = t.closest('.cw-aa-type');
            if (ty) {
                S.activeDamageType = ty.dataset.type;
                c.querySelectorAll('.cw-aa-type').forEach(function (b) {
                    b.classList.toggle('active', b.dataset.type === S.activeDamageType);
                });
                return;
            }

            // удаление метки
            var marker = t.closest('.cw-aa-marker');
            if (marker) {
                var idx = parseInt(marker.dataset.idx, 10);
                if (confirm('Удалить метку?')) {
                    S.act.damages.splice(idx, 1);
                    renderMarkers();
                }
                return;
            }

            // тап по картинке — добавить метку
            var layer = t.closest('.cw-aa-markers-layer');
            if (layer) {
                var imgEl = c.querySelector('.cw-aa-image-wrap img');
                if (!imgEl) return;
                var rect = imgEl.getBoundingClientRect();
                var x = ((e.clientX - rect.left) / rect.width) * 100;
                var y = ((e.clientY - rect.top)  / rect.height) * 100;
                if (x < 0 || y < 0 || x > 100 || y > 100) return;
                S.act.damages.push({
                    view: S.activeView,
                    x: Math.round(x * 100) / 100,
                    y: Math.round(y * 100) / 100,
                    type: S.activeDamageType
                });
                renderMarkers();
                return;
            }

            /* ---------- ЛКП ---------- */
            var paintToggle = t.closest('[data-paint-toggle]');
            if (paintToggle) {
                togglePaintDropdown();
                return;
            }
            var paintLabel = t.closest('[data-paint-opt]');
            if (paintLabel) {
                e.preventDefault();
                var val = paintLabel.getAttribute('data-paint-opt');
                var arr = paintDefectsArr();
                var pos = arr.indexOf(val);
                if (pos !== -1) arr.splice(pos, 1);
                else arr.push(val);
                paintDefectsSet(arr);
                renderPaint();
                return;
            }

            // удаление тега комплектности
            var eqDel = t.closest('[data-eq-remove]');
            if (eqDel) {
                var ei = parseInt(eqDel.dataset.eqRemove, 10);
                S.act.equipment.splice(ei, 1);
                renderEquipment();
                return;
            }

            // пресет комплектности
            var preset = t.closest('[data-eq-preset]');
            if (preset) {
                var pval = preset.dataset.eqPreset;
                if (S.act.equipment.indexOf(pval) === -1) {
                    S.act.equipment.push(pval);
                    renderEquipment();
                }
                return;
            }

            // уровень топлива
            var fuelSeg = t.closest('[data-fuel]');
            if (fuelSeg) {
                var lvl = parseInt(fuelSeg.dataset.fuel, 10);
                S.act.fuel_level = (S.act.fuel_level === lvl) ? 0 : lvl;
                renderFuel();
                return;
            }
        });

        // keydown (Enter / запятая в поле комплектности)
        c.addEventListener('keydown', function (e) {
            var t = e.target;
            if (t && t.matches && t.matches('[data-eq-input]')) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    var v = t.value.trim();
                    if (v && S.act.equipment.indexOf(v) === -1) {
                        S.act.equipment.push(v);
                        renderEquipment();
                        var ni = S._container.querySelector('[data-eq-input]');
                        if (ni) ni.focus();
                    } else {
                        t.value = '';
                    }
                }
            }
        });
    }

    /* ---------- ЛКП dropdown ---------- */
    function openPaintDropdown() {
        S.paintDropdownOpen = true;
        var block = S._container.querySelector('.cw-aa-paint-block');
        if (!block) return;
        var sel = block.querySelector('.cw-aa-select');
        var dd  = block.querySelector('[data-paint-dropdown]');
        if (sel) sel.classList.add('open');
        if (dd)  dd.classList.add('open');
    }
    function closePaintDropdown() {
        S.paintDropdownOpen = false;
        var block = S._container.querySelector('.cw-aa-paint-block');
        if (!block) return;
        var sel = block.querySelector('.cw-aa-select');
        var dd  = block.querySelector('[data-paint-dropdown]');
        if (sel) sel.classList.remove('open');
        if (dd)  dd.classList.remove('open');
    }
    function togglePaintDropdown() {
        if (S.paintDropdownOpen) closePaintDropdown();
        else openPaintDropdown();
    }

    /* ---------- load / save ---------- */
    function load() {
        return fetch(CONFIG.API_URL + '?action=get&car_id=' + S.carId, {
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); })
          .then(function (res) {
              if (!res || !res.status) {
                  throw new Error((res && res.message) || 'Ошибка загрузки');
              }
              S.car = res.car || null;
              if (res.act) {
                  S.act.damages       = Array.isArray(res.act.damages) ? res.act.damages : [];
                  S.act.paint_defects = res.act.paint_defects || '';
                  S.act.special_notes = res.act.special_notes || '';
                  S.act.equipment     = Array.isArray(res.act.equipment) ? res.act.equipment : [];
                  S.act.fuel_level    = parseInt(res.act.fuel_level) || 0;
                  S.act.mileage       = (res.act.mileage === null || res.act.mileage === '')
                                        ? null : parseInt(res.act.mileage, 10);
              }
              S.loaded = true;
          });
    }

    function save() {
        var btn = S._container.querySelector('[data-action="save"]');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Сохранение...'; }

        var payload = {
            action: 'save',
            car_id: S.carId,
            damages: JSON.stringify(S.act.damages),
            paint_defects: S.act.paint_defects || '',
            special_notes: S.act.special_notes || '',
            equipment: JSON.stringify(S.act.equipment),
            fuel_level: parseInt(S.act.fuel_level) || 0,
            mileage: S.act.mileage === null || S.act.mileage === undefined ? '' : S.act.mileage
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
                  toast('Акт сохранён');
              } else {
                  toast((res && res.message) || 'Ошибка сохранения', true);
              }
          }).catch(function () {
              if (btn) { btn.disabled = false; btn.textContent = '💾 Сохранить'; }
              toast('Ошибка соединения', true);
          });
    }

    /* ---------- публичное API ---------- */
    function open(options) {
        injectCSS();
        options = options || {};
        if (!options.carId) { alert('Не указан автомобиль'); return; }
        if (S && S._container) close();

        S = defaultState(options);
        S.mode = 'modal';

        var host = document.createElement('div');
        host.id = 'cw-aa-host';
        document.body.appendChild(host);
        S._container = host;

        host.innerHTML = '<div class="cw-aa-overlay"><div class="cw-aa-modal">' +
            '<div class="cw-aa-body"><div style="padding:40px;text-align:center;color:#A0A0B0;">Загрузка...</div></div></div></div>';

        load().then(function () {
            render(host);
            bindEvents();
        }).catch(function (e) {
            host.innerHTML = '<div class="cw-aa-overlay"><div class="cw-aa-modal">' +
                '<div class="cw-aa-body"><div style="padding:40px;text-align:center;color:#EF4444;">' +
                esc(e.message || 'Ошибка загрузки') + '</div></div></div></div>';
        });
    }

    function init(options) {
        injectCSS();
        options = options || {};
        if (!options.containerId) { console.error('CwAcceptanceAct: containerId обязателен'); return; }
        var host = document.getElementById(options.containerId);
        if (!host) { console.error('CwAcceptanceAct: контейнер не найден'); return; }
        if (!options.carId) { host.innerHTML = '<div style="padding:20px;color:#EF4444;">Не указан автомобиль</div>'; return; }

        S = defaultState(options);
        S.mode = 'inline';
        S._container = host;

        host.innerHTML = '<div style="padding:40px;text-align:center;color:#A0A0B0;">Загрузка...</div>';
        load().then(function () {
            render(host);
            bindEvents();
        }).catch(function (e) {
            host.innerHTML = '<div style="padding:40px;color:#EF4444;">' + esc(e.message) + '</div>';
        });
    }

    function close() {
        if (S && S._container && S.mode === 'modal') {
            S._container.remove();
        }
        S = null;
    }

    window.CwAcceptanceAct = {
        open: open,
        init: init,
        close: close,
        config: CONFIG
    };
})();