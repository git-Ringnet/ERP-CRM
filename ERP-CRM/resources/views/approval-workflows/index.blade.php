@extends('layouts.app')

@section('title', 'Cấu hình quy trình duyệt')
@section('page-title', 'Cấu hình quy trình duyệt')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex-1">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                <p class="text-blue-700 text-sm"><i class="fas fa-info-circle mr-2"></i>Tùy chỉnh số cấp duyệt cho từng loại chứng từ. Thêm hoặc bớt cấp duyệt theo nhu cầu.</p>
            </div>
        </div>
        <a href="{{ route('approval-workflows.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-plus mr-2"></i> Tạo quy trình mới
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded m-4">{{ session('success') }}</div>
    @endif

    <div class="p-4 space-y-4">
        @forelse($workflows as $workflow)
        <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-file-alt mr-2 text-primary"></i>{{ $workflow->name }}
                    </h3>
                    <p class="text-gray-500 text-sm">{{ $workflow->description ?? 'Quy trình phê duyệt ' . $workflow->name }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('approval-workflows.edit', $workflow) }}" class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" title="Sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('approval-workflows.toggle', $workflow) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="p-2 {{ $workflow->is_active ? 'text-gray-600 bg-gray-100 hover:bg-gray-200' : 'text-green-600 bg-green-50 hover:bg-green-100' }} rounded-lg transition-colors" title="{{ $workflow->is_active ? 'Tắt' : 'Bật' }}">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Workflow Steps -->
            <div class="flex items-center gap-2 flex-wrap my-3">
                @foreach($workflow->levels as $level)
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 text-center">
                    <div class="font-medium text-blue-800 text-sm">Cấp {{ $level->level }}</div>
                    <div class="text-xs text-blue-600">{{ $level->name }}</div>
                </div>
                @if(!$loop->last)
                <i class="fas fa-arrow-right text-blue-300"></i>
                @endif
                @endforeach
                <i class="fas fa-arrow-right text-green-300"></i>
                <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 text-center">
                    <div class="font-medium text-green-800 text-sm">Hoàn thành</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 pt-3 border-t border-gray-200">
                <span class="flex items-center gap-1">
                    <i class="fas fa-circle text-xs {{ $workflow->is_active ? 'text-green-500' : 'text-gray-400' }}"></i>
                    {{ $workflow->is_active ? 'Đang hoạt động' : 'Tạm dừng' }}
                </span>
                <span><i class="fas fa-layer-group mr-1"></i> {{ $workflow->levels->count() }} cấp</span>
                <span><i class="fas fa-clock mr-1"></i> {{ $workflow->updated_at->format('d/m/Y') }}</span>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-2"></i>
            <p>Chưa có quy trình duyệt nào.</p>
            <a href="{{ route('approval-workflows.create') }}" class="text-primary hover:underline">Tạo quy trình mới</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
