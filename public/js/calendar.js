/**
 * Calendrier des locations (FullCalendar) – URL des événements via data-events-url sur #calendar-reservations
 */
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar-reservations');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;
    var eventsUrl = calendarEl.getAttribute('data-events-url') || '';
    if (!eventsUrl) return;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
        buttonText: { today: "Aujourd'hui", month: 'Mois', list: 'Liste' },
        height: 'auto',
        contentHeight: 500,
        events: function(info, successCallback, failureCallback) {
            fetch(eventsUrl + '?start=' + encodeURIComponent(info.startStr) + '&end=' + encodeURIComponent(info.endStr))
                .then(function(r) { return r.json(); })
                .then(function(events) { successCallback(events); })
                .catch(function() { failureCallback(); });
        },
        eventClick: function(arg) {
            if (arg.event.url) {
                arg.jsEvent.preventDefault();
                window.location.href = arg.event.url;
            }
        }
    });
    calendar.render();
});
