<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\XlsxReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * One-off converter: customer's Excel classifier -> committed JSON data file
 * consumed by CatalogClassifierSeeder. Re-run whenever the customer ships an
 * updated workbook.
 */
class GenerateCatalogClassifierJson extends Command
{
    protected $signature = 'catalog:generate-classifier-json {xlsx : Path to the classifier .xlsx}';

    protected $description = 'Parse the catalog classifier xlsx into database/seeders/data/catalog_classifier.json';

    private const FIELD_TYPES = ['dropdown', 'multiselect', 'boolean', 'numeric', 'decimal', 'range', 'text', 'textarea'];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(XlsxReader $reader): int
    {
        $path = (string) $this->argument('xlsx');
        $sheets = $reader->read($path);

        foreach (['1_Дерево', '2_Характеристики', '4_Справочники', '6_Поисковые_синонимы'] as $required) {
            if (! isset($sheets[$required])) {
                $this->error("Sheet '{$required}' missing from workbook.");

                return self::FAILURE;
            }
        }

        $sections = $this->parseTree($sheets['1_Дерево']);
        $this->parseAttributes($sheets['2_Характеристики'], $sections);
        $applications = $this->collectApplications($sheets['4_Справочники'], $sections);
        $this->parseSynonyms($sheets['6_Поисковые_синонимы'], $sections);

        // Normalize nested maps to lists for a stable, diff-friendly JSON file.
        $sectionList = [];
        ksort($sections);
        foreach ($sections as $section) {
            $section['groups'] = array_values(array_map(function (array $group) {
                $group['classes'] = array_values($group['classes']);

                return $group;
            }, $section['groups']));
            $sectionList[] = $section;
        }

        $payload = [
            'source' => basename($path),
            'generated_at' => now()->toIso8601String(),
            'applications' => $applications,
            'sections' => $sectionList,
            'warnings' => $this->warnings,
        ];

        $out = database_path('seeders/data/catalog_classifier.json');
        File::ensureDirectoryExists(dirname($out));
        File::put($out, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $classCount = collect($sectionList)->flatMap(fn ($s) => $s['groups'])->flatMap(fn ($g) => $g['classes']);
        $this->info(sprintf(
            'Written %s: %d sections, %d groups, %d classes, %d attribute rows, %d synonym phrases, %d applications, %d warnings.',
            $out,
            count($sectionList),
            collect($sectionList)->sum(fn ($s) => count($s['groups'])),
            $classCount->count(),
            $classCount->sum(fn ($c) => count($c['attributes'])),
            $classCount->sum(fn ($c) => count($c['synonyms'])),
            count($applications),
            count($this->warnings),
        ));
        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }

    /**
     * Sheet 1_Дерево: № | Bölmə | Qrup | Məhsul sinfi | Tətbiq sahəsi | attr count | status
     *
     * @param  list<list<string>>  $rows
     * @return array<int, array{number: int, name: string, groups: array<string, array{name: string, classes: array<string, array>}>}>
     */
    private function parseTree(array $rows): array
    {
        $sections = [];

        foreach ($rows as $row) {
            $number = (int) ($row[0] ?? 0);
            $sectionName = trim($row[1] ?? '');
            $groupName = trim($row[2] ?? '');
            $className = trim($row[3] ?? '');
            if ($number < 1 || $sectionName === '' || $groupName === '' || $className === '') {
                continue; // header / documentation rows
            }

            $sections[$number] ??= ['number' => $number, 'name' => $sectionName, 'groups' => []];
            $sections[$number]['groups'][$groupName] ??= ['name' => $groupName, 'classes' => []];
            $sections[$number]['groups'][$groupName]['classes'][$className] ??= [
                'name' => $className,
                'applications' => $this->splitList($row[4] ?? ''),
                'synonyms' => [],
                'attributes' => [],
            ];
        }

        return $sections;
    }

    /**
     * Sheet 2_Характеристики: № | Bölmə | Qrup | Sinif | Tətbiq | Xüsusiyyət | Sahə tipi |
     * Dəyərlər | Ölçü vahidi | İzah | Mürəkkəblik | Məcburi | Filtr | Filtr prioriteti | Status | Qeyd
     *
     * @param  list<list<string>>  $rows
     */
    private function parseAttributes(array $rows, array &$sections): void
    {
        foreach ($rows as $i => $row) {
            $number = (int) ($row[0] ?? 0);
            $groupName = trim($row[2] ?? '');
            $className = trim($row[3] ?? '');
            $attrName = trim($row[5] ?? '');
            $fieldType = trim($row[6] ?? '');

            if ($number < 1 || $attrName === '' || $className === '') {
                continue;
            }
            if (! in_array($fieldType, self::FIELD_TYPES, true)) {
                if ($fieldType !== '-') {
                    $this->warnings[] = "2_Характеристики row ".($i + 1).": unknown field type '{$fieldType}' for '{$attrName}' ({$className}) — skipped.";
                }

                continue; // '-' rows are cross-references to a class in another section
            }

            $class = &$this->resolveClass($sections, $number, $groupName, $className, "2_Характеристики row ".($i + 1));
            if ($class === null) {
                continue;
            }

            $hasOptions = in_array($fieldType, ['dropdown', 'multiselect'], true);
            $unit = $this->nullableCell($row[8] ?? '');
            $tooltip = $this->nullableCell($row[9] ?? '');

            $class['attributes'][] = [
                'name' => $attrName,
                'field_type' => $fieldType,
                'options' => $hasOptions ? $this->splitList($row[7] ?? '') : [],
                'unit' => $unit,
                'tooltip' => $tooltip,
                'complexity' => str_starts_with(trim($row[10] ?? ''), 'Peşəkar') ? 'professional' : 'basic',
                'required' => mb_strtolower(trim($row[11] ?? '')) === 'bəli',
                'filterable' => mb_strtolower(trim($row[12] ?? '')) === 'bəli',
                'filter_priority' => $this->mapFilterPriority($row[13] ?? ''),
                'sort_order' => count($class['attributes']) + 1,
            ];
            unset($class);
        }
    }

    /**
     * Finds a class node in the tree; tolerates group-name drift between sheets
     * and creates missing nodes with a warning so no attribute row is lost.
     */
    private function &resolveClass(array &$sections, int $number, string $groupName, string $className, string $context): ?array
    {
        $null = null;

        if (! isset($sections[$number])) {
            $this->warnings[] = "{$context}: unknown section {$number} — skipped.";

            return $null;
        }

        $groups = &$sections[$number]['groups'];

        if (isset($groups[$groupName]['classes'][$className])) {
            return $groups[$groupName]['classes'][$className];
        }

        // Same class name in another group of the same section (group renamed between sheets).
        foreach ($groups as $name => &$group) {
            if (isset($group['classes'][$className])) {
                if ($name !== $groupName) {
                    $this->warnings[] = "{$context}: class '{$className}' found under group '{$name}' (sheet says '{$groupName}').";
                }

                return $group['classes'][$className];
            }
        }
        unset($group);

        $this->warnings[] = "{$context}: class '{$className}' missing from 1_Дерево — created under '{$groupName}'.";
        $groups[$groupName] ??= ['name' => $groupName, 'classes' => []];
        $groups[$groupName]['classes'][$className] = [
            'name' => $className,
            'applications' => [],
            'synonyms' => [],
            'attributes' => [],
        ];

        return $groups[$groupName]['classes'][$className];
    }

    /**
     * Applications dictionary: canonical list from 4_Справочники plus any extra
     * tags used in the tree (order preserved, duplicates removed).
     *
     * @param  list<list<string>>  $rows
     * @return list<string>
     */
    private function collectApplications(array $rows, array $sections): array
    {
        $applications = [];

        foreach ($rows as $row) {
            if (str_contains($row[0] ?? '', 'Application')) {
                $applications = $this->splitList($row[1] ?? '');
                break;
            }
        }

        if ($applications === []) {
            $this->warnings[] = "4_Справочники: 'Tətbiq sahəsi (Application)' row not found — dictionary built from the tree only.";
        }

        foreach ($sections as $section) {
            foreach ($section['groups'] as $group) {
                foreach ($group['classes'] as $class) {
                    foreach ($class['applications'] as $app) {
                        if (! in_array($app, $applications, true)) {
                            $applications[] = $app;
                        }
                    }
                }
            }
        }

        return $applications;
    }

    /**
     * Sheet 6_Поисковые_синонимы: Bölmə | Məhsul sinfi | comma-separated phrases.
     * Class names are unique across the whole tree, so matching is global.
     *
     * @param  list<list<string>>  $rows
     */
    private function parseSynonyms(array $rows, array &$sections): void
    {
        foreach ($rows as $i => $row) {
            $className = trim($row[1] ?? '');
            $phrases = trim($row[2] ?? '');
            if ($className === '' || $phrases === '' || $className === 'Məhsul sinfi') {
                continue;
            }
            if (preg_match('/Bölmə\s*\d/u', $phrases)) {
                continue; // "see section N" cross-reference, real synonyms live on that row
            }

            $class = &$this->findClassByName($sections, $className);
            if ($class === null) {
                $this->warnings[] = '6_Поисковые_синонимы row '.($i + 1).": class '{$className}' not found in tree — synonyms skipped.";

                continue;
            }

            $class['synonyms'] = array_values(array_unique([...$class['synonyms'], ...$this->splitList($phrases)]));
            unset($class);
        }
    }

    private function &findClassByName(array &$sections, string $className): ?array
    {
        $null = null;

        foreach ($sections as &$section) {
            foreach ($section['groups'] as &$group) {
                if (isset($group['classes'][$className])) {
                    return $group['classes'][$className];
                }
            }
            unset($group);
        }
        unset($section);

        return $null;
    }

    private function mapFilterPriority(string $raw): ?string
    {
        $raw = trim($raw);

        return match (true) {
            str_starts_with($raw, 'Top') => 'top',
            str_starts_with($raw, 'Əlavə') => 'secondary',
            str_starts_with($raw, 'Genişləndirilmiş') => 'advanced',
            default => null,
        };
    }

    /** @return list<string> */
    private function splitList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
    }

    private function nullableCell(string $raw): ?string
    {
        $raw = trim($raw);

        return ($raw === '' || $raw === '-') ? null : $raw;
    }
}
