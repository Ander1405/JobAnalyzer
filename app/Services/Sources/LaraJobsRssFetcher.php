<?php

declare(strict_types=1);

namespace App\Services\Sources;

use App\DTOs\JobOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class LaraJobsRssFetcher implements JobSourceInterface
{
    private const FEED_URL = 'https://larajobs.com/feed';

    public function fetch(): Collection
    {
        $response = Http::timeout(30)->retry(3, 2000)->get(self::FEED_URL);
        $response->throw();

        $xml = simplexml_load_string($response->body());

        if ($xml === false) {
            throw new RuntimeException('Unable to parse LaraJobs RSS feed.');
        }

        $items = [];

        foreach ($xml->channel->item as $item) {
            $items[] = $item;
        }

        return collect($items)
            ->map(function (SimpleXMLElement $item) {
                $job = $item->children('job', true);
                $company = trim((string) $job->company);
                $title = trim((string) $item->title);

                if ($company === '') {
                    [$company, $title] = $this->splitTitle($title);
                }

                return new JobOffer(
                    source: 'larajobs',
                    company: $company,
                    title: $title,
                    description: $this->buildDescription($item, $job),
                    url: (string) $item->link,
                    contractType: $this->nullableString($job->job_type),
                );
            });
    }

    private function buildDescription(SimpleXMLElement $item, SimpleXMLElement $job): string
    {
        $body = trim((string) $item->description);

        if ($body !== '') {
            return $body;
        }

        return collect([
            'Location' => $this->nullableString($job->location),
            'Type' => $this->nullableString($job->job_type),
            'Tags' => $this->nullableString($job->tags),
        ])
            ->filter()
            ->map(fn (string $value, string $label) => "{$label}: {$value}")
            ->implode(PHP_EOL);
    }

    private function nullableString(SimpleXMLElement $node): ?string
    {
        $value = trim((string) $node);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitTitle(string $title): array
    {
        $parts = explode(':', $title, 2);

        if (count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return ['', trim($title)];
    }
}
