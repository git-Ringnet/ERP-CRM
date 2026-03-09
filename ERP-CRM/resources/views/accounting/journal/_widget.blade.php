{{-- Widget: Bút toán kế toán liên quan --}}
@php
    $journalEntries = \App\Models\WarehouseJournalEntry::where('reference_type', $journalType)
        ->where('reference_id', $journalReferenceId)
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

@if($journalEntries->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Ngày</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Số tiền</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Nội dung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($journalEntries as $je)
                <tr class="hover:bg-amber-50/50">
                    <td class="px-4 py-2 text-sm text-gray-700">{{ $je->entry_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold text-gray-800">{{ number_format($je->amount, 0, ',', '.') }} đ</td>
                    <td class="px-4 py-2 text-sm text-gray-600">{{ $je->description }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
