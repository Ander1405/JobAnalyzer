<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\TrackedJobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\TailorProfile;
use App\Models\Job;
use App\Models\Profile;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProfileTailorController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:job_offers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string'],
        ]);

        $job = Job::query()->whereKey($validated['job_id'])->firstOrFail();
        $profile = Profile::active();

        if ($profile === null) {
            return response()->json(['message' => 'No hay un perfil activo para adaptar.'], 422);
        }

        $requestId = (string) Str::uuid();

        Cache::put(TailorProfile::cacheKey($requestId), ['status' => 'processing'], now()->addMinutes(15));

        TailorProfile::dispatch($requestId, $profile->id, $job->id, $validated['items'], $request->user()->id);

        return response()->json(['request_id' => $requestId, 'status' => 'processing'], 202);
    }

    /**
     * Polled by the frontend while a queue worker (run manually so it can read
     * the macOS Keychain for claude_cli) processes the TailorProfile job.
     */
    public function status(string $requestId): JsonResponse
    {
        $payload = Cache::get(TailorProfile::cacheKey($requestId));

        if ($payload === null) {
            return response()->json(['message' => 'Solicitud de tailoring no encontrada o expirada.'], 404);
        }

        return response()->json($payload);
    }

    public function confirm(Request $request, ProfileVariantService $service): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:job_offers,id'],
            'overrides' => ['required', 'array'],
            'overrides.headline' => ['required', 'string'],
            'overrides.summary' => ['required', 'string'],
            'overrides.experience' => ['present', 'array'],
            'overrides.experience.*' => ['string'],
            'overrides.skills' => ['present', 'array'],
            'overrides.skills.*' => ['string'],
        ]);

        $job = Job::query()->whereKey($validated['job_id'])->firstOrFail();
        $slug = $service->uniqueSlug(Str::slug("{$job->company}-{$job->title}"));

        try {
            $profile = $service->createVariant($slug, "{$job->company} · {$job->title}", $validated['overrides'], $job->id);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        DB::transaction(function () use ($job): void {
            $job = Job::query()->whereKey($job->id)->lockForUpdate()->first();

            // Confirmar el CV tailored es la única señal real y persistida de que
            // los ítems del tailoring quedaron aplicados a esta vacante: no hay un
            // checklist de ítems individuales guardado en base de datos.
            if (in_array($job->application_status, [ApplicationStatus::New, ApplicationStatus::CvAdapted], true)) {
                $job->update(['application_status' => ApplicationStatus::Applied]);
            }

            $trackedJob = $job->trackedJob;

            if ($trackedJob !== null && $trackedJob->status === TrackedJobStatus::SinAplicar) {
                $trackedJob->update(['status' => TrackedJobStatus::Aplique]);
            }
        });

        return response()->json($profile, 201);
    }
}
