{{-- Danh sách yêu cầu phối hợp Marketing gửi team của user (dùng ở Ticket Kỹ thuật & Sự kiện Marketing) --}}
    @if(isset($marketingRequests) && $marketingRequests->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-purple-200 overflow-hidden" x-data="{ open: true }">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-5 py-3 bg-purple-50 border-b border-purple-100 text-left">
            <span class="flex items-center gap-2 text-sm font-bold text-purple-800">
                <i class="fas fa-handshake"></i>
                Yêu cầu phối hợp từ Marketing
                <span class="inline-flex items-center justify-center min-w-[1.5rem] px-2 py-0.5 rounded-full bg-purple-600 text-white text-xs">{{ $marketingRequests->count() }}</span>
            </span>
            <i class="fas text-purple-500 text-xs" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
        <div x-show="open" class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5">Sự kiện</th>
                        <th class="px-4 py-2.5">Nội dung hỗ trợ</th>
                        <th class="px-4 py-2.5">Người phụ trách</th>
                        <th class="px-4 py-2.5 text-center">Trạng thái</th>
                        <th class="px-4 py-2.5 text-center">Hạn</th>
                        <th class="px-4 py-2.5 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($marketingRequests as $mr)
                        @php
                            $mrOverdue = $mr->is_overdue;
                            $mrNeedsAssign = !$mr->assigned_to;
                            $mrIsMine = (int) $mr->assigned_to === (int) auth()->id();
                            $mrLeadOnly = in_array($mr->pic_type, ['lead', 'assistant'], true);
                            $mrCanPickup = $mr->canBePickedUpBy(auth()->user());
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $mrOverdue ? 'bg-red-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $mr->event->title ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $mr->event->code ?? '' }}{{ $mr->code ? ' · ' . $mr->code : '' }}</div>
                            </td>
                            <td class="px-4 py-3 max-w-md">
                                <div class="font-medium text-gray-800">{{ $mr->support_content_label }}</div>
                                @if($mr->description)
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $mr->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($mrNeedsAssign && $mrLeadOnly)
                                    <span class="text-xs font-semibold text-amber-600"><i class="fas fa-user-clock mr-1"></i>{{ $mr->pending_pic_label }}</span>
                                @elseif($mrNeedsAssign)
                                    <span class="text-xs font-semibold text-emerald-600"><i class="fas fa-hand-paper mr-1"></i>Chờ nhận việc</span>
                                @else
                                    <span class="text-gray-800">{{ $mr->assignee->name ?? '—' }}</span>
                                    @if($mrIsMine)<span class="ml-1 text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">Bạn</span>@endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded-full {{ $mr->status_color }}">{{ $mr->status_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap {{ $mrOverdue ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                {{ $mr->deadline ? \Carbon\Carbon::parse($mr->deadline)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($mrCanPickup)
                                        <form action="{{ route('marketing-requests.pickup', $mr->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                                <i class="fas fa-hand-paper text-[10px]"></i> Nhận việc
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('marketing-events.show', $mr->marketing_event_id) }}?tab=requests&request={{ $mr->id }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                        {{ ($mrNeedsAssign && $mrLeadOnly) ? 'Phân công' : ($mrIsMine ? 'Xử lý' : 'Xem') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
