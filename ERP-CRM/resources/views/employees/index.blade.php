@extends('layouts.app')

@section('title', 'Nhân viên')
@section('page-title', 'Quản lý Nhân viên')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <form action="{{ route('employees.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm theo mã, tên, email, SĐT..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Filter by Department -->
            <div class="flex items-center gap-2">
                <select name="department" onchange="window.location.href='{{ route('employees.index') }}?department='+this.value+'&status={{ request('status') }}&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả phòng ban</option>
                    <option value="Kinh doanh" {{ request('department') == 'Kinh doanh' ? 'selected' : '' }}>Kinh doanh</option>
                    <option value="Kỹ thuật" {{ request('department') == 'Kỹ thuật' ? 'selected' : '' }}>Kỹ thuật</option>
                    <option value="Kế toán" {{ request('department') == 'Kế toán' ? 'selected' : '' }}>Kế toán</option>
                    <option value="Nhân sự" {{ request('department') == 'Nhân sự' ? 'selected' : '' }}>Nhân sự</option>
                    <option value="Marketing" {{ request('department') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                    <option value="IT" {{ request('department') == 'IT' ? 'selected' : '' }}>IT</option>
                </select>
            </div>

            <!-- Filter by Status -->
            <div class="flex items-center gap-2">
                <select name="status" onchange="window.location.href='{{ route('employees.index') }}?status='+this.value+'&department={{ request('department') }}&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang làm việc</option>
                    <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>Nghỉ phép</option>
                    <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Đã nghỉ việc</option>
                </select>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('employees.export') }}?{{ http_build_query(request()->query()) }}" 
               class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </a>
            <a href="{{ route('employees.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Thêm nhân viên
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã NV</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên nhân viên</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chức vụ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phòng ban</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điện thoại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $employee->employee_code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $employee->name }}</div>
                        @if($employee->join_date)
                            <div class="text-sm text-gray-500">Vào: {{ \Carbon\Carbon::parse($employee->join_date)->format('d/m/Y') }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $employee->position }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $employee->department }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $employee->email }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $employee->phone }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($employee->status == 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Đang làm việc
                            </span>
                        @elseif($employee->status == 'leave')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Nghỉ phép
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Đã nghỉ việc
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('employees.show', $employee->id) }}" 
                               class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('employees.edit', $employee->id) }}" 
                               class="text-yellow-600 hover:text-yellow-900" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="inline"
                                  onsubmit="return confirmDelete(this, 'Bạn có chắc chắn muốn xóa nhân viên {{ $employee->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu nhân viên</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($employees->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $employees->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
