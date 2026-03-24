@extends('layouts.app')

@section('title', 'Quản lý Tiền tệ')
@section('page-title', 'Quản lý Tiền tệ')

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

        {{-- Header --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Danh sách tiền tệ</h2>
                    <p class="text-sm text-gray-500">Bật/tắt các loại tiền tệ sử dụng trong hệ thống. VND là tiền tệ cơ sở.</p>
                </div>
                </div>
            </div>
        </div>

        {{-- Currencies Table --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ký hiệu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên tiếng Anh</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên tiếng Việt</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số thập phân</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($currencies as $currency)
                        <tr class="hover:bg-gray-50 {{ $currency->is_base ? 'bg-blue-50' : '' }}">
                            <td class="px-4 py-3 text-center text-sm text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-gray-900 text-lg">{{ $currency->code }}</span>
                            </td>
                            <td class="px-4 py-3 text-lg">{{ $currency->symbol }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $currency->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $currency->name_vi }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ $currency->decimal_places }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($currency->is_base)
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <i class="fas fa-star mr-1"></i>Cơ sở
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                        Ngoại tệ
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($currency->is_active)
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Hoạt động
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Tắt
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(!$currency->is_base)
                                    <form action="{{ route('currencies.toggle', $currency) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="p-2 rounded-lg transition-colors {{ $currency->is_active ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-green-600 bg-green-50 hover:bg-green-100' }}"
                                            title="{{ $currency->is_active ? 'Tắt' : 'Bật' }}">
                                            <i class="fas {{ $currency->is_active ? 'fa-toggle-on text-lg' : 'fa-toggle-off text-lg' }}"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">Không thể tắt</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
