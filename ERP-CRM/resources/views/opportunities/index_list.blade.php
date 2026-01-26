@extends('layouts.app')

@section('title', 'Quản lý Cơ hội')
@section('page-title', 'Quản lý Cơ hội')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-funnel-dollar text-yellow-500 mr-2"></i>Danh sách Cơ hội
                </h2>
                <div class="flex gap-2">
                    <a href="{{ route('opportunities.index', ['view' => 'kanban']) }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-columns mr-2"></i>Dạng thẻ
                    </a>
                    <a href="{{ route('opportunities.create') }}"
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên cơ hội</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá trị</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Giai đoạn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dự kiến chốt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phụ trách</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($opportunities as $opportunity)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $opportunity->name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ $opportunity->customer->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                {{ number_format($opportunity->amount) }} {{ $opportunity->currency }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $opportunity->stage_color }}">
                                    {{ $opportunity->stage_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $opportunity->expected_close_date ? $opportunity->expected_close_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $opportunity->assignedTo->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('opportunities.edit', $opportunity) }}"
                                        class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('opportunities.destroy', $opportunity) }}" method="POST"
                                        class="inline-block" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-funnel-dollar text-4xl mb-2 text-gray-300"></i>
                                <p>Chưa có cơ hội nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">
            {{ $opportunities->links() }}
        </div>
    </div>
@endsection