/**
 * Notification Bell & Real-time Push Notification Component
 * Alpine.js data component providing:
 * - Bell dropdown with unread count
 * - Web Browser Desktop Push Notifications (window.Notification API)
 * - In-app floating animated Push Toast banners
 * - Synthesized Audio chime alerts (Web Audio API)
 * - Smart background polling & tab visibility detection
 */
function notificationBell() {
    return {
        isOpen: false,
        unreadCount: 0,
        notifications: [],
        pushToasts: [],
        pollingInterval: null,
        desktopPermission: (typeof window !== 'undefined' && 'Notification' in window) 
            ? Notification.permission 
            : 'unsupported',
        isInitialLoad: true,
        knownNotificationIds: new Set(),
        isRealtimeConnected: false,

        /**
         * Initialize component
         */
        init() {
            // Update permission status
            if ('Notification' in window) {
                this.desktopPermission = Notification.permission;
            }

            // Initial fetch
            this.fetchNotifications();

            // Connect to Real-time WebSocket (Laravel Reverb / Echo)
            this.setupWebSocket();

            // Background Polling every 25 seconds as backup safety net
            this.pollingInterval = setInterval(() => {
                this.fetchNotifications();
            }, 25000);

            // Immediately check when user switches back to this browser tab
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.fetchNotifications();
                }
            });
        },

        /**
         * Connect to Laravel Reverb / WebSocket for instantaneous push notifications (< 100ms)
         */
        setupWebSocket() {
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (!userIdMeta || !userIdMeta.content) return;
            const currentUserId = userIdMeta.content;

            const handleIncoming = (notification) => {
                if (!notification || this.knownNotificationIds.has(notification.id)) {
                    return;
                }

                // Add to known IDs
                this.knownNotificationIds.add(notification.id);

                // Increment badge counter
                this.unreadCount = (this.unreadCount || 0) + 1;

                // Prepend to bell list
                this.notifications.unshift(notification);

                // Trigger audio chime, floating in-app toast, desktop push
                this.playChime();
                this.showDesktopNotification(notification);
                this.showPushToast(notification);
            };

            // Option 1: Use window.Echo if available from Vite bundle
            const tryEcho = () => {
                if (window.Echo) {
                    try {
                        window.Echo.private(`user.${currentUserId}`)
                            .listen('.notification.created', (e) => {
                                handleIncoming(e.notification);
                            });
                        this.isRealtimeConnected = true;
                        return true;
                    } catch (e) {
                        console.warn('Echo private channel subscription error:', e);
                    }
                }
                return false;
            };

            if (!tryEcho()) {
                // If Echo wasn't ready yet, retry in 500ms
                setTimeout(() => {
                    if (!tryEcho()) {
                        this.setupDirectPusher(currentUserId, handleIncoming);
                    }
                }, 500);
            }
        },

        /**
         * Direct Pusher protocol connection fallback
         */
        setupDirectPusher(currentUserId, handleIncoming) {
            const reverbKey = document.querySelector('meta[name="reverb-key"]')?.content;
            const reverbHost = document.querySelector('meta[name="reverb-host"]')?.content;
            const reverbPort = document.querySelector('meta[name="reverb-port"]')?.content || 8080;
            const reverbScheme = document.querySelector('meta[name="reverb-scheme"]')?.content || 'http';

            if (window.Pusher && reverbKey) {
                try {
                    const pusher = new window.Pusher(reverbKey, {
                        wsHost: reverbHost,
                        wsPort: parseInt(reverbPort),
                        wssPort: parseInt(reverbPort),
                        forceTLS: reverbScheme === 'https',
                        enabledTransports: ['ws', 'wss'],
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    });

                    const channel = pusher.subscribe(`private-user.${currentUserId}`);
                    channel.bind('notification.created', (data) => {
                        handleIncoming(data.notification);
                    });
                    this.isRealtimeConnected = true;
                } catch (err) {
                    console.warn('Direct Pusher connection failed:', err);
                }
            }
        },

        /**
         * Request Browser Desktop Push Permission
         */
        async requestPushPermission() {
            if (!('Notification' in window)) {
                this.showPushToast({
                    id: 'unsupported-' + Date.now(),
                    title: 'Trình duyệt không hỗ trợ',
                    message: 'Trình duyệt hiện tại không hỗ trợ Web Notification API.',
                    icon: 'fas fa-info-circle',
                    color: 'yellow',
                    link: '#'
                });
                return;
            }

            try {
                const permission = await Notification.requestPermission();
                this.desktopPermission = permission;

                if (permission === 'granted') {
                    this.playChime();
                    this.showDesktopNotification({
                        id: 'perm-granted-' + Date.now(),
                        title: 'Thông báo đẩy đã được kích hoạt',
                        message: 'Bạn sẽ nhận được thông báo ngay khi có sự kiện mới trên hệ thống.',
                        link: '#'
                    });
                    this.showPushToast({
                        id: 'perm-granted-' + Date.now(),
                        title: 'Đã bật thông báo đẩy',
                        message: 'Hệ thống sẽ gửi thông báo đẩy trực tiếp đến màn hình của bạn.',
                        icon: 'fas fa-check-circle',
                        color: 'green',
                        link: '#'
                    });
                }
            } catch (error) {
                console.error('Error requesting notification permission:', error);
            }
        },

        /**
         * Toggle dropdown visibility
         */
        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.fetchNotifications();
            }
        },

        /**
         * Fetch notifications from API
         */
        async fetchNotifications() {
            try {
                const response = await fetch('/notifications/recent', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) return;
                const data = await response.json();
                const incomingNotifications = data.notifications || [];
                const incomingUnreadCount = data.unreadCount || 0;

                // Detect newly arrived notifications that haven't been seen yet
                if (!this.isInitialLoad) {
                    const newItems = incomingNotifications.filter(item => 
                        !this.knownNotificationIds.has(item.id) && !item.is_read
                    );

                    if (newItems.length > 0) {
                        // Play gentle audio alert once for the batch
                        this.playChime();

                        // Dispatch push alerts for newly arrived items (up to 3 newest)
                        newItems.slice(0, 3).forEach(item => {
                            this.showDesktopNotification(item);
                            this.showPushToast(item);
                        });
                    }
                }

                // Register all known IDs
                incomingNotifications.forEach(item => {
                    this.knownNotificationIds.add(item.id);
                });

                this.notifications = incomingNotifications;
                this.unreadCount = incomingUnreadCount;
                this.isInitialLoad = false;
            } catch (error) {
                // Ignore network errors or aborted fetches gracefully
            }
        },

        /**
         * Trigger Browser Desktop Push Notification
         */
        showDesktopNotification(item) {
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }

            try {
                const notification = new Notification(item.title || 'Thông báo mới', {
                    body: item.message || '',
                    icon: '/favicon.ico',
                    tag: 'erp-crm-notif-' + item.id,
                    renotify: true,
                });

                notification.onclick = (event) => {
                    event.preventDefault();
                    window.focus();
                    if (item.id && typeof item.id === 'number') {
                        this.markAsRead(item.id);
                    }
                    if (item.link && item.link !== '#') {
                        window.location.href = item.link;
                    }
                    notification.close();
                };
            } catch (e) {
                console.warn('Desktop notification dispatch error:', e);
            }
        },

        /**
         * Trigger In-App Floating Toast Push Notification
         */
        showPushToast(item) {
            const toastId = 'toast-' + (item.id || Date.now() + Math.random());
            
            // Check if already in list
            if (this.pushToasts.some(t => t.id === toastId)) {
                return;
            }

            const toast = {
                id: toastId,
                rawId: item.id,
                title: item.title || 'Thông báo mới',
                message: item.message || '',
                link: item.link || '#',
                icon: item.icon || 'fas fa-bell',
                color: item.color || 'blue',
                createdAt: new Date(),
                timeoutId: null
            };

            // Limit active toasts to max 3
            if (this.pushToasts.length >= 3) {
                const oldest = this.pushToasts.shift();
                if (oldest && oldest.timeoutId) {
                    clearTimeout(oldest.timeoutId);
                }
            }

            // Auto dismiss after 6 seconds
            toast.timeoutId = setTimeout(() => {
                this.dismissToast(toast.id);
            }, 6000);

            this.pushToasts.push(toast);
        },

        /**
         * Dismiss an in-app push toast
         */
        dismissToast(toastId) {
            const idx = this.pushToasts.findIndex(t => t.id === toastId);
            if (idx !== -1) {
                if (this.pushToasts[idx].timeoutId) {
                    clearTimeout(this.pushToasts[idx].timeoutId);
                }
                this.pushToasts.splice(idx, 1);
            }
        },

        /**
         * Open an in-app push toast
         */
        openToast(toast) {
            if (toast.rawId && typeof toast.rawId === 'number') {
                this.markAsRead(toast.rawId);
            }
            this.dismissToast(toast.id);
            if (toast.link && toast.link !== '#') {
                window.location.href = toast.link;
            }
        },

        /**
         * Play gentle notification chime using Web Audio API synthesizer
         * Works across all modern browsers without relying on external MP3 assets
         */
        playChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();

                // If audio context is suspended (due to browser autoplay policies), resume on user click
                if (ctx.state === 'suspended') {
                    ctx.resume().catch(() => {});
                }

                const now = ctx.currentTime;

                // First chime note: 587.33 Hz (D5)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.08, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.28);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.28);

                // Second chime note: 880 Hz (A5) - slightly higher & brighter
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now + 0.12);
                gain2.gain.setValueAtTime(0.12, now + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.50);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.12);
                osc2.stop(now + 0.50);
            } catch (e) {
                // Browsers may block audio before first user gesture; catch gracefully
            }
        },

        /**
         * Mark a notification as read
         */
        async markAsRead(notificationId) {
            try {
                const response = await fetch(`/notifications/${notificationId}/mark-as-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    }
                });

                if (response.ok) {
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    const notification = this.notifications.find(n => n.id === notificationId);
                    if (notification) {
                        notification.is_read = true;
                    }
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        /**
         * Mark all notifications as read
         */
        async markAllAsRead() {
            if (this.unreadCount === 0) return;

            try {
                const response = await fetch('/notifications/mark-all-as-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    }
                });

                if (response.ok) {
                    this.unreadCount = 0;
                    this.notifications.forEach(n => {
                        n.is_read = true;
                    });
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },

        /**
         * Get icon class based on notification icon/type
         */
        getIconClass(notification) {
            const icon = notification.icon || '';
            if (icon.startsWith('fa-') || icon.startsWith('fas ') || icon.startsWith('far ')) {
                return icon;
            }

            const iconMap = {
                'arrow-down': 'fas fa-arrow-down text-blue-500',
                'arrow-up': 'fas fa-arrow-up text-orange-500',
                'exchange': 'fas fa-exchange-alt text-purple-500',
                'check': 'fas fa-check text-green-500',
                'times': 'fas fa-times text-red-500',
                'exclamation-triangle': 'fas fa-exclamation-triangle text-amber-500',
                'calendar': 'fas fa-calendar text-blue-500',
                'clock': 'fas fa-clock text-amber-500',
                'exclamation-circle': 'fas fa-exclamation-circle text-red-500',
                'folder-plus': 'fas fa-folder-plus text-purple-500',
                'clipboard-check': 'fas fa-clipboard-check text-emerald-500',
                'file-text': 'fas fa-file-alt text-emerald-500',
                'refresh-cw': 'fas fa-sync-alt text-amber-500'
            };
            return iconMap[icon] || 'fas fa-bell text-blue-500';
        },

        /**
         * Get icon background pill color for toasts
         */
        getToastIconBg(color) {
            const map = {
                'blue': 'bg-blue-100 text-blue-600',
                'green': 'bg-emerald-100 text-emerald-600',
                'emerald': 'bg-emerald-100 text-emerald-600',
                'orange': 'bg-orange-100 text-orange-600',
                'amber': 'bg-amber-100 text-amber-600',
                'yellow': 'bg-yellow-100 text-yellow-600',
                'red': 'bg-red-100 text-red-600',
                'purple': 'bg-purple-100 text-purple-600'
            };
            return map[color] || 'bg-blue-100 text-blue-600';
        },

        /**
         * Format timestamp to relative time
         */
        formatTime(timestamp) {
            if (!timestamp) return 'Vừa xong';
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds

            if (diff < 60) return 'Vừa xong';
            if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
            if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
            if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';

            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    };
}
