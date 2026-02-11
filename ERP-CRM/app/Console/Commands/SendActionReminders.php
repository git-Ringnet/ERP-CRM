<?php

namespace App\Console\Commands;

use App\Models\CustomerCareStage;
use App\Notifications\ActionDueReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendActionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-action-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for actions that are due soon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for actions that need reminders...');
        
        // Find actions due in the next 15 minutes (or up to 5 minutes overdue for grace period)
        $dueActions = CustomerCareStage::query()
            ->whereNotNull('next_action')
            ->where('next_action_completed', false)
            ->whereNotNull('assigned_to')
            ->whereBetween('next_action_due_at', [
                now()->subMinutes(5), // Grace period for slightly overdue
                now()->addMinutes(15)  // Upcoming in 15 minutes
            ])
            ->with(['customer', 'assignedTo'])
            ->get();

        if ($dueActions->isEmpty()) {
            $this->info('No actions require reminders at this time.');
            return 0;
        }

        $sentCount = 0;

        foreach ($dueActions as $stage) {
            // Check if we already sent a notification in the last hour (prevent spam)
            $recentNotification = $stage->assignedTo
                ->notifications()
                ->where('type', 'App\\Notifications\\ActionDueReminder')
                ->where('data->customer_care_stage_id', $stage->id)
                ->where('created_at', '>=', now()->subHour())
                ->exists();

            if ($recentNotification) {
                $this->line("Skipping {$stage->customer->name} - already reminded recently");
                continue;
            }

            try {
                // Send notification
                $stage->assignedTo->notify(new ActionDueReminder($stage));
                $sentCount++;
                
                $this->info("✓ Sent reminder to {$stage->assignedTo->name} for: {$stage->customer->name}");
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to send reminder for {$stage->customer->name}: " . $e->getMessage());
            }
        }

        $this->info("\nCompleted! Sent {$sentCount} reminder(s).");
        
        return 0;
    }
}
