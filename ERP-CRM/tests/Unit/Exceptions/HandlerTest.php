<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    protected Handler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new Handler($this->app);
    }

    /** @test */
    public function it_extracts_message_from_authorization_exception()
    {
        $customMessage = 'Custom authorization message';
        $exception = new AuthorizationException($customMessage);
        
        $request = Request::create('/test', 'GET');
        $response = $this->handler->render($request, $exception);
        
        // For web requests, it should redirect with flash message
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($customMessage, session('error'));
    }

    /** @test */
    public function it_detects_json_request_type()
    {
        $exception = new AuthorizationException('Test message');
        
        // Create a request that expects JSON
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($request, $exception);
        
        // Should return JSON response
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_routes_to_json_response_for_api_requests()
    {
        $exception = new AuthorizationException('API test message');
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($request, $exception);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API test message', $data['message']);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    /** @test */
    public function it_routes_to_web_response_for_html_requests()
    {
        $exception = new AuthorizationException('Web test message');
        
        $request = Request::create('/test', 'GET');
        
        $response = $this->handler->render($request, $exception);
        
        // Should redirect
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Web test message', session('error'));
    }

    /** @test */
    public function it_handles_http_exception_403_with_custom_message()
    {
        $customMessage = 'Custom forbidden message';
        $exception = new HttpException(403, $customMessage);
        
        $request = Request::create('/test', 'GET');
        $response = $this->handler->render($request, $exception);
        
        // For web requests, it should redirect with flash message
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($customMessage, session('error'));
    }

    /** @test */
    public function it_handles_http_exception_403_with_default_message()
    {
        $exception = new HttpException(403, '');
        
        $request = Request::create('/test', 'GET');
        $response = $this->handler->render($request, $exception);
        
        // Should use default Vietnamese message
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Bạn không có quyền truy cập chức năng này.', session('error'));
    }

    /** @test */
    public function it_returns_json_for_http_exception_403_api_requests()
    {
        $customMessage = 'API forbidden message';
        $exception = new HttpException(403, $customMessage);
        
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->handler->render($request, $exception);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals($customMessage, $data['message']);
        $this->assertEquals('Forbidden', $data['error']);
    }

    /** @test */
    public function it_detects_request_type_for_http_exception_403()
    {
        $exception = new HttpException(403, 'Test message');
        
        // Test JSON request
        $jsonRequest = Request::create('/api/test', 'GET');
        $jsonRequest->headers->set('Accept', 'application/json');
        
        $jsonResponse = $this->handler->render($jsonRequest, $exception);
        
        $this->assertEquals(403, $jsonResponse->getStatusCode());
        $this->assertEquals('application/json', $jsonResponse->headers->get('Content-Type'));
        
        // Test HTML request
        $htmlRequest = Request::create('/test', 'GET');
        $htmlResponse = $this->handler->render($htmlRequest, $exception);
        
        $this->assertEquals(302, $htmlResponse->getStatusCode());
    }
}
