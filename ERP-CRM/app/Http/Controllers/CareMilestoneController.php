<?php

namespace App\Http\Controllers;

use App\Models\CareMilestone;
use App\Models\CustomerCareStage;
use App\Http\Requests\CareMilestoneRequest;
use Illuminate\Http\Request;

class CareMilestoneController extends Controller
{
    /**
     * Store a newly created milestone
     */
    public function store(CareMilestoneRequest $request, CustomerCareStage $customerCareStage)
    {
        $validated = $request->validated();
        $validated['customer_care_stage_id'] = $customerCareStage->id;

        // Auto-set order if not provided
        if (!isset($validated['order'])) {
            $maxOrder = $customerCareStage->milestones()->max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        CareMilestone::create($validated);

        return back()->with('success', 'Mốc quan trọng đã được thêm thành công.');
    }

    /**
     * Update the specified milestone
     */
    public function update(CareMilestoneRequest $request, CareMilestone $careMilestone)
    {
        $validated = $request->validated();
        $careMilestone->update($validated);

        return back()->with('success', 'Mốc quan trọng đã được cập nhật.');
    }

    /**
     * Remove the specified milestone
     */
    public function destroy(CareMilestone $careMilestone)
    {
        $careMilestone->delete();

        return back()->with('success', 'Mốc quan trọng đã được xóa.');
    }

    /**
     * Toggle milestone completion status
     */
    public function toggleComplete(Request $request, CareMilestone $careMilestone)
    {
        if ($careMilestone->is_completed) {
            $careMilestone->markAsPending();
            $message = 'Mốc quan trọng đã được đánh dấu chưa hoàn thành.';
        } else {
            $careMilestone->markAsCompleted(auth()->id());
            $message = 'Mốc quan trọng đã được đánh dấu hoàn thành.';
            
            // Update care stage progress based on milestones
            $careStage = $careMilestone->customerCareStage;
            $totalMilestones = $careStage->milestones()->count();
            $completedMilestones = $careStage->milestones()->where('is_completed', true)->count();
            
            if ($totalMilestones > 0) {
                $progress = (int) round(($completedMilestones / $totalMilestones) * 100);
                $careStage->update(['completion_percentage' => $progress]);
                
                // Auto-complete care stage if all milestones are done
                if ($progress === 100 && $careStage->status !== 'completed') {
                    $careStage->update([
                        'status' => 'completed',
                        'actual_completion_date' => now()->toDateString(),
                    ]);
                }
            }
        }

        return back()->with('success', $message);
    }
}
