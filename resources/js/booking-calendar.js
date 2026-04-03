import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import ruLocale from '@fullcalendar/core/locales/ru.js';

let calendarInstance = null;

function destroyCalendar() {
    window.__bookingCalRefetch = null;
    window.__bookingCalGoto = null;
    if (calendarInstance) {
        calendarInstance.destroy();
        calendarInstance = null;
    }
}

function ymdFromDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
}

function mountCalendar() {
    const root = document.getElementById('booking-calendar-host');
    if (!root) {
        destroyCalendar();

        return;
    }

    destroyCalendar();

    const lwId = root.getAttribute('data-livewire-id');
    const comp = window.Livewire?.find(lwId);
    if (! comp) {
        return;
    }

    const tz = root.dataset.timezone || 'UTC';
    const initialView = root.dataset.initialView || 'dayGridMonth';
    const initialDate = root.dataset.initialDate || undefined;
    const dayMax = parseInt(root.dataset.dayMaxEvents || '3', 10);

    window.__bookingCalRefetch = () => {
        calendarInstance?.refetchEvents();
    };

    window.__bookingCalGoto = (dateStr, viewSlug) => {
        if (! calendarInstance || ! dateStr) {
            return;
        }
        const fcView = viewSlug === 'week' ? 'timeGridWeek' : 'dayGridMonth';
        const cur = calendarInstance.getDate();
        const curYmd = ymdFromDate(cur);
        if (calendarInstance.view.type === fcView && curYmd === dateStr) {
            return;
        }
        if (calendarInstance.view.type !== fcView) {
            calendarInstance.changeView(fcView);
        }
        calendarInstance.gotoDate(dateStr);
    };

    const calendar = new Calendar(root, {
        plugins: [dayGridPlugin, timeGridPlugin],
        locale: ruLocale,
        timeZone: tz,
        initialView,
        initialDate,
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek',
        },
        buttonText: {
            today: 'Сегодня',
            month: 'Месяц',
            week: 'Неделя',
        },
        dayMaxEvents: Number.isFinite(dayMax) ? dayMax : 3,
        nowIndicator: true,
        slotMinTime: '06:00:00',
        slotMaxTime: '23:00:00',
        /* Шаг сетки 1 ч вместо 30 мин — вдвое меньше горизонтальных линий («решето»). */
        slotDuration: '01:00:00',
        snapDuration: '01:00:00',
        slotLabelInterval: '01:00:00',
        height: 'auto',
        datesSet(info) {
            const view = info.view.type === 'timeGridWeek' ? 'week' : 'month';
            const anchor = info.view.calendar.getDate();
            const ymd = ymdFromDate(anchor);
            comp.call('syncCalendarNav', view, ymd);
        },
        events(info, success, failure) {
            comp.call('fetchEvents', info.startStr, info.endStr)
                .then((events) => success(Array.isArray(events) ? events : []))
                .catch(() => success([]));
        },
        eventClick(info) {
            const ev = info.event;
            comp.call('openEventDetail', {
                title: ev.title,
                extendedProps: { ...ev.extendedProps },
            });
        },
    });

    calendar.render();
    calendarInstance = calendar;
}

document.addEventListener('DOMContentLoaded', mountCalendar);
document.addEventListener('livewire:navigated', mountCalendar);
document.addEventListener('livewire:navigating', destroyCalendar);
