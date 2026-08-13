<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal dependency-free XLSX reader — enough to import the customer's
 * catalog classifier workbook (shared strings + inline strings, no formulas).
 */
class XlsxReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_PKGREL = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const NS_DOCREL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @return array<string, list<list<string>>> sheet name => rows (0-indexed, trimmed cell strings)
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("XLSX file not found: {$path}");
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Cannot open XLSX archive: {$path}");
        }

        try {
            $shared = $this->sharedStrings($zip);
            $result = [];

            foreach ($this->sheetTargets($zip) as $name => $target) {
                $result[$name] = $this->sheetRows($zip, $target, $shared);
            }

            return $result;
        } finally {
            $zip->close();
        }
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $shared = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return $shared;
        }

        foreach (simplexml_load_string($xml)->children(self::NS_MAIN) as $si) {
            $t = $si->children(self::NS_MAIN);
            if (isset($t->t)) {
                $shared[] = (string) $t->t;
                continue;
            }
            $s = '';
            foreach ($t->r as $r) {
                $s .= (string) $r->children(self::NS_MAIN)->t;
            }
            $shared[] = $s;
        }

        return $shared;
    }

    /** @return array<string, string> sheet name => zip entry */
    private function sheetTargets(ZipArchive $zip): array
    {
        $wb = simplexml_load_string($zip->getFromName('xl/workbook.xml'))->children(self::NS_MAIN);
        $relsRoot = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'))->children(self::NS_PKGREL);

        $rels = [];
        foreach ($relsRoot as $rel) {
            $a = $rel->attributes();
            $rels[(string) $a['Id']] = (string) $a['Target'];
        }

        $sheets = [];
        foreach ($wb->sheets->children(self::NS_MAIN) as $sh) {
            $rid = (string) $sh->attributes(self::NS_DOCREL)['id'];
            $target = $rels[$rid] ?? null;
            if ($target) {
                $target = ltrim($target, '/');
                if (! str_starts_with($target, 'xl/')) {
                    $target = 'xl/'.$target;
                }
                $sheets[(string) $sh->attributes()['name']] = $target;
            }
        }

        return $sheets;
    }

    /**
     * @param  list<string>  $shared
     * @return list<list<string>>
     */
    private function sheetRows(ZipArchive $zip, string $target, array $shared): array
    {
        $xml = $zip->getFromName($target);
        if ($xml === false) {
            return [];
        }

        $rows = [];
        $ws = simplexml_load_string($xml)->children(self::NS_MAIN);

        foreach ($ws->sheetData->children(self::NS_MAIN) as $row) {
            $cells = [];
            $maxCol = 0;
            foreach ($row->children(self::NS_MAIN) as $c) {
                $ca = $c->attributes();
                $ref = (string) $ca['r'];
                $ci = $ref !== '' ? $this->colIndex($ref) : ($maxCol + 1);
                $maxCol = max($maxCol, $ci);
                $cells[$ci] = trim($this->cellValue($c, $shared));
            }

            $line = [];
            for ($i = 1; $i <= $maxCol; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            if (implode('', $line) === '') {
                continue; // fully empty row
            }
            $rows[] = $line;
        }

        return $rows;
    }

    /** @param list<string> $shared */
    private function cellValue(SimpleXMLElement $c, array $shared): string
    {
        $type = (string) $c->attributes()['t'];
        $cc = $c->children(self::NS_MAIN);

        return match ($type) {
            's' => $shared[(int) $cc->v] ?? '',
            'inlineStr' => (string) $cc->is->children(self::NS_MAIN)->t,
            default => isset($cc->v) ? (string) $cc->v : '',
        };
    }

    private function colIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $n = 0;
        foreach (str_split($m[1]) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n;
    }
}
