import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', () => {
    const sharedCalendarEl = document.getElementById('sharedCalendar');
    if (!sharedCalendarEl) return;

    const token = sharedCalendarEl.getAttribute('data-token');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const modal = document.getElementById('guestBookingModal');
    const form = document.getElementById('guestBookingForm');
    const guestNameInput = document.getElementById('guestName');
    const guestEmailInput = document.getElementById('guestEmail');
    const startInput = document.getElementById('bookingStart');
    const endInput = document.getElementById('bookingEnd');
    const notesInput = document.getElementById('bookingNotes');

    function openModal(data = {}) {
        form.reset();

        const now = new Date();
        if (data.start) {
            const startDate = new Date(data.start);
            if (data.allDay) {
                // 終日枠選択時
                startInput.value = formatYmdWithTime(startDate, '10:00');
                endInput.value = formatYmdWithTime(startDate, '11:00');
            } else {
                startInput.value = formatDateTimeLocal(startDate);
                const endDate = data.end ? new Date(data.end) : new Date(startDate.getTime() + 60 * 60 * 1000);
                endInput.value = formatDateTimeLocal(endDate);
            }
        } else {
            const nextHour = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours() + 1, 0);
            const nextTwoHours = new Date(nextHour.getTime() + 60 * 60 * 1000);
            startInput.value = formatDateTimeLocal(nextHour);
            endInput.value = formatDateTimeLocal(nextTwoHours);
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.closeGuestBookingModal = closeModal;

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

    function formatYmdWithTime(date, timeStr = '10:00') {
        const d = (date instanceof Date) ? date : new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        const year = d.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());
        return `${year}-${month}-${day}T${timeStr}`;
    }

    function toServerDateTimeString(inputVal) {
        if (!inputVal) return '';
        const clean = inputVal.replace('T', ' ');
        return clean.length === 16 ? `${clean}:00` : clean;
    }

    const calendar = new Calendar(sharedCalendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'timeGridWeek',
        locale: 'ja',
        timeZone: 'local',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: '今日',
            week: '週',
            day: '日'
        },
        selectable: true,
        editable: false, // ゲストは既存イベントのドラッグ不可
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        scrollTime: '07:00:00',
        nowIndicator: true,
        validRange: () => {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            return {
                start: `${yyyy}-${mm}-${dd}`
            };
        },
        events: `/share/${token}/events`,

        // 過去日時や終日予定・既存予定と重複する選択を禁止
        selectAllow: (selectInfo) => {
            const start = selectInfo.start.getTime();
            const end = selectInfo.end.getTime();
            const now = Date.now();

            if (end <= now) {
                return false;
            }

            const events = calendar.getEvents();
            for (const event of events) {
                if (event.allDay || event.extendedProps?.is_all_day) {
                    const eventStart = new Date(event.start).setHours(0, 0, 0, 0);
                    const eventEnd = new Date(event.end || event.start).setHours(23, 59, 59, 999);
                    if (start <= eventEnd && end >= eventStart) {
                        return false;
                    }
                } else if (event.start && event.end) {
                    const eventStart = event.start.getTime();
                    const eventEnd = event.end.getTime();
                    if (start < eventEnd && end > eventStart) {
                        return false;
                    }
                }
            }
            return true;
        },

        // 空き枠クリックで予約モーダルオープン
        select: (info) => {
            const start = info.start.getTime();
            const end = info.end.getTime();

            // 終日予定の日のチェック
            const events = calendar.getEvents();
            for (const event of events) {
                if (event.allDay || event.extendedProps?.is_all_day) {
                    const eventStart = new Date(event.start).setHours(0, 0, 0, 0);
                    const eventEnd = new Date(event.end || event.start).setHours(23, 59, 59, 999);
                    if (start <= eventEnd && end >= eventStart) {
                        alert('この日は終日の予定が入っているため予約できません。空いている別の日をお選びください。');
                        calendar.unselect();
                        return;
                    }
                }
            }

            openModal({
                start: info.start,
                end: info.end,
                allDay: info.allDay
            });
        },

        // 既存の「予定あり」イベントクリック時
        eventClick: (info) => {
            if (info.event.allDay || info.event.extendedProps?.is_all_day) {
                alert('この日は終日の予定が入っているため予約できません。空いている別の日をお選びください。');
            } else {
                alert('この時間帯は既に予定が入っているため予約できません。空いている別の時間枠をお選びください。');
            }
        }
    });

    calendar.render();

    // 予約フォーム送信
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            guest_name: guestNameInput.value,
            guest_email: guestEmailInput.email || guestEmailInput.value,
            start_at: toServerDateTimeString(startInput.value),
            end_at: toServerDateTimeString(endInput.value),
            notes: notesInput.value
        };

        try {
            const res = await fetch(`/share/${token}/schedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!res.ok) {
                alert(data.message || '予約の登録に失敗しました。');
                return;
            }

            alert('【予約完了】\nスケジュール予約が完了しました！');
            closeModal();
            calendar.refetchEvents();
        } catch (error) {
            alert('通信エラーが発生しました。');
        }
    });
});
