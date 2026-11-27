@php
    $logs = \App\Models\ActivityLog::with('user')
        ->where('subject_type', get_class($subject))
        ->where('subject_id', $subject->id)
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->get();
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-history text-indigo-600"></i>
            <span>{{ $title ?? 'Lịch sử thao tác & phê duyệt' }}</span>
        </h3>
        <span class="text-xs px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 font-semibold">
            {{ $logs->count() }} hoạt động
        </span>
    </div>

    @if($logs->isNotEmpty())
        <div class="space-y-3">
            @foreach($logs as $log)
                @php
                    $isApprove = str_contains($log->action, 'approv');
                    $isReject = str_contains($log->action, 'reject');
                    $badgeClass = $isApprove
                        ? 'bg-green-100 text-green-700'
                        : ($isReject ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700');
                    $icon = $isApprove ? 'fa-check' : ($isReject ? 'fa-times' : 'fa-pen');
                @endphp
                <div class="flex items-start gap-3 text-sm border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 {{ $badgeClass }}">
                        <i class="fas {{ $icon }} text-xs"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-800">
                            <span class="font-semibold text-gray-900">{{ $log->user?->name ?? $log->user_name ?? 'Hệ thống' }}</span>
                            <span>{{ $log->description }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <i class="far fa-clock mr-1"></i>{{ $log->created_at?->format('d/m/Y H:i:s') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-400 italic text-center py-4">Chưa có lịch sử thao tác nào được ghi nhận.</p>
    @endif
</div>
