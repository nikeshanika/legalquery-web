<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Gives a PDF the site's title while it is served, without touching the file on disk.
 *
 * Many source PDFs carry junk in their Title metadata: legacy-font Hindi ("[£ÉÉ®iÉ BÉEä ®ÉVÉ{ÉjÉ …",
 * "Hkkjr ds jkti= ds vlk/kkj"), "Microsoft Word - H2007-56 à¤®…", "C:\Users\…", "117gi.p65", "Untitled".
 * Browsers show that title in the tab and the PDF viewer (owner, 2026-11-26). suffix() returns a PDF
 * incremental update (a new Info dictionary with /Title, plus a cross-reference section and trailer
 * pointing back to the file's own) to send right after the file's bytes. The file, its checksum and its
 * URL stay the same; only the served copy ends in these few hundred bytes.
 *
 * It gives up (returns '') whenever it cannot be sure the update is valid: no `startxref`, the offset
 * not pointing at a cross-reference table or stream, no /Root or /Size, or an encrypted PDF (its strings
 * would need encrypting). The PDF is then served as it is.
 */
final class PdfTitle
{
    private const TAIL = 4096;
    private const HEAD = 65536;

    /**
     * The bytes to append to the PDF at $path so that its title is $title, or ''.
     */
    public static function suffix(string $path, string $title): string
    {
        $title = trim((string) preg_replace('/\s+/u', ' ', $title));
        $size = @filesize($path);
        if ($title === '' || $size === false || $size < 64) {
            return '';
        }
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return '';
        }
        try {
            fseek($fh, max(0, $size - self::TAIL));
            $tail = (string) fread($fh, self::TAIL);
            if (!preg_match_all('/startxref\s+(\d+)/', $tail, $m) || ($prev = (int) end($m[1])) <= 0 || $prev >= $size) {
                return '';
            }
            fseek($fh, $prev);
            $head = (string) fread($fh, self::HEAD);
        } finally {
            fclose($fh);
        }

        if (str_starts_with($head, 'xref')) {
            // A classic table: its trailer dictionary follows the entries, right before the last `startxref`
            // (a long table can push it past $head, so look in $tail first).
            $at = strrpos($tail, 'trailer');
            $src = $tail;
            if ($at === false) {
                $at = strpos($head, 'trailer');
                $src = $head;
            }
            if ($at === false) {
                return '';
            }
            $end = strpos($src, 'startxref', $at);
            $dict = substr($src, $at, $end === false ? null : $end - $at);
            $stream = false;
        } elseif (preg_match('/^\d+\s+\d+\s+obj\s*<</', $head)) {
            // A cross-reference stream (PDF 1.5+): its dictionary comes before `stream`.
            $end = strpos($head, 'stream');
            $dict = substr($head, 0, $end === false ? 0 : $end);
            if (!preg_match('#/Type\s*/XRef\b#', $dict)) {
                return '';
            }
            $stream = true;
        } else {
            return '';
        }
        if (str_contains($dict, '/Encrypt')
            || !preg_match('#/Root\s+(\d+\s+\d+)\s+R#', $dict, $root)
            || !preg_match('#/Size\s+(\d+)#', $dict, $count)) {
            return '';
        }
        $info = (int) $count[1];
        $id = preg_match('#/ID\s*(\[\s*<[0-9A-Fa-f]*>\s*<[0-9A-Fa-f]*>\s*\])#', $dict, $ids) ? ' /ID ' . $ids[1] : '';
        [$rootNum, $rootGen] = array_map('intval', preg_split('/\s+/', $root[1]));

        // The title as UTF-16BE with a byte order mark, in hex: any script, nothing to escape.
        $hex = 'FEFF' . strtoupper(bin2hex((string) mb_convert_encoding($title, 'UTF-16BE', 'UTF-8')));
        $out = "\n";
        $objects = [];
        // Firefox (pdf.js) shows the XMP metadata's dc:title before the Info /Title, and it is as much junk:
        // a new version of the catalog without /Metadata, when the catalog is a plain object in the file.
        $catalog = self::catalog($path, $rootNum, $rootGen);
        if ($catalog !== null) {
            $objects[$rootNum] = [$size + strlen($out), $rootGen];
            $out .= "$rootNum $rootGen obj\n$catalog\nendobj\n";
        }
        $objects[$info] = [$size + strlen($out), 0];
        $out .= "$info 0 obj\n<< /Title <$hex> >>\nendobj\n";
        $xrefAt = $size + strlen($out);
        ksort($objects);

        if (!$stream) {
            $table = '';
            foreach ($objects as $num => [$at, $gen]) {
                $table .= sprintf("%d 1\n%010d %05d n \n", $num, $at, $gen);
            }
            return $out . sprintf(
                "xref\n%strailer\n<< /Size %d /Root %s R /Info %d 0 R /Prev %d%s >>\nstartxref\n%d\n%%%%EOF\n",
                $table, $info + 1, $root[1], $info, $prev, $id, $xrefAt
            );
        }
        // After a cross-reference stream, the update is one too (uncompressed): an entry per object, each
        // type 1 (in use), a 4-byte offset and a 2-byte generation, the stream's own entry last.
        $xref = $info + 1;
        $objects[$xref] = [$xrefAt, 0];
        $index = $data = '';
        foreach ($objects as $num => [$at, $gen]) {
            $index .= " $num 1";
            $data .= pack('CNn', 1, $at, $gen);
        }
        return $out . sprintf(
            "%d 0 obj\n<< /Type /XRef /Size %d /W [1 4 2] /Index [%s] /Root %s R /Info %d 0 R /Prev %d%s /Length %d >>\nstream\n",
            $xref, $xref + 1, trim($index), $root[1], $info, $prev, $id, strlen($data)
        ) . $data . sprintf("\nendstream\nendobj\nstartxref\n%d\n%%%%EOF\n", $xrefAt);
    }

    /**
     * The catalog dictionary (object $num $gen) without its /Metadata entry, or null when it has none or
     * is not found as a plain object (e.g. it sits in an object stream). The file's last definition wins.
     */
    private static function catalog(string $path, int $num, int $gen): ?string
    {
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }
        $pattern = "/(?<![0-9])$num\\s+$gen\\s+obj\\s*(<<.*?>>)\\s*endobj/s";
        $found = null;
        $carry = '';
        while (!feof($fh)) {
            $chunk = $carry . (string) fread($fh, 1 << 20);
            if (preg_match_all($pattern, $chunk, $m)) {
                $found = end($m[1]);
            }
            $carry = substr($chunk, -4096);
        }
        fclose($fh);
        if ($found === null || !preg_match('#/Type\s*/Catalog\b#', $found) || !preg_match('#/Metadata\s+\d+\s+\d+\s+R#', $found)) {
            return null;
        }
        return (string) preg_replace('#/Metadata\s+\d+\s+\d+\s+R#', '', $found);
    }
}
