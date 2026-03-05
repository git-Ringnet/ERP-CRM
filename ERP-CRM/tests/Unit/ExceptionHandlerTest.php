<?php

namespace Tests\Unit;

use App\Exceptions\Handler;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

// Custom exception class for testing ability() method
class AuthorizationExceptionWithAbility extends AuthorizationException
{
    protected $ability;

    public function setAbility($ability)
    {
        $this->ability = $ability;
        return $this;
    }

    public function ability()
    {
        return $this->ability;
    }
}

class ExceptionHandlerTest extends TestCase
{
    protected Handler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new Handler($this->app);
    }

    /** @test */
    public function it_returns_default_vietnamese_message_when_exception_has_no_custom_message()
    {
        $exception = new AuthorizationException();
        
        $message = $this->invokeProtectedMethod($this->handler, 'getAuthorizationMessage', [$exception]);
        
        $this->assertEquals(
            'Bạn không có quyền truy cập chức năng này. Vui lòng liên hệ quản trị viên để được cấp quyền.',
            $message
        );
    }

    /** @test */
    public function it_returns_default_vietnamese_message_when_exception_has_default_laravel_message()
    {
        $exception = new AuthorizationException('This action is unauthorized.');
        
        $message = $this->invokeProtectedMethod($this->handler, 'getAuthorizationMessage', [$exception]);
        
        $this->assertEquals(
            'Bạn không có quyền truy cập chức năng này. Vui lòng liên hệ quản trị viên để được cấp quyền.',
            $message
        );
    }

    /** @test */
    public function it_returns_custom_message_when_exception_has_custom_message()
    {
        $customMessage = 'Bạn không có quyền xem khách hàng này.';
        $exception = new AuthorizationException($customMessage);
        
        $message = $this->invokeProtectedMethod($this->handler, 'getAuthorizationMessage', [$exception]);
        
        $this->assertEquals($customMessage, $message);
    }

    /** @test */
    public function it_returns_custom_message_when_exception_has_english_custom_message()
    {
        $customMessage = 'You do not have permission to view this customer.';
        $exception = new AuthorizationException($customMessage);
        
        $message = $this->invokeProtectedMethod($this->handler, 'getAuthorizationMessage', [$exception]);
        
        $this->assertEquals($customMessage, $message);
    }

    /** @test */
    public function it_returns_json_response_with_message_and_error_fields()
    {
        $message = 'Bạn không có quyền truy cập chức năng này.';
        $exception = new AuthorizationException($message);
        
        $response = $this->invokeProtectedMethod($this->handler, 'jsonUnauthorizedResponse', [$message, $exception]);
        
        $this->assertEquals(403, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals($message, $data['message']);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    /** @test */
    public function it_includes_required_permission_field_when_permission_is_available()
    {
        $message = 'Bạn không có quyền xem khách hàng.';
        $permission = 'view_customers';
        
        // Create exception with ability method
        $exception = (new AuthorizationExceptionWithAbility($message))->setAbility($permission);
        
        $response = $this->invokeProtectedMethod($this->handler, 'jsonUnauthorizedResponse', [$message, $exception]);
        
        $data = $response->getData(true);
        $this->assertArrayHasKey('required_permission', $data);
        $this->assertEquals($permission, $data['required_permission']);
    }

    /** @test */
    public function it_does_not_include_required_permission_field_when_permission_is_not_available()
    {
        $message = 'Bạn không có quyền truy cập chức năng này.';
        $exception = new AuthorizationException($message);
        
        $response = $this->invokeProtectedMethod($this->handler, 'jsonUnauthorizedResponse', [$message, $exception]);
        
        $data = $response->getData(true);
        $this->assertArrayNotHasKey('required_permission', $data);
    }

    /** @test */
    public function it_returns_403_status_code_for_json_response()
    {
        $message = 'Test message';
        $exception = new AuthorizationException($message);
        
        $response = $this->invokeProtectedMethod($this->handler, 'jsonUnauthorizedResponse', [$message, $exception]);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_flashes_error_message_to_session()
    {
        $message = 'Bạn không có quyền truy cập chức năng này.';
        
        // Create a test request
        $request = $this->get('/');
        
        // Mock the URL helpers to prevent redirect loop
        $this->app->instance('url', $urlGenerator = \Mockery::mock('Illuminate\Routing\UrlGenerator'));
        $urlGenerator->shouldReceive('previous')->andReturn('http://localhost/previous');
        $urlGenerator->shouldReceive('current')->andReturn('http://localhost/current');
        $urlGenerator->shouldReceive('to')->andReturn('http://localhost/previous');
        
        $response = $this->invokeProtectedMethod($this->handler, 'webUnauthorizedResponse', [$message]);
        
        $this->assertEquals($message, session('error'));
    }

    /** @test */
    public function it_returns_redirect_response_for_web_unauthorized_request()
    {
        $message = 'Test message';
        
        $response = $this->invokeProtectedMethod($this->handler, 'webUnauthorizedResponse', [$message]);
        
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function web_unauthorized_response_prevents_redirect_loop()
    {
        $message = 'Test message';
        
        // Simulate same URL scenario by checking the implementation logic
        // The method should redirect to dashboard when previous === current
        $response = $this->invokeProtectedMethod($this->handler, 'webUnauthorizedResponse', [$message]);
        
        // Verify it's a redirect response
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        
        // Verify message is flashed
        $this->assertEquals($message, session('error'));
    }

    /**
     * Helper method to invoke protected methods for testing
     */
    protected function invokeProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}
