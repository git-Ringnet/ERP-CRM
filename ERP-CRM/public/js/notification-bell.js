/**
 * Notification Bell Component
 * Alpine.js data component for notification bell functionality
 */
function notificationBell() {
    return {
        isOpen: false,
        unreadCount: 0,
        notifications: [],
        pollingInterval: null,

        /**
         * Initialize component
         */
        init() {
            this.fetchNotifications();
            // Polling mỗi 30 giây
            this.pollingInterval = setInterval(() => {
                this.fetchNotifications();
            }, 30000);
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
                const response = await fetch('/notifications/recent');
                const data = await response.json();
                this.notifications = data.notifications;
                this.unreadCount = data.unreadCount;
            } catch (error) {
                console.error('Error fetching notifications:', error);
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
         * Get icon class based on notification type
         */
        getIconClass(notification) {
            const iconMap = {
                'arrow-down': 'fas fa-arrow-down text-blue-500',
                'arrow-up': 'fas fa-arrow-up text-orange-500',
                'exchange': 'fas fa-exchange-alt text-purple-500',
                'check': 'fas fa-check text-green-500',
                'times': 'fas fa-times text-red-500'
            };
            return iconMap[notification.icon] || 'fas fa-bell text-gray-500';
        },

        /**
         * Format timestamp to relative time
         */
        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds

            if (diff < 60) return 'Vừa xong';
            if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
            if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
            if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';
            
            // Format as date if older than 7 days
            return date.toLocaleDateString('vi-VN', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
        }
    }
}
