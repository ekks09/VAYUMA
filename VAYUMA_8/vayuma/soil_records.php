<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false)!==true) { header('Location: login.php'); exit; }
require_once __DIR__.'/includes/language.php';
require_once __DIR__.'/includes/role_routes.php';
require_once __DIR__.'/includes/csrf.php';
require_once __DIR__.'/config/db.php';
$uid=(int)$_SESSION['user_id']; $ready=false; $error=''; $notice=''; $records=[];
function soil_h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
try { $probe=$conn->query('SELECT id FROM soil_test_records LIMIT 0'); if ($probe) { $probe->free(); $ready=true; } } catch (Throwable $ignored) { }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!$ready) $error='Soil history is not set up yet. An administrator must review the soil-records migration.';
    elseif (!vayuma_csrf_valid()) $error='Your session expired. Refresh and try again.';
    else {
        $date=trim((string)($_POST['sample_date'] ?? '')); $location=trim((string)($_POST['location'] ?? '')); $depth=trim((string)($_POST['depth_cm'] ?? '')); $lab=trim((string)($_POST['laboratory'] ?? '')); $method=trim((string)($_POST['method'] ?? ''));
        $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        $results=[]; $valid=true;
        foreach (['ph'=>'pH','nitrogen'=>'Nitrogen','phosphorus'=>'Phosphorus','potassium'=>'Potassium','organic_matter'=>'Organic matter'] as $key=>$label) {
            $value=trim((string)($_POST[$key] ?? '')); $unit=trim((string)($_POST[$key.'_unit'] ?? ''));
            if ($value==='') continue;
            if (!is_numeric($value) || !is_finite((float)$value) || (float)$value<0 || (float)$value>1000000 || ($key==='ph' && (float)$value>14) || ($key!=='ph' && ($unit==='' || mb_strlen($unit)>40))) { $valid=false; break; }
            $results[$key]=['value'=>$value,'unit'=>$key==='ph'?'pH':$unit];
        }
        if (!$parsed || $parsed->format('Y-m-d')!==$date || $date>date('Y-m-d') || $location==='' || mb_strlen($location)>160 || !is_numeric($depth) || (float)$depth<0 || (float)$depth>9999 || $lab==='' || mb_strlen($lab)>160 || $method==='' || mb_strlen($method)>255 || !$valid || !$results) {
            $error='Enter a valid sample date, location, depth, laboratory, method and at least one result with its unit.';
        } else {
            try { $payload=json_encode($results,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); $depth=(float)$depth;
                $stmt=$conn->prepare('INSERT INTO soil_test_records (user_id,sample_date,location,depth_cm,laboratory,method,results_json) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('issdsss',$uid,$date,$location,$depth,$lab,$method,$payload); if (!$stmt->execute()) throw new RuntimeException(); $stmt->close();
                $_SESSION['soil_record_notice']='Soil test saved.'; header('Location: soil_records.php'); exit;
            } catch (Throwable $ignored) { error_log('VAYUMA soil record save failed.'); $error='Could not save the record. Please try again later.'; }
        }
    }
}
$notice=(string)($_SESSION['soil_record_notice'] ?? ''); unset($_SESSION['soil_record_notice']);
if ($ready) { try { $stmt=$conn->prepare('SELECT sample_date,location,depth_cm,laboratory,method,results_json FROM soil_test_records WHERE user_id=? ORDER BY sample_date DESC,id DESC LIMIT 50'); $stmt->bind_param('i',$uid); $stmt->execute(); $rs=$stmt->get_result(); while ($row=$rs->fetch_assoc()) $records[]=$row; $stmt->close(); } catch (Throwable $ignored) { $error='Could not load soil history. Please try again later.'; } }
?>
<!doctype html><html lang="<?=soil_h($current_language)?>"><head><?php require __DIR__.'/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Soil test history | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/components.css"><style>
main{max-width:850px;margin:auto;padding:20px}section,article{border:1px solid #dce8df;background:white;border-radius:12px;padding:18px;margin:16px 0}label{display:block;font-weight:600;margin:12px 0 4px}input,button{font:inherit;min-height:44px;padding:9px;max-width:100%}input{width:100%;box-sizing:border-box}button{background:#087d42;color:white;border:0;border-radius:6px;cursor:pointer}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:12px}nav{display:flex;gap:14px;flex-wrap:wrap}p,li{overflow-wrap:anywhere}
</style><link rel="stylesheet" href="frontend/css/agriculture-tools.css?v=1">
</head><body class="va-records-tool"><?php vayuma_language_selector(); ?><main><?php require __DIR__ . '/includes/workspace_back.php'; ?><nav><a href="soil_analysis.php">← Soil Intelligence</a><a href="<?=soil_h(vayuma_dashboard_return_route())?>">Dashboard</a><a href="stakeholder_directory.php?role=extension_officer">Find an extension officer</a></nav><h1>Soil test history</h1><p>Record results from your laboratory report. These are user-entered and are not verified by VAYUMA. Never guess test values or units.</p>
<?php if(!$ready):?><p role="status">History storage is not configured yet. An administrator must review and apply the soil-records migration before saving. General soil guidance still works.</p><?php endif; ?>
<?php if($error):?><p role="alert"><?=soil_h($error)?></p><?php endif; ?><?php if($notice):?><p role="status"><?=soil_h($notice)?></p><?php endif; ?>
<?php if($ready):?><section><h2>Add laboratory report</h2><form method="post"><?=vayuma_csrf_field()?><div class="grid"><div><label for="sample_date">Sample date</label><input id="sample_date" type="date" name="sample_date" max="<?=date('Y-m-d')?>" required></div><div><label for="location">Sample location</label><input id="location" name="location" maxlength="160" required></div><div><label for="depth_cm">Sample depth (cm)</label><input id="depth_cm" name="depth_cm" type="number" min="0" max="9999" step="0.01" required></div><div><label for="laboratory">Laboratory name</label><input id="laboratory" name="laboratory" maxlength="160" required></div><div><label for="method">Test method(s), as reported</label><input id="method" name="method" maxlength="255" required></div></div><p>Enter at least one result; enter each unit exactly as printed on the report.</p><div class="grid"><?php foreach(['ph'=>'pH','nitrogen'=>'Nitrogen','phosphorus'=>'Phosphorus','potassium'=>'Potassium','organic_matter'=>'Organic matter'] as $key=>$label):?><div><label for="<?=soil_h($key)?>"><?=soil_h($label)?></label><input type="number" id="<?=soil_h($key)?>" name="<?=soil_h($key)?>" min="0" <?= $key==='ph'?'max="14"':'max="1000000"'?> step="any"><?php if($key!=='ph'):?><label for="<?=soil_h($key)?>_unit">Unit for <?=soil_h($label)?></label><input id="<?=soil_h($key)?>_unit" name="<?=soil_h($key)?>_unit" maxlength="40" placeholder="e.g. mg/kg or %"><?php endif;?></div><?php endforeach;?></div><button type="submit">Save report</button></form></section><?php endif; ?>
<section><h2>Saved reports</h2><?php if(!$records):?><p>No reports to display.</p><?php else:foreach($records as $row): $values=json_decode($row['results_json'],true) ?: []; ?><article><h3>Sample <?=soil_h($row['sample_date'])?></h3><p><?=soil_h($row['location'])?> · Depth <?=soil_h($row['depth_cm'])?> cm<br>Laboratory: <?=soil_h($row['laboratory'])?><br>Method: <?=soil_h($row['method'])?></p><?php if(strtotime($row['sample_date']) < strtotime('-2 years')):?><p>More than two years old: ask a local professional whether a new test is appropriate.</p><?php endif;?><ul><?php foreach($values as $key=>$value):?><li><?=soil_h(ucwords(str_replace('_',' ',$key)))?>: <?=soil_h($value['value'] ?? '')?> <?=soil_h($value['unit'] ?? '')?></li><?php endforeach;?></ul></article><?php endforeach;endif;?></section>
<section><h2>Understanding results / Kuelewa matokeo</h2><p>pH describes acidity or alkalinity. Organic matter affects soil structure and water retention. Nitrogen, phosphorus and potassium are nutrients; interpretation depends on the laboratory method, crop and conditions. Ask a qualified adviser to interpret the report.</p><p>pH huonyesha ukali au uasili wa udongo. Mabaki hai husaidia muundo wa udongo na uhifadhi wa maji. Nitrojeni, fosforasi na potasiamu ni virutubisho; tafsiri hutegemea njia ya maabara, zao na hali ya shamba. Omba ushauri wa mtaalamu.</p></section></main></body></html>
