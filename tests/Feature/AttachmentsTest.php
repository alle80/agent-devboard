<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Support\ImageStore;
use Alle80\Griglia\Tests\Support\User;
use Alle80\Griglia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Attachments: pixel limit before decoding, authorised controller route. */
class AttachmentsTest extends TestCase
{
    public function test_store_refuses_decompression_bombs_and_serves_through_the_authorised_route(): void
    {
        Storage::fake('public');
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'l', 'user_id' => $user->id]);
        $todo = Todo::create(['title' => 't', 'order' => 1, 'checklist_id' => $list->id]);

        $a = ImageStore::store($todo, UploadedFile::fake()->image('ok.png', 40, 30));
        $this->assertSame(40, $a->width);
        $this->assertStringContainsString('/griglia/attachments/'.$a->id, $a->url(), 'served by the authorised route');
        $this->get($a->url())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        // another user cannot fetch it
        $this->actingAs(User::create(['name' => 'B', 'email' => 'b@x.it', 'password' => bcrypt('s')]));
        $this->get($a->url())->assertNotFound();
        $this->actingAs($user);

        // a "bomb": tiny file, huge declared dimensions (PNG header claims 20000×20000)
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAATiAAAE4gCAYAAADx').str_repeat("\0", 64);
        $png = substr_replace($png, pack('N', 20000), 16, 4); // width
        $png = substr_replace($png, pack('N', 20000), 20, 4); // height
        $bomb = UploadedFile::fake()->createWithContent('bomb.png', $png);
        try {
            ImageStore::store($todo, $bomb);
            $this->fail('bomb accepted');
        } catch (\RuntimeException $e) {
            $this->assertTrue(str_contains($e->getMessage(), 'megapixel') || str_contains($e->getMessage(), 'supportato') || str_contains($e->getMessage(), 'leggibile'), $e->getMessage());
        }

        // public disk url when the controller is disabled
        config(['griglia.attachments_via_controller' => false]);
        $this->assertStringContainsString('/storage/', $a->url());
    }
}
