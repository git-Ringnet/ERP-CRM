    // Care Milestones routes
    Route::post('/customer-care-stages/{customerCareStage}/milestones', [\\App\\Http\\Controllers\\CareMilestoneController::class, 'store'])->name('care-milestones.store');
    Route::put('/care-milestones/{careMilestone}', [\\App\\Http\\Controllers\\CareMilestoneController::class, 'update'])->name('care-milestones.update');
    Route::delete('/care-milestones/{careMilestone}', [\\App\\Http\\Controllers\\CareMilestoneController::class, 'destroy'])->name('care-milestones.destroy');
    Route::post('/care-milestones/{careMilestone}/toggle-complete', [\\App\\Http\\Controllers\\CareMilestoneController::class, 'toggleComplete'])->name('care-milestones.toggle-complete');

    // Communication Logs
    Route::post('/customer-care-stages/{stage}/communications', [\\App\\Http\\Controllers\\CommunicationLogController::class, 'store'])->name('communications.store');
    Route::put('/communications/{log}', [\\App\\Http\\Controllers\\CommunicationLogController::class, 'update'])->name('communications.update');
    Route::delete('/communications/{log}', [\\App\\Http\\Controllers\\CommunicationLogController::class, 'destroy'])->name('communications.destroy');

    // Milestone Templates
    Route::resource('milestone-templates', \\App\\Http\\Controllers\\MilestoneTemplateController::class);
    Route::post('/milestone-templates/{template}/apply/{stage}', [\\App\\Http\\Controllers\\MilestoneTemplateController::class, 'apply'])->name('milestone-templates.apply');

    // Reminders
    Route::get('reminders', [\\App\\Http\\Controllers\\ReminderController::class, 'index'])->name('reminders.index');
    Route::post('reminders', [\\App\\Http\\Controllers\\ReminderController::class, 'store'])->name('reminders.store');
    Route::put('reminders/{reminder}', [\\App\\Http\\Controllers\\ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('reminders/{reminder}', [\\App\\Http\\Controllers\\ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::post('/reminders/{reminder}/snooze', [\\App\\Http\\Controllers\\ReminderController::class, 'snooze'])->name('reminders.snooze');

    // Customer AJAX API
    Route::get('/api/customers/{customer}/details', [\\App\\Http\\Controllers\\CustomerCareStageController::class, 'getCustomerDetails'])->name('customers.details');
});

// Auth routes (login, logout, etc.)
require __DIR__ . '/auth.php';
