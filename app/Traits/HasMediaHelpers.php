<?php

namespace App\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMediaHelpers
{
    /**
     * Получает первое медиа из коллекции.
     * Кэширует результат, чтобы избежать повторных запросов.
     *
     * @param string $collection
     * @return Media|null
     */
    public function getFirstMediaFromCollection(string $collection): ?Media
    {
        // Используем встроенную ленивую загрузку отношений Laravel
        // Если медиа уже были загружены через ->with('media'), нового запроса не будет.
        return $this->media->where('collection_name', $collection)->first();
    }

    /**
     * Генерирует URL для показа изображения.
     *
     * @param Media|null $media
     * @param string $conversionName ('thumb', '' для big, и т.д.)
     * @param string $collection
     * @return string
     */
    public function generateMediaUrl(?Media $media, string $conversionName, string $collection): string
    {
        if (!$media) {
            return asset('img/noimage.png');
        }

        // Определяем имя маршрута в зависимости от типа конверсии
        $routeName = ($conversionName === 'thumb') ? 'image.show.thumb' : 'image.show.big';

        return route($routeName, [
            'modelType' => $this->mediaUrlName, // Используем свойство из модели
            'modelId'   => $this->id,
            'mediaId'   => $media->id,
            'mediaName' => $collection,
        ]);
    }

    /**
     * Получает URL превью для первого изображения в коллекции.
     *
     * @param string $collection
     * @return string
     */
    public function getFirstMediaThumbnailUrl(string $collection): string
    {
        $media = $this->getFirstMediaFromCollection($collection);
        return $this->generateMediaUrl($media, 'thumb', $collection);
    }

    /**
     * Получает URL большого изображения для первого изображения в коллекции.
     *
     * @param string $collection
     * @return string
     */
    public function getFirstMediaBigUrl(string $collection): string
    {
        $media = $this->getFirstMediaFromCollection($collection);
        return $this->generateMediaUrl($media, '', $collection); // Пустая строка для оригинала
    }

    /**
     * Получает массив URL-ов (превью и большое) для всех медиа в коллекции.
     * Идеально для галерей, которые передаются в JS.
     *
     * @param string $collection
     * @return array
     */
    public function getAllMediaUrls(string $collection): array
    {
        return $this->getMedia($collection)->map(function (Media $media) use ($collection) {
            return [
                'id'    => $media->id,
                'thumb' => $this->generateMediaUrl($media, 'thumb', $collection),
                'big'   => $this->generateMediaUrl($media, '', $collection),
            ];
        })->all();
    }
}
