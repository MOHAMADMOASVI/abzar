<?php
/**
 * تشخیص ffmpeg روی سرور (لوکال مک با Homebrew، یا هر هاستی که ffmpeg دارد).
 * روی هاست اشتراکی معمولاً null برمی‌گردد → صفحات به ffmpeg.wasm مرورگر برمی‌گردند.
 */
function abzar_ffmpeg_bin(): ?string
{
    static $bin = false;
    if ($bin !== false) return $bin;

    if (!function_exists('proc_open')) {
        return $bin = null;
    }
    foreach (['/opt/homebrew/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/usr/bin/ffmpeg'] as $c) {
        if (is_executable($c)) return $bin = $c;
    }
    $w = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null'));
    return $bin = ($w !== '' && is_executable($w)) ? $w : null;
}
