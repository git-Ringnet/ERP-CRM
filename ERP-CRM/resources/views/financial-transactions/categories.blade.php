@extends('layouts.app')

@section('title', 'Quản lý danh mục Thu Chi')
@section('page-title', 'Quản lý danh mục Thu Chi')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add Category Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-plus-circle text-primary mr-2"></i> Thêm danh mục mới
            </h3>
            <form action="{{ route('financial-transactions.categories.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tên danh mục <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Lương nhân viên, Tiền điện..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại <span class="text-red-500">*</span></label>
                        <select name="type" id="category_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                            <option value="expense">Chi (Expense)</option>
                            <option value="income">Thu (Income)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ánh xạ Báo cáo Dòng tiền</label>
                        <select name="cash_flow_code" id="cash_flow_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                            <option value="">-- Không ánh xạ --</option>
                            @foreach($standardItems['expense'] as $name => $code)
                                <option value="{{ $code }}" data-type="expense">{{ $name }}</option>
                            @endforeach
                            @foreach($standardItems['income'] as $name => $code)
                                <option value="{{ $code }}" data-type="income" style="display:none;">{{ $name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1">Chọn dòng tương ứng trên mẫu báo cáo Dòng tiền 12 tháng.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                        <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 font-bold transition-colors">
                        Lưu danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('category_type').addEventListener('change', function() {
            const type = this.value;
            const select = document.getElementById('cash_flow_code');
            const options = select.querySelectorAll('option');
            
            select.value = ""; // Reset value
            
            options.forEach(option => {
                if (!option.dataset.type) return; // Skip empty option
                
                if (option.dataset.type === type) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    </script>

    <!-- Category List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Danh sách danh mục</h3>
                <a href="{{ route('financial-transactions.index') }}" class="text-sm text-primary hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại giao dịch
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên danh mục</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã Lưu chuyển</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô tả</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $category->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($category->cash_flow_code)
                                    <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">{{ $category->cash_flow_code }}</span>
                                @else
                                    <span class="text-gray-300 italic">Chưa gán</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $category->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $category->type === 'income' ? 'Thu' : 'Chi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $category->description ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <form action="{{ route('financial-transactions.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Xóa danh mục này có thể ảnh hưởng đến lịch sử? Bạn chắc chắn chứ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-700">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">
                                Chưa có danh mục nào. Vui lòng thêm danh mục để bắt đầu ghi nhận Thu Chi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
