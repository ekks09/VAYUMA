<?php
/** Maintained from the actual VAYUMA interface, not learned from private accounts. */
function vayuma_ai_platform_facts(): array
{
    return [
        'account'=>['keywords'=>['login','log in','sign in','register','account','profile','role','category','akaunti','ingia','jisajili','wasifu','jukumu'],
            'en'=>'Account: Create an account or sign in, choose a stakeholder category, and complete the applicable profile. Your dashboard depends on your category. Account access and subscription rules still apply.',
            'sw'=>'Akaunti: Fungua akaunti au ingia, chagua aina ya mdau, na ukamilishe wasifu unaohitajika. Dashibodi hutegemea aina yako. Masharti ya akaunti na usajili bado yanatumika.'],
        'climate'=>['keywords'=>['weather','climate','rain','forecast','gps','location','hali ya hewa','mvua','utabiri','eneo'],
            'en'=>'Climate Guidance: Search a Kenyan town and confirm the match, or explicitly allow phone location. The forecast shows Open-Meteo model estimates, timestamps, temperature, rainfall and indicators; check official alerts and local conditions. You can choose to retain a forecast for this session.',
            'sw'=>'Mwongozo wa Hali ya Hewa: Tafuta mji wa Kenya na thibitisha eneo, au ruhusu eneo la simu. Utabiri wa Open-Meteo huonyesha makadirio, muda, halijoto, mvua na viashiria; angalia tahadhari rasmi na hali ya eneo. Unaweza kuchagua kuhifadhi utabiri kwa kipindi hiki.'],
        'soil'=>['keywords'=>['soil','ph','nitrogen','phosphorus','potassium','fertilizer','fertiliser','lab','udongo','maabara','mbolea','virutubisho'],
            'en'=>'Soil Intelligence: Enter measured values, not guesses. Soil test history can save a user-entered laboratory report with sample date, location, depth, method and units when storage is configured. Results are not verified and depend on the laboratory method, crop and local calibration; ask an extension officer for interpretation. No fertilizer or lime rate is calculated.',
            'sw'=>'Taarifa za Udongo: Ingiza vipimo halisi, si makisio. Historia inaweza kuhifadhi ripoti ya maabara iliyoingizwa na mtumiaji pamoja na tarehe, eneo, kina, njia na vipimo ikiwa hifadhi imeandaliwa. Matokeo hayajathibitishwa na hutegemea njia ya maabara, zao na eneo; omba afisa ugani ayatafsiri. Hakuna kiwango cha mbolea au chokaa kinachokokotolewa.'],
        'support'=>['keywords'=>['extension','officer','expert','adviser','advisor','vet','veterinary','ugani','mtaalamu','mifugo'],
            'en'=>'Professional support: Open the stakeholder directory and select extension officer. A listed profile may offer a support request; availability and verification status depend on the provider. Serious animal illness needs a qualified veterinarian.',
            'sw'=>'Msaada wa kitaalamu: Fungua orodha ya wadau na uchague afisa ugani. Wasifu uliopo unaweza kuruhusu ombi la msaada; upatikanaji na uthibitisho hutegemea mtoa huduma. Ugonjwa mkubwa wa mnyama unahitaji daktari wa mifugo.'],
        'market'=>['keywords'=>['market','produce','listing','buyer','sell','soko','mazao','mnunuzi','uza'],
            'en'=>'Market Connect: Browse produce through the marketplace. Eligible users can manage listings or orders according to their role. The assistant cannot see current listings, prices, orders or availability unless explicitly provided.',
            'sw'=>'Soko: Vinjari mazao kwenye soko. Watumiaji wanaostahili wanaweza kusimamia matangazo au maagizo kulingana na jukumu lao. Msaidizi haoni matangazo, bei, maagizo au upatikanaji wa sasa isipokuwa atapewa taarifa hizo.'],
        'subscription'=>['keywords'=>['subscription','price','pricing','payment','mpesa','m-pesa','paystack','usajili','bei','malipo','lipa'],
            'en'=>'Subscriptions: Check Subscription for your account status and the current administrator-managed price. Payment processing is currently unavailable; do not claim payment or activation occurred. The assistant cannot check your balance or approve access.',
            'sw'=>'Usajili: Angalia ukurasa wa Usajili kwa hali ya akaunti yako na bei ya sasa inayosimamiwa na msimamizi. Malipo hayapatikani kwa sasa; usidai malipo au uanzishaji umetokea. Msaidizi hawezi kuona salio au kuidhinisha ufikiaji.'],
        'navigation'=>['keywords'=>['dashboard','navigation','navigate','feature','tool','dashibodi','zana','ukurasa'],
            'en'=>'Navigation: Your role-specific dashboard and sidebar provide links to available tools. Feature access can require signing in, completing your profile and meeting subscription requirements. Return to your dashboard from a tool using its dashboard link. Available tools vary by role.',
            'sw'=>'Urambazaji: Dashibodi na menyu ya jukumu lako zina viungo vya zana zinazopatikana. Ufikiaji unaweza kuhitaji kuingia, kukamilisha wasifu na kutimiza masharti ya usajili. Tumia kiungo cha dashibodi kurudi kutoka kwenye zana. Zana hutofautiana kulingana na jukumu.'],
        'budget'=>['keywords'=>['budget','planner','cost','profit','bajeti','gharama','faida'],
            'en'=>'Budget Planner: Enter your own estimated income and costs to calculate an indicative result. Estimates are not guaranteed profits and the assistant cannot see saved finances.',
            'sw'=>'Mpangaji wa Bajeti: Ingiza makadirio yako ya mapato na gharama kukokotoa matokeo ya makadirio. Makadirio si faida iliyohakikishwa na msaidizi hawezi kuona fedha zilizohifadhiwa.'],
        'assistant'=>['keywords'=>['ai','assistant','nvidia','msaidizi','akili bandia'],
            'en'=>'VAYUMA TRIX AI Support sends your questions and selected context to an external AI service when enabled. Answers can be wrong; it cannot see the codebase, accounts or live data. Avoid sending personal or confidential information.',
            'sw'=>'VAYUMA TRIX AI Support hutuma maswali na taarifa ulizochagua kwa huduma ya AI ya nje ikiwezeshwa. Majibu yanaweza kukosea; haina ufikiaji wa msimbo, akaunti au data ya moja kwa moja. Usitume taarifa binafsi au za siri.'],
    ];
}
function vayuma_ai_guide(string $question, string $language, bool $all = false): string
{
    $language = $language === 'sw' ? 'sw' : 'en';
    $question = mb_strtolower($question); $selected=[];
    foreach (vayuma_ai_platform_facts() as $fact) {
        if (!$all) {
            $found=false;
            foreach ($fact['keywords'] as $word) {
                if (preg_match('/(?<![\\p{L}\\p{N}])'.preg_quote($word,'/').'(?![\\p{L}\\p{N}])/u', $question)) { $found=true; break; }
            }
            if (!$found) continue;
        }
        $selected[]=$fact[$language];
    }
    return implode("\n", $selected);
}
