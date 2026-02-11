<?php

namespace App\Http\Controllers;

use App\Models\MilestoneTemplate;
use App\Models\CustomerCareStage;
use App\Http\Requests\MilestoneTemplateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MilestoneTemplateController extends Controller
{
    /**
     * Display a listing of milestone templates.
     */
    public function index(Request $request): View
    {
        $query = MilestoneTemplate::with(['creator', 'milestones']);

        if ($request->filled('stage_type')) {
            $query->forStage($request->stage_type);
        }

        $templates = $query->orderBy('stage_type')->orderBy('name')->paginate(15);

        return view('milestone-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        return view('milestone-templates.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(MilestoneTemplateRequest $request): RedirectResponse
    {
        $template = MilestoneTemplate::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        // Create template milestones from request
        if ($request->has('milestones')) {
            foreach ($request->milestones as $index => $milestone) {
                $template->milestones()->create([
                    'title' => $milestone['title'],
                    'description' => $milestone['description'] ?? null,
                    'days_from_start' => $milestone['days_from_start'] ?? 0,
                    'order' => $index,
                ]);
            }
        }

        return redirect()->route('milestone-templates.index')
            ->with('success', 'Template đã được tạo thành công.');
    }

    /**
     * Display the specified template.
     */
    public function show(MilestoneTemplate $milestoneTemplate): View
    {
        $milestoneTemplate->load(['creator', 'milestones']);
        
        return view('milestone-templates.show', compact('milestoneTemplate'));
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(MilestoneTemplate $milestoneTemplate): View
    {
        $milestoneTemplate->load('milestones');
        
        return view('milestone-templates.edit', compact('milestoneTemplate'));
    }

    /**
     * Update the specified template.
     */
    public function update(MilestoneTemplateRequest $request, MilestoneTemplate $milestoneTemplate): RedirectResponse
    {
        $milestoneTemplate->update($request->validated());

        return redirect()->route('milestone-templates.index')
            ->with('success', 'Template đã được cập nhật.');
    }

    /**
     * Remove the specified template.
     */
    public function destroy(MilestoneTemplate $milestoneTemplate): RedirectResponse
    {
        $milestoneTemplate->delete();

        return redirect()->route('milestone-templates.index')
            ->with('success', 'Template đã được xóa.');
    }

    /**
     * Apply template to a care stage.
     */
    public function apply(MilestoneTemplate $template, CustomerCareStage $stage): RedirectResponse
    {
        try {
            $template->applyTo($stage);

            return redirect()->route('customer-care-stages.show', $stage)
                ->with('success', 'Đã áp dụng template thành công với ' . $template->milestones->count() . ' mốc quan trọng.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi khi áp dụng template: ' . $e->getMessage());
        }
    }
}
