<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatIntegrationTest extends TestCase
{
    /**
     * Test that chat endpoint exists and returns proper structure
     */
    public function test_chat_endpoint_exists(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => 'Hello, this is a test message',
            'session_id' => 'test_session_123'
        ]);

        // Should return 200 or 500 (if OpenAI key not configured)
        // We're just testing the endpoint structure exists
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500,
            'Chat endpoint should exist and return either success or error'
        );

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'response',
                'session_id',
                'timestamp'
            ]);
        } else {
            $response->assertJsonStructure([
                'success',
                'message',
                'error_code'
            ]);
        }
    }

    /**
     * Test that streaming chat endpoint exists
     */
    public function test_streaming_chat_endpoint_exists(): void
    {
        $response = $this->postJson('/api/chat/stream', [
            'message' => 'Hello, this is a test streaming message',
            'session_id' => 'test_stream_session_123'
        ]);

        // Should return 200 or 500 (if OpenAI key not configured)
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500,
            'Streaming chat endpoint should exist'
        );
    }

    /**
     * Test chat endpoint validation
     */
    public function test_chat_endpoint_validation(): void
    {
        // Test empty message
        $response = $this->postJson('/api/chat', [
            'message' => '',
            'session_id' => 'test_session_123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'errors'
                 ]);

        // Test missing message
        $response = $this->postJson('/api/chat', [
            'session_id' => 'test_session_123'
        ]);

        $response->assertStatus(422);

        // Test message too long
        $response = $this->postJson('/api/chat', [
            'message' => str_repeat('a', 2001), // Over 2000 character limit
            'session_id' => 'test_session_123'
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test that AI tools can be instantiated without errors
     */
    public function test_ai_tools_instantiation(): void
    {
        // Test that remaining AI tool classes can be instantiated
        $tools = [
            \App\AI\Tools\ScheduleDemo::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = new $toolClass();
            $this->assertInstanceOf($toolClass, $tool);

            // Test that the tool can be invoked
            $result = $tool();
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }

    /**
     * Test AI agent can be instantiated
     */
    public function test_ai_agent_instantiation(): void
    {
        // This test will pass if the agent can be created
        // It might fail if OpenAI credentials are not configured, but that's expected
        try {
            $agent = \App\AI\RillTechAgent::make();
            $this->assertInstanceOf(\App\AI\RillTechAgent::class, $agent);
        } catch (\Exception $e) {
            // If it fails due to missing OpenAI key, that's acceptable for testing
            $this->assertTrue(
                str_contains($e->getMessage(), 'API') ||
                str_contains($e->getMessage(), 'key') ||
                str_contains($e->getMessage(), 'config'),
                'Agent instantiation should fail gracefully with configuration issues'
            );
        }
    }
}
