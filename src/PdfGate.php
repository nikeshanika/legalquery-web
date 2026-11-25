<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * A short sum ("What is 6 + 9?") that a reader answers every time before a PDF opens, to stop bots
 * and scripts from downloading the PDFs in bulk (like court.legalquery.in). No database, no session:
 *
 * - A question comes with a signed token (expiry, nonce, HMAC over the right answer), so the server
 *   checks an answer without storing the question.
 * - Each token opens one PDF once: its nonce is recorded as a file in $usedDir, and a second use is
 *   refused. Old nonce files are removed now and then.
 * - A right answer also gives a pass cookie for that one PDF URL and that one token for a few minutes
 *   (pass()), so Chrome's PDF viewer can save the PDF it shows (it sends the same POST again). It never
 *   opens a PDF for a new click: every open asks again (owner, 2026-11-24).
 *
 * The secret is `pdf_captcha_secret` in config.local.php. Changing it ends every open question.
 */
final class PdfGate
{
    public const FORM_TTL = 600;

    /** Seconds the pass cookie for one opened PDF lasts (owner, 2026-11-21: download from the viewer). */
    public const PASS_TTL = 300;

    public const PASS_COOKIE = 'lq_pdf_pass';

    public function __construct(private readonly string $secret, private readonly string $usedDir)
    {
        if (strlen($secret) < 16) {
            throw new \InvalidArgumentException('pdf_captcha_secret must be at least 16 characters');
        }
    }

    /**
     * A new question.
     *
     * @return array{a: int, b: int, token: string}
     */
    public function question(?int $now = null): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $expires = ($now ?? time()) + self::FORM_TTL;
        $nonce = bin2hex(random_bytes(8));
        $token = $expires . '.' . $nonce . '.' . $this->mac("q|$expires|$nonce|" . ($a + $b));
        return ['a' => $a, 'b' => $b, 'token' => $token];
    }

    /**
     * True when $answer is the right answer to the question behind $token, in time, and the token
     * was not used before. A right answer uses the token up.
     */
    public function check(string $token, string $answer, ?int $now = null): bool
    {
        $now ??= time();
        $answer = trim($answer);
        if (!preg_match('/^\d{1,2}$/', $answer) || !preg_match('/^(\d{1,12})\.([0-9a-f]{16})\.([0-9a-f]{64})$/', $token, $m)) {
            return false;
        }
        [, $expires, $nonce, $mac] = $m;
        if ((int) $expires < $now || !hash_equals($this->mac("q|$expires|$nonce|" . (int) $answer), $mac)) {
            return false;
        }
        return $this->useUp($nonce, $now);
    }

    /**
     * Value of the pass cookie given with a PDF after a right answer, for $path (FileController signs the
     * PDF URL + the answered token) and PASS_TTL seconds. It lets Chrome's PDF viewer send the same POST
     * again (its download / save button), which would otherwise get the question page instead of the PDF.
     */
    public function pass(string $path, ?int $now = null): string
    {
        $expires = ($now ?? time()) + self::PASS_TTL;
        return $expires . '.' . $this->mac("p|$expires|$path");
    }

    /**
     * True when $value is a pass for $path that has not expired.
     */
    public function passes(string $value, string $path, ?int $now = null): bool
    {
        if (!preg_match('/^(\d{1,12})\.([0-9a-f]{64})$/', $value, $m)) {
            return false;
        }
        return (int) $m[1] >= ($now ?? time()) && hash_equals($this->mac("p|$m[1]|$path"), $m[2]);
    }

    /**
     * Records the nonce; false when it was recorded before (the token was used already).
     */
    private function useUp(string $nonce, int $now): bool
    {
        if (!is_dir($this->usedDir) && !@mkdir($this->usedDir, 0700, true) && !is_dir($this->usedDir)) {
            throw new \RuntimeException('Cannot create ' . $this->usedDir);
        }
        $handle = @fopen($this->usedDir . '/' . $nonce, 'x');
        if ($handle === false) {
            return false;
        }
        fclose($handle);
        if (random_int(1, 50) === 1) {
            foreach (glob($this->usedDir . '/*') ?: [] as $file) {
                if (@filemtime($file) < $now - self::FORM_TTL - 60) {
                    @unlink($file);
                }
            }
        }
        return true;
    }

    private function mac(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret);
    }
}
