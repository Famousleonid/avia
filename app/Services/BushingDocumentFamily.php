<?php
namespace App\Services;

use App\Models\Component;
use App\Support\BushingPrlGrouping;
use Illuminate\Support\Collection;

class BushingDocumentFamily
{
    private array $manualGroups = [];

    public function members(Component $component): Collection
    {
        $manualId = (int) $component->manual_id;
        if (!isset($this->manualGroups[$manualId])) {
            $parts = Component::where('manual_id', $manualId)->bushingCandidates()->with('media')->orderBy('id')->get();
            $this->manualGroups[$manualId] = BushingPrlGrouping::groups($parts);
        }
        return $this->manualGroups[$manualId]->first(fn ($group) => $group->contains('id', $component->id)) ?? collect([$component]);
    }

    public function owner(Component $component): Component
    {
        $members = $this->members($component);
        return BushingPrlGrouping::originalComponent($members) ?? $members->sortBy('id')->first();
    }

    public function document(Component $component)
    {
        return $this->members($component)->flatMap(fn ($part) => $part->getMedia('bushing_documents'))->sortByDesc('id')->first();
    }
}
