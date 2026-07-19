<?php

declare(strict_types=1);

namespace App\Services\Notion;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Support\Facades\Http;

class NotionPublisher
{
    private const ENDPOINT = 'https://api.notion.com/v1/pages';

    private const NOTION_VERSION = '2022-06-28';

    private const MAX_CHUNK_LENGTH = 1900;

    public function publish(Job $job): void
    {
        $response = Http::withToken((string) config('jobhunter.notion.token'))
            ->withHeaders(['Notion-Version' => self::NOTION_VERSION])
            ->post(self::ENDPOINT, [
                'parent' => ['database_id' => config('jobhunter.notion.database_id')],
                'properties' => $this->buildProperties($job),
                'children' => $this->buildChildren($job),
            ]);

        if ($response->failed()) {
            $job->update([
                'status' => JobStatus::Failed,
                'error_message' => $response->body(),
            ]);

            return;
        }

        $job->update([
            'notion_page_id' => $response->json('id'),
            'status' => JobStatus::Published,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProperties(Job $job): array
    {
        $analysis = $job->ai_analysis ?? [];

        return [
            'Cargo' => [
                'title' => [['text' => ['content' => $job->title]]],
            ],
            'Empresa' => [
                'rich_text' => [['text' => ['content' => $job->company]]],
            ],
            'Fuente' => [
                'select' => ['name' => $job->source],
            ],
            'URL' => [
                'url' => $job->url,
            ],
            'Match %' => [
                'number' => $analysis['match_score'] ?? null,
            ],
            'Tipo de contrato' => [
                'select' => ['name' => $analysis['tipo_contrato'] ?? ($job->contract_type ?? 'No especificado')],
            ],
            'Salario' => [
                'rich_text' => [['text' => ['content' => $analysis['salario_normalizado'] ?? ($job->salary_raw ?? 'No especificado')]]],
            ],
            'Moneda' => [
                'select' => ['name' => $analysis['moneda'] ?? 'No especificado'],
            ],
            'Idioma' => [
                'select' => ['name' => $analysis['idioma'] ?? 'No especificado'],
            ],
            'Estado' => [
                'select' => ['name' => 'Nueva'],
            ],
            'Fecha detectada' => [
                'date' => ['start' => $job->created_at?->format('Y-m-d')],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildChildren(Job $job): array
    {
        $analysis = $job->ai_analysis ?? [];

        $blocks = [
            [
                'object' => 'block',
                'type' => 'callout',
                'callout' => [
                    'icon' => ['type' => 'emoji', 'emoji' => '🎯'],
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => sprintf(
                            '%s%% match — %s',
                            $analysis['match_score'] ?? 'N/A',
                            $analysis['diagnostico'] ?? 'Sin diagnóstico.',
                        )],
                    ]],
                ],
            ],
            $this->heading('📄 Descripción'),
        ];

        foreach ($this->chunkText($job->description) as $chunk) {
            $blocks[] = $this->paragraph($chunk);
        }

        $blocks[] = $this->heading('💡 Tips para postular');

        foreach ($analysis['tips_postulacion'] ?? [] as $tip) {
            $blocks[] = $this->bulletedListItem($tip);
        }

        $blocks[] = $this->heading('✂️ Tailoring del CV');

        foreach ($analysis['tailoring_cv'] ?? [] as $adjustment) {
            $blocks[] = $this->toDo($adjustment);
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function chunkText(string $text): array
    {
        if ($text === '') {
            return [''];
        }

        $chunks = [];
        $length = mb_strlen($text);

        for ($offset = 0; $offset < $length; $offset += self::MAX_CHUNK_LENGTH) {
            $chunks[] = mb_substr($text, $offset, self::MAX_CHUNK_LENGTH);
        }

        return $chunks;
    }

    /**
     * @return array<string, mixed>
     */
    private function heading(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [['type' => 'text', 'text' => ['content' => $text]]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraph(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [['type' => 'text', 'text' => ['content' => $text]]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bulletedListItem(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [['type' => 'text', 'text' => ['content' => $text]]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toDo(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'to_do',
            'to_do' => [
                'rich_text' => [['type' => 'text', 'text' => ['content' => $text]]],
                'checked' => false,
            ],
        ];
    }
}
