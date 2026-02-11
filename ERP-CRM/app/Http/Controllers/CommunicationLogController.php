<?php

namespace App\Http\Controllers;

use App\Models\CommunicationLog;
use App\Models\CustomerCareStage;
use App\Http\Requests\CommunicationLogRequest;
use Illuminate\Http\RedirectResponse;

class CommunicationLogController extends Controller
{
    /**
     * Store a newly created communication log.
     */
    public function store(CommunicationLogRequest $request, CustomerCareStage $stage): RedirectResponse
    {
        $stage->communicationLogs()->create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'subject' => $request->subject,
            'description' => $request->description,
            'sentiment' => $request->sentiment,
            'duration_minutes' => $request->duration_minutes,
            'occurred_at' => $request->occurred_at,
        ]);

        return redirect()->back()->with('success', 'Đã ghi nhận giao tiếp thành công.');
    }

    /**
     * Update the specified communication log.
     */
    public function update(CommunicationLogRequest $request, CommunicationLog $log): RedirectResponse
    {
        $log->update($request->validated());

        return redirect()->back()->with('success', 'Đã cập nhật thông tin giao tiếp.');
    }

    /**
     * Remove the specified communication log.
     */
    public function destroy(CommunicationLog $log): RedirectResponse
    {
        $log->delete();

        return redirect()->back()->with('success', 'Đã xóa thông tin giao tiếp.');
    }
}
