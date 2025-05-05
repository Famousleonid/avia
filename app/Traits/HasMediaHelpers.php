<?php

namespace App\Traits;

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
}
