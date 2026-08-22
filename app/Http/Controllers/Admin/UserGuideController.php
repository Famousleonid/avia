<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\MobileController;
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
        [$workorder, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $mainController->show($workorder, $request)
        );
    }

    public function tdrReport(Request $request, TdrController $tdrController)
    {
        [$workorder, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $tdrController->show($workorder->id)
        );
    }

    public function workorderPictures(Request $request, MainController $mainController)
    {
        [$workorder, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $mainController->photos($workorder)
        );
    }

    public function training(Request $request, TrainingController $trainingController)
    {
        [, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $trainingController->index($request)
        );
    }

    public function technicians(Request $request, UserController $userController)
    {
        [, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $userController->index()
        );
    }

    public function materials(Request $request, MaterialController $materialController)
    {
        [, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $materialController->index()
        );
    }

    public function mobileWorkorders(Request $request, MobileController $mobileController)
    {
        [, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $mobileController->index()
        );
    }

    public function mobileWorkorder(Request $request, MobileController $mobileController)
    {
        [$workorder, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $mobileController->show($workorder)
        );
    }

    public function mobileWorkorderPictures(Request $request, MobileController $mobileController)
    {
        [$workorder, $technician] = $this->trainingContext($request);

        return $this->renderGuideEmbedAsTechnician(
            $request,
            $technician,
            fn () => $mobileController->show($workorder)->with('userGuideMobileFocus', 'photos')
        );
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

    private function trainingContext(Request $request): array
    {
        $user = $request->user();

        abort_unless($this->canUseLiveTrainingWorkorder($user), 403);

        $workorder = Workorder::query()
            ->with('user.role')
            ->where('number', self::TRAINING_WORKORDER_NUMBER)
            ->firstOrFail();

        $technician = $user->roleIs('Technician')
            ? $user
            : $this->trainingTechnician($workorder);

        abort_unless($technician?->roleIs('Technician'), 403);

        return [$workorder, $technician];
    }

    private function renderGuideEmbedAsTechnician(Request $request, User $technician, callable $render)
    {
        $originalAuthUser = Auth::user();
        $originalUserResolver = $request->getUserResolver();

        Auth::setUser($technician);
        $request->setUserResolver(static fn () => $technician);

        try {
            return response(
                $render()
                    ->with('userGuideEmbed', true)
                    ->render()
            );
        } finally {
            Auth::setUser($originalAuthUser);
            $request->setUserResolver($originalUserResolver);
        }
    }
}
