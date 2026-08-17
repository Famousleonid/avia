<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class UserGuideController extends Controller
{
    private const TRAINING_WORKORDER_NUMBER = 100000;

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($this->canOpenGuide($user), 403);

        return view('admin.user-guide', [
            'canUseLiveTrainingWorkorder' => $this->canUseLiveTrainingWorkorder($user),
        ]);
    }

    public function workorderMain(Request $request, MainController $mainController)
    {
        $user = $request->user();

        abort_unless($this->canUseLiveTrainingWorkorder($user), 403);

        $workorder = Workorder::query()
            ->with('user.role')
            ->where('number', self::TRAINING_WORKORDER_NUMBER)
            ->firstOrFail();

        $effectiveUser = $user->roleIs('Technician')
            ? $user
            : $this->trainingTechnician($workorder);

        abort_unless($effectiveUser?->roleIs('Technician'), 403);

        return $this->renderMainAsTechnician($mainController, $workorder, $request, $effectiveUser);
    }

    private function canOpenGuide(?User $user): bool
    {
        return $user !== null;
    }

    private function canUseLiveTrainingWorkorder(?User $user): bool
    {
        return $user !== null && Workorder::query()
            ->where('number', self::TRAINING_WORKORDER_NUMBER)
            ->exists();
    }

    private function trainingTechnician(Workorder $workorder): ?User
    {
        if ($workorder->user?->roleIs('Technician')) {
            return $workorder->user;
        }

        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Technician'))
            ->first();
    }

    private function renderMainAsTechnician(
        MainController $mainController,
        Workorder $workorder,
        Request $request,
        User $technician
    ) {
        $originalAuthUser = Auth::user();
        $originalUserResolver = $request->getUserResolver();

        Auth::setUser($technician);
        $request->setUserResolver(static fn () => $technician);

        try {
            return response(
                $mainController->show($workorder, $request)
                    ->with('userGuideEmbed', true)
                    ->render()
            );
        } finally {
            Auth::setUser($originalAuthUser);
            $request->setUserResolver($originalUserResolver);
        }
    }
}
