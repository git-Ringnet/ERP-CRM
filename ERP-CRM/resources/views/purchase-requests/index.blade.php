@extends('layouts.app')

@section('title', 'Yêu cầu báo giá NCC')
@section('page-title', 'Yêu cầu báo giá từ nhà cung cấp')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-gray-600">Quản lý yêu cầu báo giá gửi đến nhà cung cấp</p>
        </div>
        <a href="{{ route('purchase-requests.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <i class="fas fa-plus mr-2"></i> Tạo yêu cầu báo giá
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 border-b border-gray-200 bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..." 
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Đã gửi NCC</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Đã nhận báo giá</option>
                <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Đã chuyển PO</option>
            </select>
            <select name="priority" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả ưu tiên --</option>
                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Bình thường</option>
                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Cao</option>
                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-search mr-2"></i> Lọc
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiêu đề</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NCC</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hạn báo giá</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ưu tiên</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Báo giá nhận</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requests as $request)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-primary">{{ $request->code }}</td>
                        <td class="px-4 py-3">{{ $request->title }}</td>
                        <td class="px-4 py-3">
                            @foreach($request->suppliers as $supplier)
                                <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded mr-1 mb-1">{{ $supplier->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">{{ $request->deadline->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($request->priority == 'urgent')
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Khẩn cấp</span>
                            @elseif($request->priority == 'high')
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Cao</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Bình thường</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $request->quotations->count() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($request->status == 'draft')
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nháp</span>
                            @elseif($request->status == 'sent')
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Đã gửi NCC</span>
                            @elseif($request->status == 'received')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đã nhận báo giá</span>
                            @elseif($request->status == 'converted')
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Đã chuyển PO</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Đã hủy</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <a href="{{ route('purchase-requests.show', $request) }}" class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($request->status == 'draft')
                                    <a href="{{ route('purchase-requests.edit', $request) }}" class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('purchase-requests.send', $request) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Gửi NCC" onclick="return confirm('Gửi yêu cầu báo giá cho các NCC?')">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($request->quotations->count() > 0)
                                    <a href="{{ route('supplier-quotations.index', ['purchase_request_id' => $request->id]) }}" class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200" title="Xem báo giá">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có yêu cầu báo giá nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
