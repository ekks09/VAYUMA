<?php
/** Deterministic explanation of explicitly selected, unverified inputs. Not a diagnosis. */
function vayuma_agricultural_explanation(array $context, string $language, array $reviewedRules = []): string
{
    $sw = $language === 'sw';
    $lines = [$sw ? 'Msaidizi wa bure wa kanuni (si AI). Huu si uchunguzi wa shamba.' : 'Free rule-based helper (not AI). This is not a field diagnosis.'];
    foreach (['Crop' => ['Zao','Crop'], 'Growth stage' => ['Hatua ya ukuaji','Growth stage'], 'Location' => ['Eneo','Location']] as $key => $labels) {
        if (!empty($context[$key]) && is_string($context[$key])) $lines[] = ($sw ? $labels[0] : $labels[1]) . ': ' . $context[$key] . ($sw ? ' (umeingiza mwenyewe).' : ' (user supplied).');
    }
    $weather = $context['Weather forecast'] ?? null;
    if (is_array($weather)) {
        $loc = $weather['location'] ?? []; $current = $weather['current'] ?? [];
        $lines[] = ($sw ? 'Utabiri wa Open-Meteo (makadirio, si kipimo cha shamba): ' : 'Open-Meteo forecast (model estimate, not a field measurement): ') . ($loc['latitude'] ?? '?') . ', ' . ($loc['longitude'] ?? '?') . '; ' . ($sw ? 'muda wa utabiri: ' : 'forecast time: ') . ($weather['forecast_time'] ?? '?') . ' ' . ($weather['timezone'] ?? '') . '; ' . ($sw ? 'ilipatikana: ' : 'retrieved: ') . ($weather['retrieved_at'] ?? '?') . '.';
        if (is_numeric($current['temperature'] ?? null)) $lines[] = ($sw ? 'Halijoto iliyokadiriwa sasa: ' : 'Estimated current temperature: ') . $current['temperature'] . ' °C.';
        foreach (array_slice($weather['forecast'] ?? [], 0, 2) as $day) {
            if (!is_array($day)) continue;
            $rain = is_numeric($day['rainfall'] ?? null) ? $day['rainfall'] . ' mm' : ($sw ? 'haipatikani' : 'unavailable');
            $chance = is_numeric($day['rain_probability'] ?? null) ? $day['rain_probability'] . '%' : ($sw ? 'haipatikani' : 'unavailable');
            $lines[] = ($sw ? 'Mvua iliyotabiriwa ' : 'Forecast rain ') . ($day['date'] ?? '?') . ': ' . $rain . '; ' . ($sw ? 'uwezekano: ' : 'probability: ') . $chance . '.';
        }
        $lines[] = $sw ? 'Angalia unyevu wa udongo kabla ya kumwagilia; utabiri unaweza kubadilika. Angalia tahadhari rasmi za eneo lako.' : 'Check soil moisture before irrigating; forecasts can change. Check official local warnings.';
        $lines[] = 'Source: https://open-meteo.com/';
    }
    $soil = $context['User-entered laboratory report (not independently verified)'] ?? null;
    if (is_array($soil)) {
        $lines[] = ($sw ? 'Ripoti ya udongo uliyoingiza (haijathibitishwa): sampuli ' : 'User-entered soil report (not independently verified): sample ') . ($soil['sample_date'] ?? '?') . ', ' . ($soil['location'] ?? '?') . '; ' . ($sw ? 'kina ' : 'depth ') . ($soil['depth_cm'] ?? '?') . ' cm; ' . ($sw ? 'maabara ' : 'laboratory ') . ($soil['laboratory'] ?? '?') . '; ' . ($sw ? 'njia ' : 'method ') . ($soil['method'] ?? '?') . '.';
        $labels = ['ph'=>['pH','pH'],'nitrogen'=>['Nitrojeni','Nitrogen'],'phosphorus'=>['Fosforasi','Phosphorus'],'potassium'=>['Potasiamu','Potassium'],'organic_matter'=>['Mabaki hai','Organic matter']];
        $results = is_array($soil['results'] ?? null) ? $soil['results'] : [];
        $missing = [];
        foreach ($labels as $key => $label) {
            $result = $results[$key] ?? null;
            if (!is_array($result) || !is_numeric($result['value'] ?? null) || !is_string($result['unit'] ?? null) || $result['unit'] === '') { $missing[] = $label[$sw ? 0 : 1]; continue; }
            $lines[] = $label[$sw ? 0 : 1] . ': ' . $result['value'] . ' ' . $result['unit'] . '.';
        }
        if ($missing) $lines[] = ($sw ? 'Haikutolewa: ' : 'Not supplied: ') . implode(', ', $missing) . '.';
        $lines[] = $sw ? 'pH huonyesha ukali au uasili; virutubisho hutafsiriwa kulingana na zao, njia ya maabara na eneo. Mtaalamu atafsiri ripoti; hatuhesabu kiwango cha mbolea au chokaa.' : 'pH describes acidity or alkalinity; nutrient interpretation depends on the crop, laboratory method and local calibration. Ask a professional to interpret the report; no fertilizer or lime rate is calculated.';
        if (!empty($soil['sample_date']) && strtotime((string)$soil['sample_date']) !== false && strtotime((string)$soil['sample_date']) < strtotime('-2 years')) $lines[] = $sw ? 'Sampuli ina zaidi ya miaka miwili: uliza mtaalamu kama kipimo kipya kinafaa.' : 'The sample is over two years old: ask an adviser whether retesting is appropriate.';
        $lines[] = 'Source: FAO soil testing guidance, https://www.fao.org/4/i0131e/i0131e.pdf';
    }
    if (!$weather) $lines[] = $sw ? 'Hakuna utabiri uliochaguliwa au unaopatikana.' : 'No selected recent forecast was available.';
    if (!$soil) $lines[] = $sw ? 'Hakuna ripoti ya udongo iliyochaguliwa au inayopatikana.' : 'No selected soil report was available.';
    $matched = vayuma_reviewed_crop_rules($reviewedRules, (string)($context['Crop'] ?? ''), (string)($context['Location'] ?? ''));
    if (!$matched) $lines[] = $sw ? 'Hakuna mwongozo uliopitiwa unaolingana na zao na kaunti yako. Wasiliana na afisa ugani kabla ya uamuzi wa matibabu au kipimo.' : 'No professionally reviewed crop-specific rules match the crop and county provided. Contact an extension officer before treatment or application decisions.';
    foreach ($matched as $rule) {
        $lines[] = ($sw ? 'Mwongozo uliopitiwa kwa ' : 'Reviewed guidance for ') . $rule['crop'] . ', ' . $rule['county'] . ': ' . $rule[$sw ? 'advice_sw' : 'advice_en'];
        $lines[] = ($sw ? 'Mpitiaji: ' : 'Reviewer: ') . $rule['reviewer'] . ', ' . $rule['qualification'] . '; ' . $rule['reviewed_on'] . '; ' . $rule['review_reference'] . '. ' . ($sw ? 'Inatumika hadi: ' : 'Valid until: ') . $rule['valid_until'] . '.';
        $lines[] = 'Source: ' . $rule['source_name'] . ' — ' . $rule['source_url'];
    }
    $questions = [];
    if (empty($context['Crop'])) $questions[] = $sw ? 'zao' : 'crop';
    if (empty($context['Growth stage'])) $questions[] = $sw ? 'hatua ya ukuaji' : 'growth stage';
    if (empty($context['Location'])) $questions[] = $sw ? 'eneo la shamba' : 'farm location';
    $lines[] = $questions ? ($sw ? 'Tafadhali toa: ' : 'Please provide: ') . implode(', ', $questions) . '.' : ($sw ? 'Tatizo gani umeona shambani, na lilianza lini?' : 'What problem have you observed in the field, and when did it start?');
    return implode("\n", $lines);
}

/** Match only complete, time-valid rules for an explicitly supplied crop and county. */
function vayuma_reviewed_crop_rules(array $rules, string $crop, string $county): array
{
    $crop = trim($crop); $county = trim($county); $today = date('Y-m-d');
    if ($crop === '' || $county === '') return [];
    $found = [];
    foreach ($rules as $rule) {
        if (!is_array($rule)) continue;
        foreach (['crop','county','valid_from','valid_until','reviewed_on','reviewer','qualification','review_reference','source_name','source_url','advice_en','advice_sw'] as $key) {
            if (!isset($rule[$key]) || !is_string($rule[$key]) || trim($rule[$key]) === '' || mb_strlen($rule[$key]) > 2000) continue 2;
        }
        foreach (['valid_from','valid_until','reviewed_on'] as $key) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $rule[$key]);
            if (!$date || $date->format('Y-m-d') !== $rule[$key]) continue 2;
        }
        if ($rule['reviewed_on'] > $today || $rule['valid_from'] > $today || $rule['valid_until'] < $today || $rule['valid_until'] < $rule['valid_from']) continue;
        if (!filter_var($rule['source_url'], FILTER_VALIDATE_URL) || strtolower((string)parse_url($rule['source_url'], PHP_URL_SCHEME)) !== 'https') continue;
        if (mb_strtolower(trim($rule['crop'])) !== mb_strtolower($crop) || mb_strtolower(trim($rule['county'])) !== mb_strtolower($county)) continue;
        $found[] = $rule;
        if (count($found) >= 3) break;
    }
    return $found;
}
