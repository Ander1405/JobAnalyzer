<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobCvPdfController extends Controller
{
    public function show(Job $job, Request $request): Response|StreamedResponse
    {
        $profile = $job->latestCvVariant;

        if ($profile === null) {
            abort(404, 'Esta vacante todavía no tiene un CV adaptado confirmado.');
        }

        // raw_md starts with "# {headline}" — the Blade view renders that headline
        // itself alongside the job subtitle, so it is stripped to avoid a duplicate H1.
        $body = Str::of($profile->raw_md)->after("\n")->trim()->toString();
        $contentHtml = (new CommonMarkConverter)->convert($body)->getContent();

        $pdf = Pdf::loadView('pdf.cv', [
            'profile' => $profile,
            'job' => $job,
            'contentHtml' => $contentHtml,
        ])->setPaper('letter');

        $filename = Str::slug("cv-{$job->company}-{$job->title}").'.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
