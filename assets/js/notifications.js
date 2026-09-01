(function () {
    'use strict';
    var menu = document.getElementById('notificationMenu');
    var count = document.getElementById('notificationCount');
    var list = document.getElementById('notificationList');
    if (!menu || !count || !list || !menu.dataset.feedUrl) return;

    function updateCount(unread) {
        unread = Math.max(0, Number(unread) || 0);
        count.textContent = unread > 9 ? '9+' : String(unread);
        count.hidden = unread === 0;
    }

    function renderNotifications(notifications) {
        list.replaceChildren();
        if (!notifications.length) {
            var empty = document.createElement('p');
            empty.className = 'notification-empty';
            empty.textContent = 'ยังไม่มีการแจ้งเตือน';
            list.appendChild(empty);
            return;
        }
        notifications.forEach(function (notification) {
            var link = document.createElement('a');
            link.className = 'notification-item' + (notification.is_read ? ' is-read' : '');
            link.href = notification.url;
            var title = document.createElement('b');
            title.textContent = notification.title;
            var message = document.createElement('span');
            message.textContent = notification.message;
            var time = document.createElement('small');
            time.textContent = notification.created_at;
            link.append(title, message, time);
            list.appendChild(link);
        });
    }

    async function refresh(details) {
        if (document.hidden && !details) return;
        try {
            var response = await fetch(menu.dataset.feedUrl + (details ? '?details=1' : ''), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) return;
            var data = await response.json();
            updateCount(data.unread);
            if (details && Array.isArray(data.notifications)) renderNotifications(data.notifications);
        } catch (error) {
            // การแจ้งเตือนเป็นส่วนเสริม จึงไม่รบกวนการใช้งานหลักเมื่อเครือข่ายมีปัญหา
        }
    }

    menu.addEventListener('toggle', function () {
        if (menu.open) refresh(true);
    });
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refresh(menu.open);
    });
    window.setInterval(function () { refresh(menu.open); }, 30000);
})();
