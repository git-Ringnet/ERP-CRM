@extends('layouts.app')

@section('title', 'Phân nhóm người dùng')
@section('page-title', 'Quản lý Phân nhóm')

@section('content')
<div class="space-y-4">
    <!-- Header Card -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-users-cog text-primary"></i> Danh sách Nhóm người dùng / Team
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Quản lý các nhóm chuyên môn, trưởng nhóm (Lead) và thành viên để phục vụ phân quyền và điều phối ticket kỹ thuật.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('user-groups.create') }}" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-semibold shadow-xs transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm Nhóm Mới
                </a>
            </div>
        </div>

        <!-- Search & Filter -->
        <form method="GET" action="{{ route('user-groups.index') }}" class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Tìm kiếm theo tên nhóm, mã nhóm, tên Lead..."
                       class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">
            </div>
            @if(isset($departments) && $departments->isNotEmpty())
            <div class="w-48">
                <select name="department" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">
                    <option value="">-- Tất cả bộ phận --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="w-36">
                <select name="status" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">
                    <option value="">-- Trạng thái --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tạm dừng</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-xs font-semibold hover:bg-gray-700 transition-colors">
                <i class="fas fa-filter mr-1.5"></i> Lọc
            </button>
            @if(request()->anyFilled(['search', 'department', 'status']))
                <a href="{{ route('user-groups.index') }}" class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs hover:bg-gray-200">
                    <i class="fas fa-redo mr-1"></i> Bỏ lọc
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Tên nhóm / Mã</th>
                        <th class="px-4 py-3.5">Bộ phận</th>
                        <th class="px-4 py-3.5">Trưởng nhóm (Lead)</th>
                        <th class="px-4 py-3.5">Thành viên</th>
                        <th class="px-4 py-3.5 text-center">Trạng thái</th>
                        <th class="px-4 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse($groups as $group)
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-gray-900">{{ $group->name }}</div>
                                @if($group->code)
                                    <span class="inline-block font-mono text-[11px] text-primary bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 mt-0.5">{{ $group->code }}</span>
                                @endif
                                @if($group->description)
                                    <div class="text-xs text-gray-500 mt-1 max-w-sm truncate" title="{{ $group->description }}">{{ $group->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-700">
                                {{ $group->department ?: 'Chưa phân bộ phận' }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($group->leader)
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                            {{ substr($group->leader->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 text-xs">{{ $group->leader->name }}</div>
                                            <div class="text-[10px] text-gray-400">{{ $group->leader->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Chưa chỉ định Lead</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5 flex-wrap max-w-md">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                        {{ $group->members->count() }} thành viên
                                    </span>
                                    @foreach($group->members->take(4) as $m)
                                        <span class="inline-flex items-center text-[11px] bg-gray-100 text-gray-700 px-2 py-0.5 rounded-md border border-gray-200">
                                            {{ $m->name }}
                                        </span>
                                    @endforeach
                                    @if($group->members->count() > 4)
                                        <span class="text-[11px] text-gray-400 font-semibold">+{{ $group->members->count() - 4 }} khác</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($group->status === 'active')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Tạm dừng</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('user-groups.edit', $group->id) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('user-groups.destroy', $group->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhóm này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                                <p class="text-sm font-medium">Chưa có nhóm người dùng nào được tạo.</p>
                                <a href="{{ route('user-groups.create') }}" class="inline-flex items-center mt-3 text-xs text-primary font-semibold hover:underline">
                                    <i class="fas fa-plus mr-1"></i> Tạo nhóm ngay
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($groups->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $groups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
