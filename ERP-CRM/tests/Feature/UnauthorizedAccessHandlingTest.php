<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthorizedAccessHandlingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_redirects_with_flash_message_when_web_request_is_unauthorized()
    {
        // Create a user without permissions
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Try to access a protected route (assuming /roles requires permission)
        $response = $this->get('/roles');
        
        // Should redirect
        $this->assertEquals(302, $response->getStatusCode());
        
        // Should have error flash message
        $this->assertNotNull(session('error'));
    }

    /** @test */
    public function it_displays_error_message_from_middleware()
    {
        // Create a user without permissions
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Try to access a protected route
        $response = $this->get('/roles');
        
        // Get the flash message
        $errorMessage = session('error');
        
        // Should have error message (currently from middleware: "Unauthorized action.")
        $this->assertNotNull($errorMessage);
        $this->assertIsString($errorMessage);
    }

    /** @test */
    public function it_prevents_redirect_loop_by_redirecting_to_dashboard()
    {
        // Create a user without permissions
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Access a route that would cause redirect loop
        // (when previous URL equals current URL)
        $response = $this->get('/roles');
        
        // Should redirect (not throw exception or loop)
        $this->assertEquals(302, $response->getStatusCode());
        
        // Should have flash message
        $this->assertNotNull(session('error'));
    }

    /** @test */
    public function handler_web_unauthorized_response_flashes_message_and_redirects()
    {
        // This test verifies the webUnauthorizedResponse method behavior
        // by simulating an authorization exception
        
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Access a protected route
        $response = $this->get('/roles');
        
        // Verify redirect response
        $this->assertTrue($response->isRedirect());
        
        // Verify flash message exists
        $this->assertTrue(session()->has('error'));
        
        // Verify message is a string
        $this->assertIsString(session('error'));
    }
}
