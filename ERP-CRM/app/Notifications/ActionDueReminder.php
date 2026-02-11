<?php

namespace App\Notifications;

use App\Models\CustomerCareStage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActionDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $customerCareStage;

    /**
     * Create a new notification instance.
     */
    public function __construct(CustomerCareStage $customerCareStage)
    {
        $this->customerCareStage = $customerCareStage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $stage = $this->customerCareStage;
        $customer = $stage->customer;
        
        return (new MailMessage)
                    ->subject('⏰ Hành động sắp đến hạn: ' . $customer->name)
                    ->greeting('Xin chào ' . $notifiable->name)
                    ->line('Bạn có hành động sắp đến hạn cần thực hiện:')
                    ->line('**Khách hàng:** ' . $customer->name)
                    ->line('**Hành động:** ' . $stage->next_action)
                    ->line('**Đến hạn:** ' . $stage->next_action_due_at->format('d/m/Y H:i'))
                    ->action('Xem chi tiết', route('customer-care-stages.show', $stage))
                    ->line('Vui lòng hoàn thành hành động này đúng hạn để duy trì chất lượng dịch vụ!');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $stage = $this->customerCareStage;
        
        return [
            'type' => 'action_due',
            'customer_care_stage_id' => $stage->id,
            'customer_name' => $stage->customer->name,
            'next_action' => $stage->next_action,
            'due_at' => $stage->next_action_due_at->toDateTimeString(),
            'url' => route('customer-care-stages.show', $stage),
            'message' => 'Hành động "' . $stage->next_action . '" cho khách hàng ' . $stage->customer->name . ' sắp đến hạn lúc ' . $stage->next_action_due_at->format('H:i'),
        ];
    }
}
