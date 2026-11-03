<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\TechnicalTicket;
use App\Models\TechnicalTicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalTicketCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $creatorUser;
    protected User $engineerUser;
    protected User $leadUser;
    protected User $salesOwnerUser;
    protected TechnicalTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $techLeadRole = Role::firstOrCreate(['slug' => 'technical_lead'], ['name' => 'Technical Lead']);
        $engineerRole = Role::firstOrCreate(['slug' => 'technical_engineer'], ['name' => 'Technical Engineer']);
        $salesRole = Role::firstOrCreate(['slug' => 'sales'], ['name' => 'Sales Staff']);

        $this->creatorUser = User::factory()->create(['name' => 'Nguyen Van Creator', 'status' => 'active', 'is_locked' => false]);
        $this->creatorUser->roles()->syncWithoutDetaching([$salesRole->id]);

        $this->salesOwnerUser = User::factory()->create(['name' => 'Tran Thi SalesOwner', 'status' => 'active', 'is_locked' => false]);
        $this->salesOwnerUser->roles()->syncWithoutDetaching([$salesRole->id]);

        $this->engineerUser = User::factory()->create(['name' => 'Le Van Engineer', 'status' => 'active', 'is_locked' => false]);
        $this->engineerUser->roles()->syncWithoutDetaching([$engineerRole->id]);

        $this->leadUser = User::factory()->create(['name' => 'Pham Van Lead', 'status' => 'active', 'is_locked' => false]);
        $this->leadUser->roles()->syncWithoutDetaching([$techLeadRole->id]);

        $this->ticket = TechnicalTicket::create([
            'code' => 'TK-TEST-001',
            'title' => 'Test Ticket trao đổi',
            'status' => 'in_progress',
            'work_type' => 'support',
            'priority' => 'medium',
            'created_by' => $this->creatorUser->id,
            'sales_owner_id' => $this->salesOwnerUser->id,
            'assigned_to' => $this->engineerUser->id,
            'team_lead_id' => $this->leadUser->id,
        ]);

        $this->ticket->assignedEngineers()->sync([$this->engineerUser->id]);
    }

    public function test_posting_comment_notifies_relevant_stakeholders()
    {
        $commentContent = 'Kỹ thuật đã kiểm tra xong thiết bị, cấu hình hoạt động bình thường.';

        $response = $this->actingAs($this->engineerUser)
            ->post(route('technical-tickets.comments.store', $this->ticket->id), [
                'comment' => $commentContent,
            ]);

        $response->assertRedirect(route('technical-tickets.show', [
            'technical_ticket' => $this->ticket->id,
            'tab' => 'comments',
        ]));

        $comment = TechnicalTicketComment::where('technical_ticket_id', $this->ticket->id)->latest('id')->first();
        $this->assertNotNull($comment);
        $this->assertEquals($commentContent, $comment->comment);

        // Sender should NOT receive notification
        $senderNotification = Notification::where('user_id', $this->engineerUser->id)
            ->where('type', 'technical_ticket_comment')
            ->first();
        $this->assertNull($senderNotification);

        // Creator should receive notification
        $creatorNotification = Notification::where('user_id', $this->creatorUser->id)
            ->where('type', 'technical_ticket_comment')
            ->first();
        $this->assertNotNull($creatorNotification);
        $this->assertStringContainsString('TK-TEST-001', $creatorNotification->title);
        $this->assertStringContainsString('Le Van Engineer', $creatorNotification->message);
        $this->assertStringContainsString('tab=comments', $creatorNotification->link);
        $this->assertStringContainsString('#comment-' . $comment->id, $creatorNotification->link);
        $this->assertEquals('comments', $creatorNotification->icon);
        $this->assertTrue($creatorNotification->isAccessibleBy($this->creatorUser));

        // Sales Owner should receive notification
        $salesOwnerNotification = Notification::where('user_id', $this->salesOwnerUser->id)
            ->where('type', 'technical_ticket_comment')
            ->first();
        $this->assertNotNull($salesOwnerNotification);
        $this->assertTrue($salesOwnerNotification->isAccessibleBy($this->salesOwnerUser));

        // Team Lead should receive notification
        $leadNotification = Notification::where('user_id', $this->leadUser->id)
            ->where('type', 'technical_ticket_comment')
            ->first();
        $this->assertNotNull($leadNotification);
        $this->assertTrue($leadNotification->isAccessibleBy($this->leadUser));
    }

    public function test_creator_comment_notifies_assigned_engineer()
    {
        $commentContent = 'Khách hàng vừa gửi thêm tài liệu logs mới.';

        $response = $this->actingAs($this->creatorUser)
            ->post(route('technical-tickets.comments.store', $this->ticket->id), [
                'comment' => $commentContent,
            ]);

        $response->assertRedirect();

        // Engineer should receive notification
        $engineerNotification = Notification::where('user_id', $this->engineerUser->id)
            ->where('type', 'technical_ticket_comment')
            ->first();
        $this->assertNotNull($engineerNotification);
        $this->assertStringContainsString('Nguyen Van Creator', $engineerNotification->message);
        $this->assertTrue($engineerNotification->isAccessibleBy($this->engineerUser));

        // Creator should NOT receive notification
        $creatorNotification = Notification::where('user_id', $this->creatorUser->id)
            ->where('type', 'technical_ticket_comment')
            ->first();
        $this->assertNull($creatorNotification);
    }
}

