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

        $this->addLetterhead($phpWord, $section, $incident);

        // ── Client name + Title ─────────────────────────────────────────
        $section->addText($this->xmlSafe($incident->client?->name ?? '—'), ['bold' => true, 'size' => 14, 'color' => '085041']);
        $section->addText('INCIDENT ANALYSIS REPORT', ['bold' => true, 'size' => 14, 'color' => '663701']);
        $section->addTextBreak(1);

        // ── Header block ─────────────────────────────────────────────────
        $this->addLabeledLine($section, 'Date of Incident:', $incident->incident_date->format('d F Y'));
        $this->addLabeledLine($section, 'Time of Incident:', $incident->incident_date->format('H:i'));
        $this->addLabeledLine($section, 'Location:', $location ?: '—');
        $this->addLabeledLine($section, 'Subject Vehicle:', $incident->vehicle_label ?? $tracker?->label ?? '—');
        $this->addLabeledLine($section, 'Report Date:', now()->format('d F Y'));
        $section->addTextBreak(1);

        // ── Sections 1-4 ─────────────────────────────────────────────────
        foreach ($sections as $sec) {
            $section->addText($this->xmlSafe($sec['heading']), ['bold' => true, 'size' => 11]);

            if ($sec['type'] === 'table' && !empty($sec['rows'])) {
                $this->addChronologyTable($section, $sec['rows']);
            } elseif ($sec['type'] === 'subsections') {
                foreach ($sec['subsections'] as $sub) {
                    $section->addText($this->xmlSafe($sub['heading']), ['bold' => true, 'italic' => true, 'size' => 10]);
                    $section->addText($this->xmlSafe($sub['body']));
                    $section->addTextBreak(1);
                }
            } elseif ($sec['type'] === 'numbered_list') {
                foreach ($sec['items'] as $i => $item) {
                    $section->addText(($i + 1) . '. ' . $this->xmlSafe($item));
                }
            } else {
                $section->addText($this->xmlSafe($sec['body']));
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
            'Prepared By: ' . $this->xmlSafe($incident->prepared_by_name ?: '—') .
            ' | ' . $this->xmlSafe($incident->prepared_by_title ?: '—')
        );
        $section->addText(
            'Reviewed By: ' . $this->xmlSafe($incident->reviewed_by_name ?: '—') .
            ' | ' . $this->xmlSafe($incident->reviewed_by_title ?: '—')
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
     * Adds the Bantu Track header (logo image, far left + plain-text
     * contact details, far right, right-aligned + rule) and footer (brand
     * graphic). No watermark. Assets live in
     * Modules/AfisIncidents/resources/branding/ and are committed to git —
     * not user-generated, so they don't belong in storage/.
     */
    private function addLetterhead(PhpWord $phpWord, $section, AfisIncident $incident): void
    {
        $brandingPath = base_path('Modules/AfisIncidents/resources/branding');
        $logoPath     = $brandingPath . '/logo-header.png';
        $footerPath   = $brandingPath . '/footer.png';

        $header = $section->addHeader();

        $table = $header->addTable(['cellMargin' => 0, 'alignment' => Jc::START]);
        $table->addRow();

        $logoCell = $table->addCell(5500, ['valign' => 'center']);
        if (file_exists($logoPath)) {
            $logoCell->addImage($logoPath, ['width' => 210, 'height' => 71.7, 'alignment' => Jc::START]);
        }

        $contactCell = $table->addCell(4000, ['valign' => 'center']);
        $contactStyle = ['size' => 8, 'color' => '444444'];
        $rightAlign   = [
            'alignment'   => Jc::END,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1,
        ];
        $contactCell->addText('+263 242 702 509', $contactStyle, $rightAlign);
        $contactCell->addText('+263 778 002 318', $contactStyle, $rightAlign);
        $contactCell->addText('operations@bantutrack.co.zw', $contactStyle, $rightAlign);
        $contactCell->addText('10 Cherry Tree, Avonlea, Harare', $contactStyle, $rightAlign);

        $header->addText('', [], [
            'borderBottomSize'  => 6,
            'borderBottomColor' => '663701',
            'spaceAfter'        => 0,
        ]);

        $footer = $section->addFooter();
        if (file_exists($footerPath)) {
            $footer->addImage($footerPath, [
                'width'     => 380,
                'height'    => 12.5,
                'alignment' => Jc::CENTER,
            ]);
        }
    }

    private function addContactLine($cell, string $iconPath, string $text, array $textStyle, array $paraStyle, array $iconStyle): void
    {
        $run = $cell->addTextRun($paraStyle);

        if (file_exists($iconPath)) {
            $run->addImage($iconPath, $iconStyle);
            $run->addText('  ', $textStyle);
        }

        $run->addText($this->xmlSafe($text), $textStyle);
    }

    /**
     * PHPWord's addText() does not reliably auto-escape XML special
     * characters ("&", "<", ">") in ANY context in this installation. A raw
     * "&" anywhere in the document (client name, AI-generated prose,
     * user-entered names) produces invalid XML that Word refuses to open
     * outright. Every dynamic string passed to addText() in this file goes
     * through this first.
     */
    private function xmlSafe(string $text): string
    {
        return str_replace(
            ['&', '<', '>'],
            ['&amp;', '&lt;', '&gt;'],
            $text
        );
    }

    private function addLabeledLine($section, string $label, string $value): void
    {
        $textRun = $section->addTextRun();
        $textRun->addText($label . ' ', ['bold' => true]);
        $textRun->addText($this->xmlSafe($value));
    }

    private function extractLocation(string $response): ?string
    {
        if (preg_match('/^LOCATION:\s*(.+)$/mi', $response, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Parses the AI response markdown into the 4 fixed sections requested
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
                continue;
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
            if (preg_match('/^\|[\s\-:|]+\|$/', $line)) continue;

            $rows[] = array_map('trim', explode('|', trim($line, '|')));
        }

        if (!empty($rows)) {
            array_shift($rows);
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
                $table->addCell(3000)->addText($this->xmlSafe($row[$i] ?? ''));
            }
        }
    }
}