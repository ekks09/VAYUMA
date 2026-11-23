<?php
/** Curated paths only. Links are navigational, not authorization or account actions. */
require_once __DIR__ . '/role_routes.php';
function vayuma_ai_navigation_catalog(string $role, string $language): array
{
    $sw = $language === 'sw';
    $dashboard = vayuma_role_route($role, 'dashboard') ?? 'role.php';
    $profile = vayuma_role_route($role, 'profile') ?? 'role.php';
    return [
        ['id'=>'profile','label'=>$sw?'Wasifu na mipangilio':'Profile & settings','href'=>'profile.php','keywords'=>['profile','account details','edit account','personal details','wasifu','taarifa za akaunti']],
        ['id'=>'edit_profile','label'=>$sw?'Badili wasifu wa mdau':'Edit stakeholder profile','href'=>$profile,'keywords'=>['profile','farm details','farm information','wasifu','shamba']],
        ['id'=>'dashboard','label'=>$sw?'Dashibodi yangu':'My dashboard','href'=>$dashboard,'keywords'=>['dashboard','home page','dashibodi']],
        ['id'=>'role','label'=>$sw?'Chagua aina ya mdau':'Choose account category','href'=>'role.php','keywords'=>['role','category','stakeholder type','category selection','jukumu','aina ya mdau']],
        ['id'=>'subscription','label'=>$sw?'Usajili wangu':'My subscription','href'=>'subscription.php','keywords'=>['subscription','trial','renew','payment','paybill','mpesa','usajili','malipo','lipa']],
        ['id'=>'market','label'=>$sw?'Soko la mazao':'Produce marketplace','href'=>'market_connect.php','keywords'=>['marketplace','market','sell produce','buy produce','soko','uza mazao']],
        ['id'=>'orders','label'=>$sw?'Maagizo yangu':'My orders','href'=>'my_orders.php','keywords'=>['orders','my order','purchase','maagizo']],
        ['id'=>'exchange','label'=>$sw?'Huduma na fursa':'Stakeholder exchange','href'=>'stakeholder_offers.php','keywords'=>['exchange','offer','services','service','fursa','huduma']],
        ['id'=>'requests','label'=>$sw?'Maombi ya huduma':'Service requests','href'=>'stakeholder_requests.php','keywords'=>['request','requests','maombi']],
        ['id'=>'directory','label'=>$sw?'Orodha ya wadau':'Stakeholder directory','href'=>'stakeholder_directory.php','keywords'=>['directory','extension officer','expert','veterinary','orodha','ugani','mtaalamu']],
        ['id'=>'climate','label'=>$sw?'Mwongozo wa hali ya hewa':'Climate guidance','href'=>'feature_access.php?feature=climate','keywords'=>['weather','climate','forecast','rainfall','hali ya hewa','utabiri','mvua']],
        ['id'=>'soil','label'=>$sw?'Taarifa za udongo':'Soil intelligence','href'=>'feature_access.php?feature=soil','keywords'=>['soil','udongo']],
        ['id'=>'budget','label'=>$sw?'Mpangaji wa bajeti':'Budget planner','href'=>'budget_planner.php','keywords'=>['budget planner','budget tool','bajeti']],
        ['id'=>'crop_calendar','label'=>$sw?'Kalenda ya mazao':'Crop calendar','href'=>'crop_calendar.php','keywords'=>['crop calendar','planting calendar','kalenda']],
        ['id'=>'disease','label'=>$sw?'Msaada wa magonjwa':'Disease helper','href'=>'disease_helper.php','keywords'=>['disease helper','pest tool','disease tool','magonjwa']],
        ['id'=>'staff','label'=>$sw?'Ufikiaji wa wafanyakazi':'Staff Access','href'=>'onboarding.php','keywords'=>['staff access','staff onboarding','farmer onboarding','wafanyakazi']],
        ['id'=>'claim','label'=>$sw?'Unganisha akaunti ya mkulima':'Claim a farmer account','href'=>'claim_account.php','keywords'=>['claim account','claim my account','claim farmer','unganisha akaunti']],
    ];
}
function vayuma_ai_navigation_links(string $question, string $role, string $language): array
{
    $question = mb_strtolower($question, 'UTF-8'); $links=[];
    foreach (vayuma_ai_navigation_catalog($role, $language) as $item) {
        foreach ($item['keywords'] as $word) {
            if (preg_match('/(?<![\\p{L}\\p{N}])'.preg_quote($word,'/').'(?![\\p{L}\\p{N}])/u', $question)) {
                $links[]=['id'=>$item['id'],'label'=>$item['label'],'href'=>$item['href']];
                break;
            }
        }
        if (count($links) >= 4) break;
    }
    return $links;
}
