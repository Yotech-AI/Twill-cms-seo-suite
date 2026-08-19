<?php

use A17\Twill\Models\Media;

it('never returns 200 to a guest, redirecting to the twill login instead', function () {
    $this->get(twillSeoUrl('media'))->assertRedirect(route('twill.login.form'));
});

it('returns a created twill media with its id, name and thumbnail url', function () {
    $media = Media::query()->create([
        'uuid' => 'a-real-uuid.jpg',
        'filename' => 'mountain-view.jpg',
        'width' => 800,
        'height' => 600,
    ]);

    $this->actingAsTwillAdmin();

    $response = $this->getJson(twillSeoUrl('media'))->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($media->id);

    $row = collect($response->json('data'))->firstWhere('id', $media->id);
    expect($row['name'])->toBe('mountain-view.jpg')
        ->and($row['thumbnail'])->toBeString()
        ->and($row['thumbnail'])->toContain($media->uuid);
});

it('filters by the q query parameter against the filename', function () {
    Media::query()->create(['uuid' => 'one.jpg', 'filename' => 'mountain-view.jpg', 'width' => 10, 'height' => 10]);
    $river = Media::query()->create(['uuid' => 'two.jpg', 'filename' => 'river-bank.jpg', 'width' => 10, 'height' => 10]);

    $this->actingAsTwillAdmin();

    $response = $this->getJson(twillSeoUrl('media').'?q=river')->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toBe([$river->id]);
});

it('paginates results via the page query parameter', function () {
    foreach (range(1, 3) as $i) {
        Media::query()->create(['uuid' => "media-{$i}.jpg", 'filename' => "photo-{$i}.jpg", 'width' => 10, 'height' => 10]);
    }

    $this->actingAsTwillAdmin();

    $response = $this->getJson(twillSeoUrl('media').'?page=1')->assertOk();

    expect($response->json('meta.page'))->toBe(1)
        ->and($response->json('meta.total'))->toBe(3);
});
