@extends('layouts.app')

@section('title', 'Quản lý Tỷ giá')
@section('page-title', 'Quản lý Tỷ giá Hối đoái')

@section('content')
    <div class="space-y-6">
        {{-- Navigation Tabs --}}
        <div class="bg-white rounded-t-lg border-b border-gray-200 px-4">
            <nav class="flex -mb-px space-x-8" aria-label="Tabs">
                <a href="{{ route('exchange-rates.index') }}"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('exchange-rates.*') ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-exchange-alt mr-2"></i>Tỷ giá hối đoái
                </a>
                <a href="{{ route('currencies.index') }}"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ request()->routeIs('currencies.*') ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-coins mr-2"></i>Danh mục tiền tệ
                </a>
            </nav>
        </div>

        {{-- Header Actions --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Tỷ giá hối đoái</h2>
                    <p class="text-sm text-gray-500">Nguồn: Ngân hàng Vietcombank (tự động cập nhật lúc 8:00 sáng)</p>
                </div>
                <div class="flex gap-2">
                    <form action="{{ route('exchange-rates.fetch-today') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Fetch tỷ giá hôm nay
                        </button>
                    </form>
                    <button type="button" onclick="document.getElementById('addRateModal').classList.remove('hidden')"
                        class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Nhập thủ công
                    </button>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form action="{{ route('exchange-rates.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <select name="currency_id"
                    class="h-10 border border-gray-300 rounded-lg pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm bg-white appearance-none cursor-pointer">
                    <option value="">Tất cả ngoại tệ</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ request('currency_id') == $currency->id ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name_vi }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Từ ngày"
                    class="h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Đến ngày"
                    class="h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">

                <select name="source"
                    class="h-10 border border-gray-300 rounded-lg pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm bg-white appearance-none cursor-pointer">
                    <option value="">Tất cả nguồn</option>
                    <option value="auto" {{ request('source') == 'auto' ? 'selected' : '' }}>Tự động (VCB)</option>
                    <option value="manual" {{ request('source') == 'manual' ? 'selected' : '' }}>Nhập thủ công</option>
                </select>

                <button type="submit"
                    class="h-10 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('exchange-rates.index') }}"
                    class="h-10 px-4 flex items-center text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times mr-1"></i> Xóa lọc
                </a>
            </form>
        </div>

        {{-- Rates Table --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">STT</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiền tệ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày áp dụng</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tỷ giá</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nguồn</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rates as $rate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center text-sm text-gray-500">
                                    {{ ($rates->currentPage() - 1) * $rates->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $rate->currency->symbol }}</span>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $rate->currency->code }}</div>
                                            <div class="text-xs text-gray-500">{{ $rate->currency->name_vi }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $rate->effective_date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    {{ number_format($rate->rate, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($rate->source === 'auto')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fas fa-robot mr-1"></i>VCB
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-user mr-1"></i>Thủ công
                                        </span>
                                    @endif
                                    @if($rate->creator)
                                        <div class="text-xs text-gray-400 mt-1">{{ $rate->creator->name }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button"
                                        onclick="editRate({{ $rate->id }}, {{ $rate->rate }})"
                                        class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors"
                                        title="Sửa tỷ giá">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-chart-line text-4xl mb-2"></i>
                                    <p>Chưa có dữ liệu tỷ giá</p>
                                    <p class="text-sm mt-1">Nhấn "Fetch tỷ giá hôm nay" để lấy tỷ giá từ Vietcombank</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $rates->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: Thêm tỷ giá thủ công --}}
    <div id="addRateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Nhập tỷ giá thủ công</h3>
                    <button onclick="document.getElementById('addRateModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form action="{{ route('exchange-rates.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiền tệ</label>
                    <select name="currency_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name_vi }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày áp dụng</label>
                    <input type="date" name="effective_date" value="{{ date('Y-m-d') }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá <span class="text-red-500">*</span></label>
                    <input type="number" name="rate" step="0.000001" required placeholder="VD: 25400"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('addRateModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-save mr-1"></i> Lưu tỷ giá
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Sửa tỷ giá --}}
    <div id="editRateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Sửa tỷ giá</h3>
                    <button onclick="document.getElementById('editRateModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form id="editRateForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá <span class="text-red-500">*</span></label>
                    <input type="number" name="rate" id="editRate" step="0.000001" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('editRateModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-save mr-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function editRate(id, rate) {
        document.getElementById('editRateForm').action = `/exchange-rates/${id}`;
        document.getElementById('editRate').value = rate;
        document.getElementById('editRateModal').classList.remove('hidden');
    }
</script>
@endpush
