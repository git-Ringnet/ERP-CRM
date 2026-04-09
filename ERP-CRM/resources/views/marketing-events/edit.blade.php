@extends('layouts.app')
@section('title', 'Chỉnh sửa: ' . $marketingEvent->title)

@section('content')
<div class="">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-edit text-purple-500"></i>Chỉnh sửa sự kiện
        </h2>
        <form action="{{ route('marketing-events.update', $marketingEvent) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @include('marketing-events._form')
            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors">
                    <i class="fas fa-save mr-2"></i> Lưu thay đổi
                </button>
                <a href="{{ route('marketing-events.show', $marketingEvent) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection
