<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Support\Speech;
use Alle80\Griglia\Tests\TestCase;
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
        $this->get('/settings')->assertOk()->assertSee('GRIGLIA_SPEECH', false)->assertSee('"mode":"browser"', false);
    }

    public function test_audio_mime_drops_the_codec_parameter(): void
    {
        // What browsers really send: the codec as a parameter, or webm guessed as video.
        $this->assertSame('audio/webm', Speech::audioMime('audio/webm;codecs=opus', 'speech.webm'));
        $this->assertSame('audio/ogg', Speech::audioMime('audio/ogg; codecs=opus', 'speech.ogg'));
        $this->assertSame('audio/webm', Speech::audioMime('video/webm', 'speech.webm'));
        $this->assertSame('audio/mp4', Speech::audioMime('audio/mp4', 'speech.mp4'));
        $this->assertSame('audio/wav', Speech::audioMime('AUDIO/WAV', 'speech.wav'));

        // Unusable mime: the extension decides.
        $this->assertSame('audio/ogg', Speech::audioMime('application/octet-stream', 'speech.opus'));
        $this->assertSame('audio/mp4', Speech::audioMime('', 'speech.m4a'));
        $this->assertSame('audio/webm', Speech::audioMime(null, null));
    }

    public function test_transcription_prompt_comes_from_config_or_translations(): void
    {
        $this->assertStringContainsString('agent', Speech::prompt());

        config(['griglia.speech_prompt' => 'Only these words: alfa, beta.']);
        $this->assertSame('Only these words: alfa, beta.', Speech::prompt());

        config(['griglia.speech_prompt' => '']);
        $this->assertSame('', Speech::prompt(), 'empty string disables the hint');
    }

    public function test_transcribe_endpoint_refuses_when_not_configured(): void
    {
        $this->post(route('griglia.transcribe'), ['audio' => UploadedFile::fake()->create('speech.webm', 10, 'audio/webm')])
            ->assertStatus(422)->assertJson(['ok' => false]);
    }
}
