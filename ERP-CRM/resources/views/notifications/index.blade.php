@extends('layouts.app')

@section('title', 'Tất cả thông báo')
@section('page-title', 'Tất cả thông báo')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Filter Tabs -->
    <div class="mb-6 flex gap-2">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
           class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            Tất cả
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
           class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filter === 'unread' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            Chưa đọc
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
           class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filter === 'read' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            Đã đọc
        </a>
    </div>

    <!-- Notification List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @forelse($notifications as $notification)
        <a href="{{ $notification->link }}" 
           onclick="markAsRead({{ $notification->id }})"
           class="block p-4 border-b hover:bg-gray-50 transition-colors {{ !$notification->is_read ? 'bg-blue-50' : '' }}">
            <div class="flex items-start">
                <i class="{{ getIconClass($notification) }} mt-1 mr-4 text-xl"></i>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <p class="font-semibold text-gray-800">{{ $notification->title }}</p>
                        @if(!$notification->is_read)
                        <span class="ml-2 flex-shrink-0 inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="far fa-clock mr-1"></i>
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </a>
        @empty
        <div class="p-12 text-center text-gray-500">
            <i class="fas fa-bell-slash text-5xl mb-4 text-gray-300"></i>
            <p class="text-lg font-medium">Không có thông báo</p>
            <p class="text-sm mt-2">
                @if($filter === 'unread')
                    Bạn không có thông báo chưa đọc nào
                @elseif($filter === 'read')
                    Bạn không có thông báo đã đọc nào
                @else
                    Bạn chưa có thông báo nào
                @endif
            </p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
    @endif
</div>

@php
function getIconClass($notification) {
    $iconMap = [
        'arrow-down' => 'fas fa-arrow-down text-blue-500',
        'arrow-up' => 'fas fa-arrow-up text-orange-500',
        'exchange' => 'fas fa-exchange-alt text-purple-500',
        'check' => 'fas fa-check text-green-500',
        'times' => 'fas fa-times text-red-500'
    ];
    return $iconMap[$notification->icon] ?? 'fas fa-bell text-gray-500';
}
@endphp

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/mark-as-read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    }).catch(error => {
        console.error('Error marking notification as read:', error);
    });
}
</script>
@endpush
@endsection
