@extends('layouts.app')
@section('title', 'Tạo sự kiện Marketing')
@section('page-title', 'Tạo sự kiện Marketing mới')

@section('content')
<div class="">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-calendar-plus text-purple-500"></i>Thông tin sự kiện
        </h2>

        <form action="{{ route('marketing-events.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @include('marketing-events._form')

            <div class="rounded-xl border border-purple-100 bg-purple-50 p-4">
                <h3 class="text-sm font-semibold text-purple-900">Yêu cầu phối hợp khi sự kiện được duyệt</h3>
                <p class="mt-1 text-xs text-purple-700">Các yêu cầu được tạo sẵn nhưng chỉ gửi cho đội phụ trách sau khi BOD duyệt ngân sách sự kiện.</p>
                <div class="mt-3 flex flex-wrap gap-5 text-sm text-gray-700">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="support_marketing" value="1" {{ old('support_marketing') ? 'checked' : '' }}>
                        Marketing chuẩn bị/điều phối
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="support_technical" value="1" {{ old('support_technical') ? 'checked' : '' }}>
                        Kỹ thuật trình bày/hỗ trợ
                    </label>
                </div>
                <textarea name="support_request_note" rows="2" maxlength="2000" class="mt-3 w-full rounded-lg border border-purple-200 px-3 py-2 text-sm" placeholder="Nội dung cần phối hợp, yêu cầu chuẩn bị hoặc thời hạn...">{{ old('support_request_note') }}</textarea>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors">
                    <i class="fas fa-save mr-2"></i> Tạo sự kiện
                </button>
                <a href="{{ route('marketing-events.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
