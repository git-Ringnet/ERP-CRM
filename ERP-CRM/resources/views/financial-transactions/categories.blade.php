@extends('layouts.app')

@section('title', 'Quản lý danh mục Thu Chi')
@section('page-title', 'Quản lý danh mục Thu Chi')

@section('content')
<div x-data="{ 
    showEditModal: false, 
    editingCategory: {},
    editCategory(category) {
        this.editingCategory = { ...category };
        this.showEditModal = true;
        // Trigger select change logic if needed, but for edit we just set values
    }
}">
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
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại <span class="text-red-500">*</span></label>
                            <select name="type" id="category_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white">
                                <option value="expense">Chi (Expense)</option>
                                <option value="income">Thu (Income)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ánh xạ Báo cáo Dòng tiền</label>
                            <select name="cash_flow_code" id="cash_flow_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white">
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
                            <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white"></textarea>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center flex items-center justify-center gap-3">
                                    <button @click="editCategory({{ $category->toJson() }})" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-edit"></i>
                                    </button>
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
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
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

    <!-- Edit Category Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showEditModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="'{{ url('/financial-transactions/categories') }}/' + editingCategory.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Chỉnh sửa danh mục</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên danh mục <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="editingCategory.name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white shadow-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Loại <span class="text-red-500">*</span></label>
                                <select name="type" x-model="editingCategory.type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white shadow-sm">
                                    <option value="income">Thu (Income)</option>
                                    <option value="expense">Chi (Expense)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ánh xạ Báo cáo Dòng tiền</label>
                                <select name="cash_flow_code" x-model="editingCategory.cash_flow_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white shadow-sm">
                                    <option value="">-- Không ánh xạ --</option>
                                    <template x-if="editingCategory.type === 'expense'">
                                        <template x-for="(code, name) in {{ json_encode($standardItems['expense']) }}" :key="code">
                                            <option :value="code" x-text="name" :selected="editingCategory.cash_flow_code == code"></option>
                                        </template>
                                    </template>
                                    <template x-if="editingCategory.type === 'income'">
                                        <template x-for="(code, name) in {{ json_encode($standardItems['income']) }}" :key="code">
                                            <option :value="code" x-text="name" :selected="editingCategory.cash_flow_code == code"></option>
                                        </template>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                <textarea name="description" x-model="editingCategory.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-gray-900 bg-white shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Cập nhật
                        </button>
                        <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
