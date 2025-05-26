<?php

namespace App\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMediaHelpers
{
    public function getThumbnailUrl($collection)
    {
        $media = $this->getMedia($collection)->first();

        return $media
            ? route('image.show.thumb', [
                'mediaId' => $media->id,
                'modelId' => $this->id,
                'mediaName' => $collection
            ])
            : asset('img/noimage.png');
    }

    public function getBigImageUrl($collection)
    {
        $media = $this->getMedia($collection)->first();

        return $media
            ? route('image.show.big', [
                'mediaId' => $media->id,
                'modelId' => $this->id,
                'mediaName' => $collection])
            : asset('img/noimage.png');
    }

    public function getAllMediaUrls($collection)
    {
        return $this->getMedia($collection)->map(function (Media $media) use ($collection) {
            return [
                'thumb' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $this->id,
                    'mediaName' => $collection
                ]),
                'big' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $this->id,
                    'mediaName' => $collection
                ])
            ];
        })->toArray();
    }

}
