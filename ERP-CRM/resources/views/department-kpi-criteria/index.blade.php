@extends('layouts.app')

@section('title', 'Quản lý Tiêu chí KPI')
@section('page-title', 'Quản lý Tiêu chí KPI')

@section('content')
<div class="w-full">
    

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Danh sách Tiêu chí Đánh giá</h2>
            <a href="{{ route('department-kpi-criteria.create') }}" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>Thêm tiêu chí mới
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Tên tiêu chí</th>
                        <th class="px-4 py-3">Bộ phận áp dụng</th>
                        <th class="px-4 py-3 text-center">Trọng số (%)</th>
                        <th class="px-4 py-3">Mục tiêu</th>
                        <th class="px-4 py-3">Người tạo</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($criteria as $criterion)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $criterion->name }}</td>
                            <td class="px-4 py-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded border border-blue-200">
                                    {{ $criterion->department }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ number_format($criterion->weight, 0) }}%</td>
                            <td class="px-4 py-3 text-gray-500">{{ $criterion->target ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $criterion->creator->name ?? 'Hệ thống' }}</td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('department-kpi-criteria.edit', $criterion) }}" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-1.5 rounded inline-flex items-center justify-center transition-colors" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('department-kpi-criteria.destroy', $criterion) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tiêu chí này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded inline-flex items-center justify-center transition-colors" title="Xóa">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                                    <p>Chưa có dữ liệu tiêu chí KPI.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $criteria->links() }}
        </div>
    </div>
</div>
@endsection
