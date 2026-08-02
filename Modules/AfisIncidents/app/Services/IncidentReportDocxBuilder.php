<?php

namespace Modules\AfisIncidents\Services;

use Illuminate\Support\Facades\Storage;
use Modules\AfisIncidents\Models\AfisIncident;
use Modules\AfisPipeline\Models\AfisTracker;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class IncidentReportDocxBuilder
{
    public function build(AfisIncident $incident, string $aiResponse, ?AfisTracker $tracker = null): string
    {
        $location = $this->extractLocation($aiResponse);
        $sections = $this->parseSections($aiResponse);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'paperSize'    => 'A4', // explicit, since watermark centering math assumes A4 (595.3 x 841.9 pt)
            'marginTop'    => 1500, // extra top margin so body text clears the header logo/line
            'marginBottom' => 1200,
            'marginLeft'   => 1000,
            'marginRight'  => 1000,
        ]);

        $this->addLetterhead($phpWord, $section);

        // ── Title ────────────────────────────────────────────────────────
        $section->addText('INCIDENT ANALYSIS REPORT', ['bold' => true, 'size' => 14, 'color' => '663701']);        $section->addTextBreak(1);

        // ── Header block ─────────────────────────────────────────────────
        $this->addLabeledLine($section, 'Date of Incident:', $incident->incident_date->format('d F Y'));
        $this->addLabeledLine($section, 'Time of Incident:', $incident->incident_date->format('H:i'));
        $this->addLabeledLine($section, 'Location:', $location ?: '—');
        $this->addLabeledLine($section, 'Subject Vehicle:', $incident->vehicle_label ?? $tracker?->label ?? '—');
        $this->addLabeledLine($section, 'Report Date:', now()->format('d F Y'));
        $section->addTextBreak(1);

        // ── Sections 1-5 ─────────────────────────────────────────────────
        foreach ($sections as $sec) {
            $section->addText($sec['heading'], ['bold' => true, 'size' => 11]);

            if ($sec['type'] === 'table' && !empty($sec['rows'])) {
                $this->addChronologyTable($section, $sec['rows']);
            } elseif ($sec['type'] === 'subsections') {
                foreach ($sec['subsections'] as $sub) {
                    $section->addText($sub['heading'], ['bold' => true, 'italic' => true, 'size' => 10]);
                    $section->addText($sub['body']);
                    $section->addTextBreak(1);
                }
            } elseif ($sec['type'] === 'numbered_list') {
                foreach ($sec['items'] as $i => $item) {
                    $section->addText(($i + 1) . '. ' . $item);
                }
            } else {
                $section->addText($sec['body']);
            }

            $section->addTextBreak(1);
        }

         // ── Recommendations (fixed policy text — never AI-generated) ─────
        $section->addText('5. RECOMMENDATIONS', ['bold' => true, 'size' => 11]);
        $section->addText('1. Retraining: All drivers should undergo retraining on speed limit compliance and defensive driving techniques.');
        $section->addText('2. Policy Review: Review and enforce stricter penalties for speeding violations and unsanctioned trips.');
        $section->addTextBreak(1);

        // ── Signature block ─────────────────────────────────────────────
        $section->addText(str_repeat('_', 50));
        $section->addText(
            'Prepared By: ' . ($incident->prepared_by_name ?: '—') .
            ' | ' . ($incident->prepared_by_title ?: '—')
        );
        $section->addText(
            'Reviewed By: ' . ($incident->reviewed_by_name ?: '—') .
            ' | ' . ($incident->reviewed_by_title ?: '—')
        );

        // ── Save to storage/app/incidents ────────────────────────────────
        Storage::disk('local')->makeDirectory('incidents');
        $filename     = "incident_{$incident->id}_" . now()->format('Ymd_His') . '.docx';
        $relativePath = "incidents/{$filename}";
        $fullPath     = Storage::disk('local')->path($relativePath);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return $relativePath;
    }

    /**
     * Adds the Bantu Track header (logo + contact block + rule), footer
     * (brand graphic), and a faded logo watermark centered on every page.
     * Assets live in Modules/AfisIncidents/resources/branding/ and are
     * committed to git — not user-generated, so they don't belong in storage/.
     */
    private function addLetterhead(PhpWord $phpWord, $section): void
    {
        $brandingPath = base_path('Modules/AfisIncidents/resources/branding');
        $logoPath      = $brandingPath . '/logo-header.png';
        $watermarkPath = $brandingPath . '/watermark-faded.png';
        $footerPath    = $brandingPath . '/footer.png';

        // ── Header: logo (left) + contact block (right) + rule ──────────
        $header = $section->addHeader();

        $table = $header->addTable(['cellMargin' => 0]);
        $table->addRow();

        $logoCell = $table->addCell(5500, ['valign' => 'center']);
        if (file_exists($logoPath)) {
            // Source is 4098x1398 (~2.93:1) — scaled to keep that ratio.
            $logoCell->addImage($logoPath, ['width' => 210, 'height' => 71.7]);
        }

       $contactCell = $table->addCell(4000, ['valign' => 'center']);
        $contactStyle = ['size' => 12, 'color' => '444444'];
        $rightAlign    = [
            'alignment'   => Jc::START,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1,
        ];
        $iconStyle    = ['width' => 16, 'height' => 16];

        $this->addContactLine($contactCell, $brandingPath . '/icon-phone.png', '+263242702509 | +263778002318', $contactStyle, $rightAlign, $iconStyle);
        $this->addContactLine($contactCell, $brandingPath . '/icon-envelope.png', 'operations@bantutrack.co.zw', $contactStyle, $rightAlign, $iconStyle);
        $this->addContactLine($contactCell, $brandingPath . '/icon-pin.png', '10 Cherry Tree, Avonlea, Harare', $contactStyle, $rightAlign, $iconStyle);

        // Horizontal rule under the header block
        $header->addText('', [], [
            'borderBottomSize'  => 6,
            'borderBottomColor' => '663701',
            'spaceAfter'        => 0,
        ]);

        // ── Watermark: faded logo, centered on every page, behind text ──
        // Uses position constants (posHorizontal/posVertical => 'center',
        // relative to the page) instead of manually computed marginLeft/
        // marginTop offsets — letting Word do the centering math itself
        // rather than assuming a specific paper size/unit interpretation,
        // which is what caused the previous version to render off-center.
        if (file_exists($watermarkPath)) {
            $header->addWatermark($watermarkPath, [
                'width'            => 260,
                'height'           => 256,
                'positioning'      => 'absolute',
                'posHorizontalRel' => 'page',
                'posHorizontal'    => 'center',
                'posVerticalRel'   => 'page',
                'posVertical'      => 'center',
            ]);
        }

        // ── Footer: brand graphic, centered ──────────────────────────────
        $footer = $section->addFooter();
        if (file_exists($footerPath)) {
            // Source is 1001x33 (~30:1) — scaled to keep that ratio.
            $footer->addImage($footerPath, [
                'width'       => 380,
                'height'      => 12.5,
                'alignment'   => Jc::CENTER,
            ]);
        }
    }

    private function addContactLine($cell, string $iconPath, string $text, array $textStyle, array $paraStyle, array $iconStyle): void
    {
        $run = $cell->addTextRun($paraStyle);

        if (file_exists($iconPath)) {
            $run->addImage($iconPath, $iconStyle);
            $run->addText('  ', $textStyle); // small gap between icon and text
        }

        $run->addText($text, $textStyle);
    }

    private function addLabeledLine($section, string $label, string $value): void
    {
        $textRun = $section->addTextRun();
        $textRun->addText($label . ' ', ['bold' => true]);
        $textRun->addText($value);
    }

    private function extractLocation(string $response): ?string
    {
        if (preg_match('/^LOCATION:\s*(.+)$/mi', $response, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Parses the AI response markdown into the 5 fixed sections requested
     * in PromptBuilder::incidentAnalysis(). If the model deviates from the
     * requested format, sections fall back to plain text rendering rather
     * than throwing — a slightly-off docx is better than a failed job.
     */
    private function parseSections(string $response): array
    {
        $response = preg_replace('/^LOCATION:.*$/mi', '', $response);

        $parts    = preg_split('/^##\s+/m', $response);
        $sections = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            $lines   = explode("\n", $part);
            $heading = trim(array_shift($lines));
            $body    = trim(implode("\n", $lines));

            if (stripos($heading, 'RECOMMENDATIONS') !== false) {
                continue; // Recommendations are fixed policy text, added separately below — ignore anything the AI produced here.
            } elseif (stripos($heading, 'CHRONOLOGY') !== false) {
                $sections[] = ['heading' => $heading, 'type' => 'table', 'rows' => $this->parseMarkdownTable($body)];
            } elseif (stripos($heading, 'ANALYSIS AND KEY FINDINGS') !== false) {
                $sections[] = ['heading' => $heading, 'type' => 'subsections', 'subsections' => $this->parseSubsections($body)];
            } else {
                $sections[] = ['heading' => $heading, 'type' => 'text', 'body' => $body];
            }
        }

        return $sections;
    }

    private function parseMarkdownTable(string $body): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $body)));
        $rows  = [];

        foreach ($lines as $line) {
            if (!str_starts_with($line, '|')) continue;
            if (preg_match('/^\|[\s\-:|]+\|$/', $line)) continue; // separator row

            $rows[] = array_map('trim', explode('|', trim($line, '|')));
        }

        if (!empty($rows)) {
            array_shift($rows); // drop the model's own header row — we render ours
        }

        return $rows;
    }

    private function parseSubsections(string $body): array
    {
        $parts = preg_split('/^###\s+/m', $body);
        $subs  = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            $lines   = explode("\n", $part);
            $heading = trim(array_shift($lines));
            $subs[]  = ['heading' => $heading, 'body' => trim(implode("\n", $lines))];
        }

        return $subs;
    }

    private function parseNumberedList(string $body): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $body)));
        $items = [];

        foreach ($lines as $line) {
            $clean = preg_replace('/^\d+\.\s*/', '', $line);
            if ($clean !== '') {
                $items[] = $clean;
            }
        }

        return $items;
    }

    private function addChronologyTable($section, array $rows): void
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);

        $table->addRow(null, ['tblHeader' => true]);
        foreach (['Time', 'Event', 'Details'] as $header) {
            $table->addCell(3000)->addText($header, ['bold' => true]);
        }

        foreach ($rows as $row) {
            $table->addRow();
            foreach ([0, 1, 2] as $i) {
                $table->addCell(3000)->addText($row[$i] ?? '');
            }
        }
    }
}