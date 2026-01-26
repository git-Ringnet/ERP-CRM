@extends('layouts.app')

@section('title', 'Quản lý Đấu mối')
@section('page-title', 'Quản lý Đấu mối')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-bullseye text-cyan-500 mr-2"></i>Danh sách Đấu mối
                </h2>
                <div class="flex gap-2">
                    <a href="{{ route('leads.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Thêm mới
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên / Công ty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liên hệ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nguồn</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phụ trách</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $lead->name }}</div>
                                <div class="text-sm text-gray-500">{{ $lead->company_name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ $lead->email }}</div>
                                <div class="text-sm text-gray-500">{{ $lead->phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $lead->source }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $lead->status_color }}">
                                    {{ $lead->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $lead->assignedTo->name ?? 'Chưa giao' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if($lead->status !== 'converted')
                                        <form action="{{ route('leads.convert', $lead) }}" method="POST" class="inline-block"
                                            onsubmit="return confirm('Bạn có chắc muốn chuyển đổi đấu mối này thành Khách hàng và Cơ hội không?')">
                                            @csrf
                                            <button type="submit"
                                                class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200"
                                                title="Chuyển đổi sang Khách hàng & Cơ hội">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('leads.edit', $lead) }}"
                                        class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('leads.destroy', $lead) }}" method="POST" class="inline-block"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200"
                                            title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-bullseye text-4xl mb-2 text-gray-300"></i>
                                <p>Chưa có đấu mối nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">
            {{ $leads->links() }}
        </div>
    </div>
@endsection