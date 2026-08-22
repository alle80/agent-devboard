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

    public function test_the_front_end_payload_carries_every_label_and_the_time_limit(): void
    {
        // The mic button reports errors on its own (the JS cannot read the translations): a missing
        // label means the user talks for five minutes and is never told that it failed (task 431).
        $speech = Speech::frontend();

        foreach (['mode', 'url', 'csrf', 'lang', 'max_seconds', 'start', 'stop', 'busy', 'error',
            'retry', 'empty', 'silent', 'denied', 'lost', 'expired', 'kept', 'recovered', 'limit'] as $key) {
            $this->assertArrayHasKey($key, $speech);
            if ($key !== 'csrf') {   // the token is empty without a session, the labels never are
                $this->assertNotSame('', (string) $speech[$key], "empty speech label: $key");
            }
        }

        $this->assertSame(300, $speech['max_seconds'], 'a dictation is closed and transcribed after five minutes');
        $this->get('/settings')->assertOk()->assertSee('"max_seconds":300', false);
    }

    public function test_the_time_limit_comes_from_the_config_and_can_be_disabled(): void
    {
        config(['griglia.speech_max_seconds' => 120]);
        $this->assertSame(120, Speech::maxSeconds());

        config(['griglia.speech_max_seconds' => 0]);
        $this->assertSame(0, Speech::maxSeconds(), '0 = no limit');

        config(['griglia.speech_max_seconds' => -5]);
        $this->assertSame(0, Speech::maxSeconds(), 'a negative limit would stop the recording at once');
    }

    public function test_the_shipped_build_contains_the_resilient_dictation(): void
    {
        // The dictation must survive a Livewire re-render: the session lives in the module (not in the
        // Alpine component) and a failed upload keeps its audio. Guard the built bundle, not the source.
        $js = file_get_contents(__DIR__.'/../../public/build/griglia.js');

        $this->assertStringContainsString('grigliaMic', $js);
        $this->assertStringContainsString('max_seconds', $js);
        $this->assertStringContainsString('isConnected', $js, 'the target field is resolved again at every tick');
        $this->assertStringContainsString('beforeunload', $js, 'leaving the page mid-dictation must warn');
    }

    public function test_transcribe_endpoint_refuses_when_not_configured(): void
    {
        $this->post(route('griglia.transcribe'), ['audio' => UploadedFile::fake()->create('speech.webm', 10, 'audio/webm')])
            ->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_transcription_upload_declares_an_audio_mime_allow_list(): void
    {
        $controller = file_get_contents(__DIR__.'/../../src/Http/Controllers/TranscribeController.php');

        $this->assertStringContainsString('mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,audio/mpeg,audio/wav,audio/x-wav,application/ogg', $controller);
    }
}
