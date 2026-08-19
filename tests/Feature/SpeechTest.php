<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Support\Speech;
use Alle80\Devboard\Tests\TestCase;
use Illuminate\Http\UploadedFile;

/** Speech to text mode resolution + the transcription endpoint guard (the AI SDK is not installed here). */
class SpeechTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function test_mode_falls_back_to_browser_without_the_ai_sdk(): void
    {
        $this->assertSame('auto', app(AppSettings::class)->speech_mode);
        $this->assertFalse(Speech::serverAvailable());
        $this->assertSame('browser', Speech::mode());

        $app = app(AppSettings::class);
        $app->speech_mode = 'server';
        $app->save();
        $this->assertSame('browser', Speech::mode(), 'server requested but unavailable → browser');

        $this->assertSame('en', Speech::language());
        $this->get('/settings')->assertOk()->assertSee('DEVBOARD_SPEECH', false)->assertSee('"mode":"browser"', false);
    }

    public function test_transcribe_endpoint_refuses_when_not_configured(): void
    {
        $this->post(route('devboard.transcribe'), ['audio' => UploadedFile::fake()->create('speech.webm', 10, 'audio/webm')])
            ->assertStatus(422)->assertJson(['ok' => false]);
    }
}
