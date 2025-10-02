<?php

namespace Database\Seeders;

use App\Models\ResourceItem;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ResourceSeeder extends Seeder
{
    public int $minPerSection = 1;
    public int $maxPerSection = 4;

    public function run(): void
    {
        $sections = Section::query()->withCount('resources')->get();
        foreach ($sections as $section) {
            if ($section->resources_count > 0) {
                continue;
            }
            $count = random_int($this->minPerSection, $this->maxPerSection);
            for ($i = 1; $i <= $count; $i++) {
                $isPdf = random_int(0, 1) === 1; // random content type
                ResourceItem::create([
                    'section_id' => $section->id,
                    'content_type' => $isPdf ? 'pdf' : 'yt',
                    'url' => $isPdf ? $this->fakePdfUrl($section->id, $i) : $this->fakeYoutubeUrl(),
                    'filename' => $isPdf ? $this->fakePdfFilename($section->id, $i) : null,
                ]);
            }
        }
    }

    protected function fakePdfFilename(int $sectionId, int $index): string
    {
        return 'section_' . $sectionId . '_res_' . $index . '.pdf';
    }

    protected function fakePdfUrl(int $sectionId, int $index): string
    {
        return 'https://example.com/storage/pdfs/' . $this->fakePdfFilename($sectionId, $index);
    }

    protected function fakeYoutubeUrl(): string
    {
        // random short code
        return 'https://youtu.be/' . Str::random(8);
    }
}
