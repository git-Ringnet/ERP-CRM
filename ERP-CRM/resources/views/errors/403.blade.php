@extends('layouts.app')

@section('title', 'Truy Cập Bị Từ Chối (403)')
@section('page-title', 'Truy Cập Bị Từ Chối')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center space-y-6">
        <!-- Error Graphic / Icon -->
        <div class="relative mx-auto w-24 h-24 flex items-center justify-center">
            <div class="absolute inset-0 bg-red-100 rounded-full animate-ping opacity-25"></div>
            <div class="relative w-20 h-20 bg-gradient-to-tr from-red-500 to-rose-400 text-white rounded-full flex items-center justify-center shadow-lg shadow-red-200">
                <i class="fas fa-shield-halved text-3xl"></i>
            </div>
        </div>

        <!-- Text content -->
        <div class="space-y-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-600 border border-red-100 uppercase tracking-widest">
                Lỗi 403 • Forbidden
            </span>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Không có quyền truy cập</h2>
            <p class="text-sm text-gray-600 leading-relaxed max-w-md mx-auto">
                {{ $exception->getMessage() ?: 'Bạn không có quyền xem hoặc thao tác trên tài nguyên này. Để tránh lộ thông tin dự án, chỉ những tài khoản liên quan mới được phép truy cập.' }}
            </p>
        </div>

        <!-- Information Box -->
        <div class="bg-gray-50 border border-gray-200/80 rounded-xl p-4 text-left flex items-start space-x-3">
            <i class="fas fa-circle-info text-blue-500 mt-0.5 text-base shrink-0"></i>
            <div class="text-xs text-gray-600 space-y-1">
                <p class="font-semibold text-gray-800">Cần quyền truy cập?</p>
                <p>Nếu bạn là người phụ trách hoặc cần tiếp nhận công việc này, vui lòng liên hệ <strong>Quản lý trực tiếp</strong> hoặc <strong>Technical Lead / Quản trị viên</strong> để được phân công.</p>
            </div>
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
