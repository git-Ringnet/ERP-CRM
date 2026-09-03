@extends('layouts.app')

@section('title', 'Không Tìm Thấy Trang (404)')
@section('page-title', 'Không Tìm Thấy Trang')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center space-y-6">
        <!-- Error Graphic / Icon -->
        <div class="relative mx-auto w-24 h-24 flex items-center justify-center">
            <div class="absolute inset-0 bg-blue-100 rounded-full animate-ping opacity-25"></div>
            <div class="relative w-20 h-20 bg-gradient-to-tr from-blue-500 to-indigo-400 text-white rounded-full flex items-center justify-center shadow-lg shadow-blue-200">
                <i class="fas fa-magnifying-glass text-3xl"></i>
            </div>
        </div>

        <!-- Text content -->
        <div class="space-y-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-widest">
                Lỗi 404 • Not Found
            </span>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Không tìm thấy trang</h2>
            <p class="text-sm text-gray-600 leading-relaxed max-w-md mx-auto">
                {{ $exception->getMessage() ?: 'Đường dẫn bạn yêu cầu không tồn tại hoặc đã bị xóa khỏi hệ thống.' }}
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <button onclick="window.history.back()"
                class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-xl text-sm font-semibold transition-all shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </button>
            <a href="{{ route('dashboard') }}"
                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary/20">
                <i class="fas fa-house mr-2"></i> Về Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
