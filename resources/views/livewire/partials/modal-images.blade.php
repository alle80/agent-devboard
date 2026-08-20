{{--
    Sezione immagini del modale, condivisa da tutti gli stili.
    Variabili attese: $todo, $imageError, più le classi di stile:
      $labelClass  intestazione ("Immagini")
      $btnClass    bottoni "Aggiungi" / "Scatta"
      $hintClass   testo di aiuto (incolla)
      $thumbClass  cornice delle anteprime
--}}
<div
    x-data="{
        uploading: false,
        progress: 0,
        // Da appunti: screenshot o immagine copiata → Livewire upload
        onPaste(e) {
            const items = Array.from(e.clipboardData?.items || []).filter(i => i.type.startsWith('image/'))
            if (!items.length) return
            e.preventDefault()
            const files = items.map(i => i.getAsFile()).filter(Boolean)
            this.send(files)
        },
        onDrop(e) {
            const files = Array.from(e.dataTransfer?.files || []).filter(f => f.type.startsWith('image/'))
            if (files.length) this.send(files)
        },
        send(files) {
            this.uploading = true; this.progress = 0
            $wire.uploadMultiple('images', files,
                () => { this.uploading = false },
                () => { this.uploading = false },
                (ev) => { this.progress = ev.detail.progress },
            )
        },
    }"
    x-on:paste.window="onPaste($event)"
    x-on:drop.prevent="onDrop($event)"
    x-on:dragover.prevent
>
    <div class="mb-2 flex items-center justify-between gap-2">
        <span class="{{ $labelClass }}">{{ __('griglia::t.images') }}</span>
        <div class="flex items-center gap-2">
            {{-- Da galleria / file --}}
            <label class="{{ $btnClass }} cursor-pointer">
                <x-griglia::icon name="image" /> {{ __('griglia::t.add_image') }}
                <input type="file" accept="image/jpeg,image/png,image/gif" multiple class="sr-only" wire:model="images">
            </label>
            {{-- Fotocamera (su smartphone apre direttamente la camera) --}}
            <label class="{{ $btnClass }} cursor-pointer sm:hidden">
                <x-griglia::icon name="camera" /> {{ __('griglia::t.take_photo') }}
                <input type="file" accept="image/*" capture="environment" class="sr-only" wire:model="images">
            </label>
        </div>
    </div>

    <p class="{{ $hintClass }} mb-2 text-xs" x-show="!uploading">{{ __('griglia::t.paste_hint') }}</p>
    <p class="{{ $hintClass }} mb-2 text-xs" x-show="uploading" x-cloak>{{ __('griglia::t.uploading') }} <span x-text="progress"></span>%</p>
    <p class="{{ $hintClass }} mb-2 text-xs" wire:loading wire:target="images">{{ __('griglia::t.processing_image') }}</p>

    @if ($imageError)
        <p class="mb-2 text-sm font-bold text-red-600">{{ $imageError }}</p>
    @endif

    @if ($todo->attachments->isNotEmpty())
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4" x-data="{ zoom: null, caption: '', name: '' }">
            @foreach ($todo->attachments as $img)
                <div wire:key="att-{{ $img->id }}" class="group relative">
                    <button type="button" x-on:click="zoom = @js($img->url()); caption = @js((string) $img->description); name = @js($img->original_name)" class="{{ $thumbClass }} block aspect-square w-full cursor-zoom-in overflow-hidden" title="{{ $img->description ? \Illuminate\Support\Str::limit($img->description, 140) : $img->original_name }}">
                        <img src="{{ $img->url() }}" alt="{{ $img->original_name }}" width="{{ $img->width ?? 400 }}" height="{{ $img->height ?? 400 }}" loading="lazy" class="size-full object-cover">
                    </button>
                    <button
                        type="button"
                        wire:click="deleteAttachment({{ $img->id }})"
                        wire:confirm="{{ __('griglia::t.delete_image_confirm', ['name' => $img->original_name]) }}"
                        title="{{ __('griglia::t.delete_image') }}"
                        class="absolute -top-1.5 -right-1.5 flex size-6 cursor-pointer items-center justify-center rounded-full border-2 border-black bg-white text-xs font-bold text-red-600 shadow transition sm:opacity-0 sm:group-hover:opacity-100 sm:focus:opacity-100"
                        aria-label="{{ __('griglia::t.delete_image') }}"
                    ><x-griglia::icon name="close" /></button>
                </div>
            @endforeach

            {{-- Lightbox --}}
            <template x-teleport="body">
                <div x-show="zoom" x-cloak x-on:click="zoom = null" x-on:keydown.escape.window="zoom = null" class="db-lightbox fixed inset-0 z-[80] flex cursor-zoom-out flex-col items-center justify-center gap-3 bg-black/90 p-4">
                    <img :src="zoom" :alt="caption || name" class="min-h-0 max-w-full flex-1 object-contain">
                    {{-- AI description of the picture (what the search sees), under the image --}}
                    <figcaption x-show="caption || name" class="db-lightbox-caption max-h-[30vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-black/60 px-4 py-3 text-center text-sm leading-relaxed text-white" x-on:click.stop>
                        <span class="block text-xs uppercase tracking-wide opacity-60">{{ __('griglia::t.image_description') }}</span>
                        <span x-text="caption || @js(__('griglia::t.image_no_description'))" x-bind:class="caption ? '' : 'italic opacity-60'"></span>
                    </figcaption>
                </div>
            </template>
        </div>
    @endif
</div>
