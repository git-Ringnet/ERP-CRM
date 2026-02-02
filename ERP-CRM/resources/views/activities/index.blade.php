@extends('layouts.app')

@section('title', 'Danh sách Công việc')

@section('content')
    <div class="h-full flex flex-col">
        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-sm mb-4 shrink-0">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-tasks text-blue-500 mr-2"></i>Danh sách Công việc
                    </h2>
                </div>
            </div>
        </div>

        <!-- Task List -->
        <div class="bg-white rounded-lg shadow-sm flex-1 p-4">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hoàn thành</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Công việc</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hạn chót</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liên quan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($activities as $activity)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 w-10">
                                    <form action="{{ route('activities.update', $activity) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="toggle_status" value="1">
                                        <button type="submit" class="text-gray-400 hover:text-green-500">
                                            <i class="far fa-square fa-lg"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $activity->subject }}</div>
                                    @if($activity->description)
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $activity->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($activity->due_date)
                                        <span
                                            class="text-sm {{ $activity->due_date->isPast() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                            {{ $activity->due_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($activity->opportunity)
                                        <a href="{{ route('opportunities.show', $activity->opportunity) }}"
                                            class="text-blue-600 hover:underline">
                                            <i class="fas fa-funnel-dollar mr-1"></i> {{ $activity->opportunity->name }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $activity->customer->name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-check-circle text-4xl mb-3 text-gray-300"></i>
                                    <p>Tuyệt vời! Bạn không còn công việc nào tồn đọng.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
@endsection