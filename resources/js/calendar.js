import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // モーダル要素
    const taskModal = document.getElementById('taskModal');
    const taskModalCard = document.getElementById('taskModalCard');
    const taskForm = document.getElementById('taskForm');
    const modalTitle = document.getElementById('modalTitle');
    const modalBadge = document.getElementById('modalBadge');
    const taskIdInput = document.getElementById('taskId');
    const titleInput = document.getElementById('taskTitle');
    const startInput = document.getElementById('taskStart');
    const endInput = document.getElementById('taskEnd');
    const isAllDayInput = document.getElementById('taskIsAllDay');
    const descInput = document.getElementById('taskDescription');
    const colorInput = document.getElementById('taskColor');
    const isCompletedInput = document.getElementById('taskIsCompleted');
    const deleteBtn = document.getElementById('deleteTaskBtn');
    const completedContainer = document.getElementById('completedContainer');
    const colorButtons = document.querySelectorAll('.color-btn');

    // カラーパレットの選択状態を更新
    function setSelectedColor(color) {
        const targetColor = color || '#4f46e5';
        colorInput.value = targetColor;

        colorButtons.forEach((btn) => {
            const btnColor = btn.getAttribute('data-color');
            const check = btn.querySelector('.checkmark');
            if (btnColor && btnColor.toLowerCase() === targetColor.toLowerCase()) {
                btn.classList.add('ring-2', 'ring-indigo-600');
                if (check) check.classList.remove('hidden');
            } else {
                btn.classList.remove('ring-2', 'ring-indigo-600');
                if (check) check.classList.add('hidden');
            }
        });
    }

    // 各カラーボタンクリック時
    colorButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            setSelectedColor(btn.getAttribute('data-color'));
        });
    });

    // Dateオブジェクトを YYYY-MM-DDTHH:mm (datetime-local用) に変換
    function formatDateTimeLocal(date) {
        if (!date) return '';
        const d = (date instanceof Date) ? date : new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        const year = d.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());
        const hours = pad(date.getHours());
        const minutes = pad(date.getMinutes());
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    // YYYY-MM-DD 文字列と HH:mm から datetime-local 用文字列を作成
    function formatYmdWithTime(date, timeStr = '10:00') {
        const d = (date instanceof Date) ? date : new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        const year = d.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());
        return `${year}-${month}-${day}T${timeStr}`;
    }

    // input[type=datetime-local] の値を "YYYY-MM-DD HH:mm:ss" 形式（サーバー送信形式）に変換
    function toServerDateTimeString(inputVal) {
        if (!inputVal) return '';
        const clean = inputVal.replace('T', ' ');
        return clean.length === 16 ? `${clean}:00` : clean;
    }

    // Dateオブジェクトをローカルタイム基準の "YYYY-MM-DD HH:mm:ss" に変換
    function dateToServerDateTimeString(date) {
        if (!date) return '';
        const d = (date instanceof Date) ? date : new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        const year = d.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());
        const hours = pad(date.getHours());
        const minutes = pad(date.getMinutes());
        const seconds = pad(date.getSeconds());
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    function openModal(mode = 'create', data = {}) {
        taskForm.reset();
        taskIdInput.value = data.id || '';

        if (mode === 'create') {
            modalTitle.textContent = 'スケジュール追加';
            if (modalBadge) {
                modalBadge.textContent = '新規追加';
                modalBadge.className = 'text-xs px-2.5 py-0.5 rounded-full font-semibold bg-indigo-50 text-indigo-700';
            }
            if (taskModalCard) {
                taskModalCard.classList.remove('border-amber-400');
                taskModalCard.classList.add('border-indigo-400');
            }

            deleteBtn.classList.add('hidden');
            completedContainer.classList.add('hidden');

            const now = new Date();

            if (data.start) {
                const startDate = new Date(data.start);
                if (data.allDay) {
                    // 月ビューなどで終日枠をクリックした場合：クリックした日の 09:00 〜 10:00 を初期セット
                    startInput.value = formatYmdWithTime(startDate, '09:00');
                    endInput.value = formatYmdWithTime(startDate, '10:00');
                    isAllDayInput.checked = false;
                } else {
                    // 週・日ビューなどで特定の時間枠をクリックした場合
                    startInput.value = formatDateTimeLocal(startDate);
                    const endDate = data.end ? new Date(data.end) : new Date(startDate.getTime() + 60 * 60 * 1000);
                    endInput.value = formatDateTimeLocal(endDate);
                    isAllDayInput.checked = false;
                }
            } else {
                // 右上の「＋スケジュール追加」ボタンの場合：現在の次の正時 〜 1時間後
                const nextHour = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours() + 1, 0);
                const nextTwoHours = new Date(nextHour.getTime() + 60 * 60 * 1000);
                startInput.value = formatDateTimeLocal(nextHour);
                endInput.value = formatDateTimeLocal(nextTwoHours);
                isAllDayInput.checked = false;
            }

            setSelectedColor('#4f46e5');
        } else {
            modalTitle.textContent = 'スケジュール詳細 / 編集';
            if (modalBadge) {
                modalBadge.textContent = '編集モード';
                modalBadge.className = 'text-xs px-2.5 py-0.5 rounded-full font-semibold bg-amber-50 text-amber-700';
            }
            if (taskModalCard) {
                taskModalCard.classList.remove('border-indigo-400');
                taskModalCard.classList.add('border-amber-400');
            }

            deleteBtn.classList.remove('hidden');
            completedContainer.classList.remove('hidden');

            titleInput.value = data.title || '';
            startInput.value = formatDateTimeLocal(data.start);
            endInput.value = formatDateTimeLocal(data.end || data.start);
            isAllDayInput.checked = data.allDay || false;
            descInput.value = data.extendedProps?.description || '';
            setSelectedColor(data.extendedProps?.color || '#4f46e5');
            isCompletedInput.checked = data.extendedProps?.is_completed || false;
        }

        taskModal.classList.remove('hidden');
        taskModal.classList.add('flex');
    }

    function closeModal() {
        taskModal.classList.add('hidden');
        taskModal.classList.remove('flex');
    }

    window.closeTaskModal = closeModal;
    window.openCreateTaskModal = () => openModal('create');

    // 終日チェックボックスの切り替え時の挙動
    isAllDayInput.addEventListener('change', (e) => {
        if (e.target.checked) {
            // 終日に設定された場合：開始日の 00:00 〜 23:59 に設定
            const startDateStr = startInput.value.split('T')[0] || new Date().toISOString().split('T')[0];
            startInput.value = `${startDateStr}T00:00`;
            endInput.value = `${startDateStr}T23:59`;
        }
    });

    let isFutureOnly = false;

    window.setDashboardFutureFilter = (futureOnly) => {
        isFutureOnly = futureOnly;
        const btnAll = document.getElementById('btnFilterAll');
        const btnFuture = document.getElementById('btnFilterFuture');

        if (futureOnly) {
            btnFuture?.classList.add('bg-white', 'text-indigo-700', 'shadow-sm', 'font-semibold');
            btnFuture?.classList.remove('text-gray-600');
            btnAll?.classList.remove('bg-white', 'text-indigo-700', 'shadow-sm', 'font-semibold');
            btnAll?.classList.add('text-gray-600');
        } else {
            btnAll?.classList.add('bg-white', 'text-indigo-700', 'shadow-sm', 'font-semibold');
            btnAll?.classList.remove('text-gray-600');
            btnFuture?.classList.remove('bg-white', 'text-indigo-700', 'shadow-sm', 'font-semibold');
            btnFuture?.classList.add('text-gray-600');
        }

        calendar.refetchEvents();
    };

    // FullCalendar 初期化
    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: 'ja',
        timeZone: 'local',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: '今日',
            month: '月',
            week: '週',
            day: '日'
        },
        selectable: true,
        editable: true,
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        scrollTime: '07:00:00',
        nowIndicator: true,
        allDayText: '終日',
        events: (fetchInfo, successCallback, failureCallback) => {
            const url = new URL('/tasks/events', window.location.origin);
            url.searchParams.append('start', fetchInfo.startStr);
            url.searchParams.append('end', fetchInfo.endStr);
            if (isFutureOnly) {
                url.searchParams.append('future_only', '1');
            }
            fetch(url, {
                headers: { 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => successCallback(data))
                .catch(err => failureCallback(err));
        },

        // 日付・時間枠クリック/選択時（新規追加）
        select: (info) => {
            openModal('create', {
                start: info.start,
                end: info.end,
                allDay: info.allDay
            });
        },

        // イベントクリック時（詳細・編集）
        eventClick: (info) => {
            openModal('edit', info.event);
        },

        // イベントドラッグ＆ドロップ移動時
        eventDrop: async (info) => {
            await updateTaskDates(info.event);
        },

        // イベント時間リサイズ時
        eventResize: async (info) => {
            await updateTaskDates(info.event);
        }
    });

    calendar.render();

    // ドラッグやリサイズによる日時更新API呼び出し
    async function updateTaskDates(event) {
        const payload = {
            start_at: dateToServerDateTimeString(event.start),
            end_at: dateToServerDateTimeString(event.end || event.start),
            is_all_day: event.allDay
        };

        try {
            const res = await fetch(`/tasks/${event.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            if (!res.ok) throw new Error();
        } catch (e) {
            alert('スケジュールの更新に失敗しました。');
            calendar.refetchEvents();
        }
    }

    // フォーム送信（作成・更新）
    taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = taskIdInput.value;
        const isEdit = Boolean(id);
        const url = isEdit ? `/tasks/${id}` : '/tasks';
        const method = isEdit ? 'PUT' : 'POST';

        const payload = {
            title: titleInput.value,
            start_at: toServerDateTimeString(startInput.value),
            end_at: toServerDateTimeString(endInput.value),
            is_all_day: isAllDayInput.checked,
            description: descInput.value,
            color: colorInput.value,
            is_completed: isCompletedInput.checked
        };

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!res.ok) {
                const errData = await res.json();
                alert(errData.message || '保存に失敗しました。');
                return;
            }

            closeModal();
            calendar.refetchEvents();
        } catch (error) {
            alert('通信エラーが発生しました。');
        }
    });

    // 削除ボタン
    deleteBtn.addEventListener('click', async () => {
        const id = taskIdInput.value;
        if (!id) return;

        if (!confirm('このスケジュールを削除しますか？')) return;

        try {
            const res = await fetch(`/tasks/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error();

            closeModal();
            calendar.refetchEvents();
        } catch (e) {
            alert('削除に失敗しました。');
        }
    });
});
