<?php
class VayumaProfileError extends RuntimeException {}
/** Compressed thumbnails can be stored atomically alongside profile data in TiDB. */
function vayuma_avatar_uses_tidb(): bool {
    return getenv('VAYUMA_AVATAR_STORAGE') === 'tidb';
}
function vayuma_avatar_tidb_ready($conn): bool {
    if (!vayuma_avatar_uses_tidb() || !$conn) return false;
    try {
        $result = $conn->query('SELECT user_id FROM user_avatars LIMIT 0');
        if (!$result) return false;
        $result->free();
        return true;
    } catch (Throwable $e) { return false; }
}
function vayuma_avatar_tidb_put($conn, int $userId, string $bytes): void {
    if ($userId < 1 || strlen($bytes) < 1 || strlen($bytes) > 81920) throw new VayumaProfileError('Photo could not be saved.');
    $stmt = $conn->prepare('INSERT INTO user_avatars (user_id, jpeg_bytes) VALUES (?, ?) ON DUPLICATE KEY UPDATE jpeg_bytes=VALUES(jpeg_bytes)');
    if (!$stmt) throw new VayumaProfileError('Photo storage is unavailable.');
    try {
        $stmt->bind_param('is', $userId, $bytes);
        if (!$stmt->execute()) throw new VayumaProfileError('Photo could not be saved.');
    } finally { $stmt->close(); }
}
function vayuma_avatar_tidb_exists($conn, int $userId): bool {
    if ($userId < 1 || !vayuma_avatar_uses_tidb() || !$conn) return false;
    try {
        $stmt = $conn->prepare('SELECT 1 FROM user_avatars WHERE user_id=? AND OCTET_LENGTH(jpeg_bytes) BETWEEN 1 AND 81920 LIMIT 1');
        if (!$stmt) return false;
        try {
            $stmt->bind_param('i', $userId);
            return $stmt->execute() && (bool) $stmt->get_result()->fetch_row();
        } finally { $stmt->close(); }
    } catch (Throwable $e) { return false; }
}
function vayuma_avatar_tidb_get($conn, int $userId): ?string {
    if ($userId < 1 || !vayuma_avatar_uses_tidb()) return null;
    try {
        $stmt = $conn->prepare('SELECT jpeg_bytes FROM user_avatars WHERE user_id=? AND OCTET_LENGTH(jpeg_bytes) BETWEEN 1 AND 81920 LIMIT 1');
        if (!$stmt) return null;
        try {
            $stmt->bind_param('i', $userId);
            if (!$stmt->execute()) return null;
            $row = $stmt->get_result()->fetch_assoc();
        } finally { $stmt->close(); }
        $bytes = $row['jpeg_bytes'] ?? null;
        return is_string($bytes) && strlen($bytes) > 0 && strlen($bytes) <= 81920 ? $bytes : null;
    } catch (Throwable $e) { return null; }
}
/** Returns a private configured directory only when an operator explicitly confirms durability. */
function vayuma_avatar_directory(): ?string {
    if (getenv('VAYUMA_AVATAR_STORAGE_PERSISTENT') !== '1') return null;
    $root = (string)getenv('VAYUMA_STORAGE_ROOT');
    if ($root === '' || $root[0] !== '/' || !is_dir($root) || !is_writable($root)) return null;
    $real = realpath($root); $web = realpath(dirname(__DIR__));
    if (!$real || !$web || $real === $web || strpos($real . '/', $web . '/') === 0) return null;
    $dir = $real . '/avatars';
    if (is_link($dir)) return null;
    if (!is_dir($dir) && !@mkdir($dir, 0700) && !is_dir($dir)) return null;
    return is_writable($dir) ? $dir : null;
}
/** Pure byte-to-thumbnail transform; caller must authenticate upload provenance. */
function vayuma_avatar_encode_jpeg(string $input): string {
    if (strlen($input) < 1 || strlen($input) > 1048576) throw new VayumaProfileError('The photo must be no larger than 1 MB after resizing.');
    if (!class_exists('finfo') || !function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) throw new VayumaProfileError('Photo processing is unavailable on this server.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($input);
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) throw new VayumaProfileError('Choose a JPEG, PNG or WebP image.');
    $info = @getimagesizefromstring($input);
    if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000 || $info[0] * $info[1] > 16000000) throw new VayumaProfileError('Image dimensions are too large or invalid.');
    $image = @imagecreatefromstring($input);
    if (!$image) throw new VayumaProfileError('The image could not be decoded.');
    $thumb = imagecreatetruecolor(160,160);
    if (!$thumb) { unset($image); throw new VayumaProfileError('The image could not be processed.'); }
    $white = imagecolorallocate($thumb,255,255,255); imagefill($thumb,0,0,$white);
    $side = min($info[0],$info[1]);
    imagecopyresampled($thumb,$image,0,0,(int)(($info[0]-$side)/2),(int)(($info[1]-$side)/2),160,160,$side,$side);
    unset($image);
    $bytes = '';
    foreach ([80,70,60,50,40,30] as $quality) {
        ob_start(); $ok = imagejpeg($thumb, null, $quality); $encoded = ob_get_clean();
        if ($ok && is_string($encoded) && strlen($encoded) <= 81920) { $bytes = $encoded; break; }
    }
    unset($thumb);
    if ($bytes === '') throw new VayumaProfileError('The photo could not be compressed below 80 KB.');
    return $bytes;
}
/** Enforce HTTP upload provenance and size before reading any image bytes. */
function vayuma_avatar_compress(array $upload): string {
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) throw new VayumaProfileError('The photo upload failed.');
    $tmp = $upload['tmp_name'];
    if (($upload['size'] ?? 0) < 1 || $upload['size'] > 1048576 || filesize($tmp) > 1048576) throw new VayumaProfileError('The photo must be no larger than 1 MB after resizing.');
    $bytes = file_get_contents($tmp);
    if (!is_string($bytes)) throw new VayumaProfileError('The photo upload failed.');
    return vayuma_avatar_encode_jpeg($bytes);
}
