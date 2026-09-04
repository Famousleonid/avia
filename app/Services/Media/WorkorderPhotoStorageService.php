<?php

namespace App\Services\Media;

use App\Models\Workorder;
use App\Models\WorkorderMediaSequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WorkorderPhotoStorageService
{
    /**
     * Store a WO photo with a sequence that is never reused for this archive folder.
     *
     * @param  array<string, mixed>  $customProperties
     */
    public function store(
        Workorder $workorder,
        UploadedFile $photo,
        string $collection,
        array $customProperties = []
    ): Media {
        return DB::transaction(function () use ($workorder, $photo, $collection, $customProperties): Media {
            $lockedWorkorder = $this->lockWorkorder($workorder);
            $filename = $this->nextFilenameLocked(
                $lockedWorkorder,
                $collection,
                $photo->getClientOriginalExtension()
            );

            $adder = $lockedWorkorder
                ->addMedia($photo)
                ->usingFileName($filename);

            if ($customProperties !== []) {
                $adder->withCustomProperties($customProperties);
            }

            return $adder->toMediaCollection($collection);
        }, 3);
    }

    public function move(Workorder $workorder, Media $media, string $collection): Media
    {
        return DB::transaction(function () use ($workorder, $media, $collection): Media {
            $lockedWorkorder = $this->lockWorkorder($workorder);
            $extension = pathinfo((string) $media->file_name, PATHINFO_EXTENSION);
            $filename = $this->nextFilenameLocked($lockedWorkorder, $collection, $extension);

            return $media->move($lockedWorkorder, $collection, '', $filename);
        }, 3);
    }

    private function lockWorkorder(Workorder $workorder): Workorder
    {
        return Workorder::withDrafts()
            ->whereKey($workorder->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function nextFilenameLocked(Workorder $workorder, string $collection, ?string $extension): string
    {
        $sequence = WorkorderMediaSequence::query()
            ->where('workorder_id', $workorder->id)
            ->where('collection_name', $collection)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            $sequence = WorkorderMediaSequence::query()->create([
                'workorder_id' => $workorder->id,
                'collection_name' => $collection,
                'last_sequence' => $this->existingSequenceFloor($workorder, $collection),
            ]);
        }

        $sequence->last_sequence = (int) $sequence->last_sequence + 1;
        $sequence->save();

        $workorderNumber = $this->safeWorkorderNumber($workorder);
        $safeExtension = $this->safeExtension($extension);
        $ordinal = str_pad((string) $sequence->last_sequence, 3, '0', STR_PAD_LEFT);

        return 'wo_'.$workorderNumber.'_'.now()->format('ymd').'_'.$ordinal.'.'.$safeExtension;
    }

    private function existingSequenceFloor(Workorder $workorder, string $collection): int
    {
        $fileNames = Media::query()
            ->where('model_type', $workorder->getMorphClass())
            ->where('model_id', $workorder->id)
            ->where('collection_name', $collection)
            ->pluck('file_name');

        $pattern = '/^wo_'.preg_quote($this->safeWorkorderNumber($workorder), '/').'_\d{6}_(\d+)\.[A-Za-z0-9]+$/i';
        $largestNamedSequence = $fileNames->reduce(function (int $largest, $fileName) use ($pattern): int {
            return preg_match($pattern, (string) $fileName, $matches) === 1
                ? max($largest, (int) $matches[1])
                : $largest;
        }, 0);

        // Existing legacy names had no folder sequence. Starting after the
        // current file count keeps the first new number intuitive on upgrade.
        return max($fileNames->count(), $largestNamedSequence);
    }

    private function safeWorkorderNumber(Workorder $workorder): string
    {
        $number = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim((string) $workorder->number));

        return trim((string) $number, '-') ?: (string) $workorder->id;
    }

    private function safeExtension(?string $extension): string
    {
        $extension = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $extension));

        return $extension !== '' ? $extension : 'jpg';
    }
}
