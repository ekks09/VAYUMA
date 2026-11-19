<?php
require __DIR__ . '/../vayuma/includes/avatar_storage.php';
function ok($v,$m) { if (!$v) { fwrite(STDERR,"FAIL $m\n"); exit(1); } }
function fixture($width,$height,$format) {
    $im=imagecreatetruecolor($width,$height);
    for ($i=0;$i<1000;$i++) { $c=imagecolorallocate($im,($i*13)%256,($i*67)%256,($i*23)%256); imagefilledrectangle($im,($i*7)%$width,($i*19)%$height,($i*7)%$width+10,($i*19)%$height+10,$c); }
    ob_start(); if ($format==='png') imagepng($im); elseif ($format==='webp') imagewebp($im); else imagejpeg($im); $bytes=ob_get_clean(); unset($im); return $bytes;
}
foreach (['png','jpeg','webp'] as $format) {
    $result=vayuma_avatar_encode_jpeg(fixture(500,300,$format));
    $info=getimagesizefromstring($result); ok($info[0]===160 && $info[1]===160 && $info['mime']==='image/jpeg', "$format crop and JPEG");
    ok(strlen($result)<=81920, "$format byte limit");
}
$raw=fixture(400,250,'jpeg');
$metadata="Exif\0\0VAYUMA_PRIVATE_METADATA_TEST";
$withMetadata=substr($raw,0,2)."\xff\xe1".pack('n',strlen($metadata)+2).$metadata.substr($raw,2);
$clean=vayuma_avatar_encode_jpeg($withMetadata);
ok(strpos($clean,'VAYUMA_PRIVATE_METADATA_TEST')===false,'metadata stripped');
foreach (['not an image',str_repeat('x',1048577), ''] as $bad) {
    try { vayuma_avatar_encode_jpeg($bad); ok(false,'reject invalid/oversize'); } catch (VayumaProfileError $e) {}
}
echo "PASS real GD resize/re-encode, PNG/JPEG/WebP input and invalid/oversize rejection\n";
