<?php
session_start();
require_once __DIR__ . '/includes/role_routes.php';

/* ==============================
   PROTECT PAGE
============================== */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "Farmer";
?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>VAYUMA Health Helper</title>

<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-profile-page { max-width: 950px; margin: 0 auto; padding: var(--va-space-6); }
.va-profile-brand { text-align: center; margin-bottom: var(--va-space-6); }
.va-profile-brand h1 { color: var(--va-primary); font-size: var(--va-font-size-3xl); letter-spacing: 0.05em; margin: 0; }
.va-profile-brand p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-profile-card { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-6); box-shadow: var(--va-shadow-sm); border: 1px solid var(--va-border); }
.va-profile-heading { margin-bottom: var(--va-space-5); }
.va-profile-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--va-space-2); }
.va-profile-heading h2 { font-size: var(--va-font-size-2xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-profile-heading p { color: var(--va-text-secondary); line-height: var(--va-line-height-relaxed); }
.va-profile-welcome { background: var(--va-primary-light); border-radius: var(--va-radius-md); padding: var(--va-space-4); margin: var(--va-space-6) 0; color: #17663a; line-height: var(--va-line-height-relaxed); }
.va-profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4); }
.va-profile-form-group { display: flex; flex-direction: column; }
.va-profile-full { grid-column: 1 / -1; }
.va-profile-label-field { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-2); color: var(--va-text-secondary); }
.va-profile-input { width: 100%; padding: 0.75rem 1rem; font-size: var(--va-font-size-base); font-family: inherit; border: 1px solid var(--va-border); border-radius: var(--va-radius); background: var(--va-surface); color: var(--va-text-primary); }
.va-profile-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-profile-hint { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: var(--va-space-1); }
.va-profile-button-area { margin-top: var(--va-space-6); }
.va-profile-btn { width: 100%; border: none; padding: var(--va-space-4); background: var(--va-primary); color: #fff; border-radius: var(--va-radius); font-size: var(--va-font-size-base); font-weight: var(--va-font-weight-bold); cursor: pointer; }
.va-profile-btn:hover { background: var(--va-primary-hover); }
.va-profile-back { display: inline-block; margin-top: var(--va-space-4); color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
.va-profile-error { background: #fde7e7; color: #a32121; padding: var(--va-space-3); border-radius: var(--va-radius-md); margin-bottom: var(--va-space-4); }
@media (max-width: 700px) {
  .va-profile-page { padding: var(--va-space-4); }
  .va-profile-card { padding: var(--va-space-4); }
  .va-profile-form-grid { grid-template-columns: 1fr; }
  .va-profile-full { grid-column: auto; }
}
</style>

</head>


<body>


<header class="header">

    <h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> VAYUMA</h1>

    <p>
        Smart Farming Ecosystem
    </p>

</header>


<main class="container">


    <!-- ==============================
         PAGE TITLE
    =============================== -->

    <div class="page-title">

        <h2>
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> VAYUMA Health Helper
        </h2>

        <p>

            Hello
            <?php echo htmlspecialchars($full_name); ?>.

            Get basic guidance about crop diseases
            and animal health problems.

        </p>

    </div>


    <!-- ==============================
         MODE SELECTION
    =============================== -->

    <div class="mode-title">

        <h3>
            What do you need help with?
        </h3>

    </div>


    <div class="mode-grid">


        <!-- CROP -->

        <div
            class="mode-card"
            id="cropMode"
            onclick="selectMode('crop')"
        >

            <div class="mode-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
            </div>

            <h3>
                Crop Health
            </h3>

            <p>
                Check common crop symptoms,
                possible diseases and recommended
                precautions.
            </p>

        </div>


        <!-- ANIMAL -->

        <div
            class="mode-card"
            id="animalMode"
            onclick="selectMode('animal')"
        >

            <div class="mode-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg>
            </div>

            <h3>
                Animal Health
            </h3>

            <p>
                Check common livestock and domestic
                animal health concerns and basic
                precautions.
            </p>

        </div>

    </div>


    <!-- ==============================
         HELPER
    =============================== -->

    <section
        class="helper-card"
        id="helperCard"
        style="display:none;"
    >

        <h3 id="helperTitle">
            Health Check
        </h3>


        <div class="va-profile-form-grid">


            <!-- CROP -->

            <div
                class="form-group"
                id="cropGroup"
            >

                <label class="va-profile-label-field">
                    Select Crop
                </label>

                <select class="va-profile-input" id="crop">

                    <option value="">
                        Choose a crop
                    </option>

                    <option value="maize">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-maize"/></svg> Maize
                    </option>

                    <option value="beans">
                        🫘 Beans
                    </option>

                    <option value="tomatoes">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tomato"/></svg> Tomatoes
                    </option>

                    <option value="potatoes">🥔 Irish potatoes</option>
                    <option value="kale">🥬 Kale / Sukuma wiki</option>
                    <option value="cabbage">🥬 Cabbage</option>
                    <option value="rice"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Rice</option>
                    <option value="banana"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-banana"/></svg> Banana</option>
                    <option value="cassava"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-herbs"/></svg> Cassava</option>
                    <option value="onions">🧅 Onions</option>
                    <option value="sweet_potato"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tomato"/></svg> Sweet potato</option>
                    <option value="sorghum"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Sorghum</option>

                </select>

            </div>


            <!-- ANIMAL -->

            <div
                class="form-group"
                id="animalGroup"
                style="display:none;"
            >

                <label class="va-profile-label-field">
                    Select Animal
                </label>

                <select class="va-profile-input" id="animal">

                    <option value="">
                        Choose an animal
                    </option>

                    <option value="cattle">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg> Cattle
                    </option>

                    <option value="goat">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-goat"/></svg> Goat
                    </option>

                    <option value="sheep">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-sheep"/></svg> Sheep
                    </option>

                    <option value="pig">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-pig"/></svg> Pig
                    </option>

                    <option value="poultry"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-chicken"/></svg> Poultry (chickens, layers and broilers)</option>
                    <option value="rabbit"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-rabbit"/></svg> Rabbits</option>
                    <option value="fish">🐟 Farmed fish (tilapia/catfish)</option>
                    <option value="dogs"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dog"/></svg> Dogs (farm/domestic)</option>

                </select>

            </div>


            <!-- SYMPTOM: options are loaded for the selected crop OR animal -->
            <div class="form-group full">
                <label for="symptom">Main symptom or visible sign</label>
                <select class="va-profile-input" id="symptom" disabled>
                    <option value="">First select a crop or animal</option>
                </select>
                <small id="symptomHint" style="color:#777;margin-top:7px;line-height:1.5;">The symptom list changes according to your selection.</small>
            </div>


            <div class="form-group full">

                <button
                    type="button"
                    class="analyze-button"
                    onclick="analyzeHealth()"
                >

                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Check Health Concern

                </button>

            </div>


        </div>


        <!-- ==============================
             RESULT
        =============================== -->

        <div
            class="result"
            id="result"
        >

            <div class="result-header">

                <h2 id="resultTitle">
                    Health Concern
                </h2>

                <p id="resultType">
                    VAYUMA Guidance
                </p>

            </div>


            <div class="result-body">


                <div class="result-section">

                    <h3>
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Priority
                    </h3>

                    <span
                        class="severity"
                        id="severity"
                    >
                        Moderate
                    </span>

                </div>


                <div class="result-section">

                    <h3>
                        🔎 What may be happening
                    </h3>

                    <p id="signs"></p>

                </div>


                <div class="result-section">
                    <h3><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Main health concern</h3>
                    <p id="concern"></p>
                </div>

                <div class="result-section">
                    <h3>👩‍<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> What the farmer should do</h3>
                    <div class="precaution"><span id="action"></span></div>
                </div>

                <div class="result-section">
                    <h3>🚫 What to avoid</h3>
                    <p id="avoid"></p>
                </div>

                <div class="warning">

                    <strong>
                        Important:
                    </strong>

                    This is a symptom-based guide, not a confirmed diagnosis. Different diseases can cause similar signs, and some conditions require laboratory testing. Do not use human medicines or guess pesticide, antibiotic or dewormer doses. For severe symptoms, fast-spreading disease, suspected outbreaks, multiple sudden deaths, or an animal unable to stand or breathe normally, seek qualified veterinary or agricultural help urgently. In Kenya, contact your local veterinary officer or county agricultural extension officer.

                </div>

            </div>

        </div>


        <button
            class="reset-button"
            onclick="resetHelper()"
        >
            ↻ Start Another Check
        </button>

    </section>


    <a
        href="<?= htmlspecialchars(vayuma_dashboard_return_route(), ENT_QUOTES, 'UTF-8') ?>"
        class="back-button"
    >
        ← Back to Dashboard
    </a>


</main>


<script>
/* VAYUMA HEALTH HELPER — crop and animal pathways are fully separated.
   Guidance is educational and should be confirmed by an extension/veterinary professional. */
const healthCatalog = {
  crop: {
    maize: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-maize"/></svg> Maize', cases:[
      ['yellow_streaks','Yellow streaks along leaves','Maize streak disease (MSV) may be involved','A virus transmitted by leafhoppers can cause yellow or white streaks, stunting and poor cob formation.','Remove heavily affected volunteer maize where advised, control grass weeds around fields, monitor leafhoppers and use locally recommended tolerant varieties. Ask an extension officer to confirm.','Do not assume it is a nutrient deficiency or spray an unverified pesticide.'],
      ['leaf_spots','Brown, grey or oval leaf spots','Northern corn leaf blight, grey leaf spot or other leaf-spot disease','Fungal leaf diseases can reduce green leaf area and grain filling, especially in humid conditions.','Inspect both sides of leaves, avoid working in wet crops, manage crop residues and seek local advice on resistant varieties and approved control options.','Avoid overhead irrigation late in the day and avoid repeated use of the same fungicide without advice.'],
      ['rust','Orange-brown powdery pustules','Common or southern rust','Rust fungi produce raised orange, reddish or dark pustules on leaves and may reduce photosynthesis.','Check whether the spots rub off as powder, monitor spread and consult an extension officer about variety susceptibility and locally registered fungicides.','Do not apply household chemicals or unlabelled products.'],
      ['wilting','Wilting, yellowing or dying plants','Root/stalk rot, water stress or nutrient problem','Several causes can look similar; waterlogging, drought, damaged roots and fungal rots are possible.','Check soil moisture and drainage, inspect roots and lower stalks, improve drainage and obtain a field diagnosis if patches expand.','Do not apply fertilizer or pesticide until the likely cause is checked.'],
      ['holes','Holes, windowing or chewed leaves','Fall armyworm or other caterpillar damage','Larvae may feed inside the leaf funnel, leaving window panes, ragged holes and frass.','Open the leaf funnel and inspect for larvae/frass, scout the field regularly, use locally approved integrated pest management and follow label directions if treatment is advised.','Do not spray blindly or use unlabelled mixtures; protect pollinators and beneficial insects.'],
      ['poor_cobs','Poor cob filling or small cobs','Pollination stress, drought, nutrient deficiency or pest/disease damage','Poor kernel formation can follow moisture stress around flowering, poor pollination or nutrient problems.','Check planting date, soil moisture, plant population and soil fertility; seek soil testing or extension advice before applying nutrients.','Do not assume one single disease is responsible.']
    ]},
    beans: {label:'🫘 Beans', cases:[
      ['rust','Reddish-brown powdery spots','Bean rust','Rust pustules commonly develop on leaves and can cause premature leaf loss.','Remove heavily infected residues after harvest, rotate with non-host crops, use clean seed and ask an extension officer about resistant varieties and approved fungicides.','Avoid handling plants when wet and avoid unregistered sprays.'],
      ['angular_spots','Angular brown spots limited by leaf veins','Angular leaf spot or bacterial blight','Leaf lesions may be angular, sometimes with yellow margins; pods can also develop lesions.','Use certified disease-free seed, rotate crops, remove infected residues and avoid moving through wet foliage. Confirm whether symptoms are bacterial or fungal before treatment.','Do not use a fungicide as a substitute for diagnosis; many bacterial diseases are not controlled by fungicides.'],
      ['mosaic','Mottled light and dark green leaves','Bean common mosaic or other viral disease','Viral infection may cause mosaic patterns, leaf distortion and stunted plants.','Remove clearly affected plants where appropriate, control aphids and other vectors using integrated methods, and plant certified clean seed.','There is no curative spray for a virus; do not buy products claiming to cure viral infection.'],
      ['wilting','Yellowing and wilting from lower leaves','Root rot, Fusarium wilt or waterlogging','Root damage and soil-borne pathogens can restrict water uptake and cause yellowing or wilt.','Check roots and soil drainage, remove badly affected plants, rotate crops and seek diagnosis before choosing control measures.','Avoid planting beans repeatedly in the same affected plot.'],
      ['holes','Leaves with holes or rolled/tied leaflets','Bean fly, leaf miner or foliage-feeding insects','Insect feeding can reduce leaf area; bean fly larvae may damage stems and cause wilting.','Inspect stems and leaf undersides, use recommended planting dates and integrated pest management, and seek local advice on suitable controls.','Do not spray without identifying the pest and checking the product label.'],
      ['pod_spots','Dark spots or lesions on pods','Anthracnose or other pod disease','Pod lesions may affect seed quality and spread through infected seed or crop residues.','Use clean seed, rotate crops, remove infected residues and avoid working in wet crops.','Do not save seed from badly affected plants.']
    ]},
    tomatoes: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tomato"/></svg> Tomatoes', cases:[
      ['dark_blight','Dark water-soaked or expanding leaf lesions','Late blight or early blight (needs confirmation)','Blight diseases can spread quickly in wet conditions and may affect leaves, stems and fruit.','Remove badly affected plant material where safe, avoid overhead watering, improve spacing and seek prompt extension advice on locally approved control.','Do not compost heavily diseased material or mix pesticides without label approval.'],
      ['curling','Upward curling, yellowing or small leaves','Tomato yellow leaf curl virus, heat stress or herbicide injury','Leaf curl has several causes; viral disease may also cause stunting and reduced fruit set.','Inspect for whiteflies, remove severely affected plants if viral disease is confirmed, control weeds/volunteer hosts and use healthy seedlings.','Do not assume every curled leaf is a virus or apply insecticide without checking for pests.'],
      ['wilting','Whole plant wilts despite moist soil','Bacterial wilt, Fusarium wilt or root damage','Vascular disease can block water movement; plants may wilt rapidly and roots/stems may show discoloration.','Remove and isolate suspect plants, avoid moving contaminated soil/water, clean tools and request diagnosis.','Do not replant tomatoes or related crops in the same affected spot without rotation advice.'],
      ['fruit_rot','Fruit has sunken, soft or dark rotten patches','Blossom-end rot, anthracnose or fruit rot','Rot may result from calcium/water imbalance or infection; the pattern and fruit location matter.','Keep soil moisture even, mulch, avoid root damage, remove rotting fruit and seek advice if lesions spread.','Do not add calcium or spray fungicide without identifying the cause.'],
      ['holes','Leaves or fruit have holes and frass','Tuta absoluta, fruit borers or other caterpillars','Leaf mines, pinholes and fruit entry holes may indicate tomato leaf miner or fruit-feeding larvae.','Inspect leaves for mines and fruit for entry holes, remove infested parts, use traps and integrated pest management advice.','Avoid repeated broad-spectrum spraying that can kill beneficial insects.'],
      ['yellow_leaves','Older leaves yellow between veins','Nutrient deficiency, root stress or disease','Yellowing is not specific to one disease and may reflect nitrogen/magnesium deficiency, water stress or root problems.','Check irrigation, drainage and leaf pattern; use soil or plant testing and extension guidance before applying fertilizer.','Do not diagnose from leaf colour alone.']
    ]},
    potatoes: {label:'🥔 Irish potatoes', cases:[
      ['dark_blight','Dark irregular leaf lesions, sometimes white growth beneath','Late blight','Late blight can destroy foliage rapidly in cool, wet weather and infect tubers.','Act quickly: isolate the affected area, remove severely affected foliage as advised, avoid moving wet plants and contact an extension officer for locally approved management.','Do not store suspect tubers with healthy harvest or use unapproved chemicals.'],
      ['leaf_spots','Small dark spots with concentric rings','Early blight','Early blight often starts on older leaves and can cause yellowing and leaf drop.','Use clean seed tubers, rotate crops, remove volunteer potatoes and maintain balanced crop nutrition. Seek advice on registered products if needed.','Avoid continuous potato cropping in the same field.'],
      ['yellow_wilt','Yellowing, wilting and brown stem vessels','Bacterial wilt or other vascular disease','Bacterial wilt can spread through infected seed tubers, soil and contaminated water/tools.','Remove suspect plants and tubers, keep them separate, clean tools and seek confirmation from an agricultural officer.','Do not save seed tubers from affected plants or move soil from the affected area.'],
      ['tuber_rot','Tubers soft, wet, dry-rotten or foul-smelling','Bacterial soft rot, fungal dry rot or storage rot','Poor ventilation, injury and infection can cause tuber decay in the field or store.','Sort and remove rotten tubers, cure and store healthy tubers in a cool, dark, well-ventilated place.','Do not eat or feed badly rotted tubers to livestock.'],
      ['holes','Holes or tunnels in tubers','Potato tuber moth or soil pests','Larvae may tunnel into tubers in the field or store, making them vulnerable to rot.','Earth up properly, harvest on time, remove volunteer plants and store in insect-proof conditions. Ask for local pest advice.','Do not leave exposed tubers in the field or store infested tubers with clean stock.']
    ]},
    kale: {label:'🥬 Kale / Sukuma wiki', cases:[
      ['holes','Irregular holes and chewed leaves','Diamondback moth, cabbage worms or flea beetles','Caterpillars and beetles feed on leaves, reducing marketable yield.','Inspect leaf undersides, hand-pick caterpillars where practical, use netting and integrated pest management, and seek advice on approved controls.','Do not harvest within a pesticide pre-harvest interval.'],
      ['yellow_spots','Yellow patches or angular leaf spots','Downy mildew or leaf-spot disease','Fungal-like diseases can cause yellow patches, underside growth and leaf damage in humid conditions.','Improve spacing and airflow, water at the base, remove badly affected leaves and ask an extension officer to identify the disease.','Avoid keeping foliage wet for long periods.'],
      ['wilting','Plants wilt or collapse','Clubroot, bacterial wilt or root damage','Root disease and water stress can both cause wilting; swollen or distorted roots may suggest clubroot.','Inspect roots, improve drainage, remove affected plants and rotate with non-brassica crops if clubroot is suspected.','Do not move soil from affected beds to clean beds.'],
      ['aphids','Clusters of small insects and sticky or curled leaves','Aphid infestation','Aphids suck plant sap, distort new growth and may spread viruses.','Check leaf undersides, conserve natural enemies, remove heavily infested tips and seek integrated control advice.','Avoid unnecessary broad-spectrum insecticides.']
    ]},
    cabbage: {label:'🥬 Cabbage', cases:[
      ['holes','Large holes or caterpillars on leaves','Diamondback moth, cabbage webworm or cabbage looper','Leaf-feeding larvae can damage the growing point and reduce head formation.','Scout frequently, inspect leaf undersides, use physical exclusion where possible and follow local integrated pest management advice.','Do not spray without identifying the pest and observing harvest intervals.'],
      ['yellow_wilt','Yellowing, one-sided wilting or stunted plants','Fusarium yellows, clubroot or nutrient/water stress','Soil-borne disease and root damage can cause uneven growth and yellowing.','Inspect roots and field drainage, remove severely affected plants and rotate with non-brassica crops after professional advice.','Do not move contaminated soil or seedlings to clean fields.'],
      ['soft_rot','Head becomes soft, wet and foul-smelling','Bacterial soft rot','Bacteria enter through wounds and thrive in wet, poorly ventilated conditions.','Remove rotting heads, reduce injuries, improve drainage and airflow and sanitize tools.','Do not store or sell rotting heads with healthy produce.']
    ]},
    rice: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Rice', cases:[
      ['diamond_spots','Diamond-shaped lesions on leaves','Rice blast','Blast can affect leaves, nodes and panicles and may cause poor grain filling.','Use clean seed, balanced nitrogen, appropriate water management and resistant varieties recommended locally. Seek extension advice promptly.','Avoid excessive nitrogen and do not rely on an unverified pesticide.'],
      ['brown_spots','Brown spots on leaves or grain','Brown spot disease or nutrient stress','Brown spot may be associated with fungal infection and stressed or nutrient-deficient plants.','Check soil fertility and water management, use clean seed and request soil/plant diagnosis.','Do not treat nutrient problems with fungicide alone.'],
      ['dead_tillers','Deadheart or whitehead tillers','Stem borers','Larvae can bore into stems, killing central shoots or emptying panicles.','Monitor moths and egg masses, remove affected tillers where practical and use integrated pest management advice.','Avoid unnecessary insecticide applications that harm natural enemies.'],
      ['yellow_streaks','Yellowing or orange discoloration in patches','Bacterial leaf blight or nutrient/water issue','Bacterial leaf blight may cause yellow-to-white lesions and drying from leaf tips.','Use clean seed, avoid excessive nitrogen, maintain field hygiene and seek diagnosis if patches spread.','Do not move infected seedlings or irrigation water to clean plots.']
    ]},
    banana: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-banana"/></svg> Banana', cases:[
      ['yellow_wilt','Yellowing, wilting and splitting pseudostem','Fusarium wilt (Panama disease) or bacterial wilt','Serious wilt diseases can spread through infected planting material, tools and soil.','Stop sharing suckers from affected mats, disinfect tools, mark affected plants and contact county agricultural officers for diagnosis and containment.','Do not cut and distribute suckers from suspect plants.'],
      ['leaf_spots','Brown streaks or cigar-shaped leaf spots','Black Sigatoka or other leaf-spot disease','Leaf diseases reduce functional leaf area and can lower bunch weight.','Remove badly affected leaves appropriately, improve spacing and drainage and seek local disease-management advice.','Avoid moving infected leaves or planting material between farms.'],
      ['small_bunch','Small bunches or poor fruit development','Nutrient, water or weevil/root damage','Poor bunch development can result from low nutrients, drought, root damage or pests.','Check mulch, soil moisture, drainage, mat density and corm health; seek soil and pest assessment.','Do not apply fertilizer blindly without checking the field.'],
      ['holes','Holes or tunnels in corm/pseudostem','Banana weevil','Weevil larvae tunnel in corms and can weaken plants and reduce bunch production.','Use clean planting material, remove old residues, inspect corms and seek integrated pest management guidance.','Do not use damaged suckers as planting material.']
    ]},
    cassava: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-herbs"/></svg> Cassava', cases:[
      ['mosaic','Mosaic yellow-green leaf patterns and distortion','Cassava mosaic disease','A viral disease spread mainly through infected cuttings and whiteflies can reduce root yield.','Plant clean certified cuttings, remove severely affected plants and control volunteer cassava; seek extension advice.','Do not share cuttings from symptomatic plants.'],
      ['brown_streak','Yellowing and brown corky patches in roots','Cassava brown streak disease','The disease can cause internal root necrosis even when leaves look relatively normal.','Use clean planting material, inspect roots at harvest and obtain diagnosis; remove and destroy infected planting material according to local guidance.','Do not distribute cuttings from suspect plants.'],
      ['wilting','Sudden wilt, stem lesions or root rot','Bacterial blight or root rot','Bacterial and fungal diseases can cause leaf blight, stem dieback and root decay.','Use healthy cuttings, improve drainage, remove affected plants and rotate crops where advised.','Avoid moving infected stems and tools between fields.'],
      ['spots','Brown leaf spots and leaf drop','Leaf-spot disease','Fungal leaf spots may reduce leaf area, especially in humid conditions.','Use clean cuttings, maintain field hygiene and seek identification if defoliation becomes severe.','Do not spray before confirming the problem.']
    ]},
    onions: {label:'🧅 Onions', cases:[
      ['white_spots','Silvery-white streaks or patches on leaves','Thrips damage','Thrips scrape leaf surfaces, causing silvery patches, curling and reduced bulb size.','Inspect leaf folds, use field monitoring and integrated pest management and seek advice on approved control.','Avoid repeated use of one insecticide group.'],
      ['purple_spots','Purple or brown oval lesions on leaves','Purple blotch','Fungal lesions can expand and cause leaf dieback in humid conditions.','Use clean planting material, improve airflow, avoid prolonged leaf wetness and consult extension services.','Do not irrigate overhead late in the day.'],
      ['bulb_rot','Soft or mouldy bulbs','Neck rot or bacterial bulb rot','Infection may develop during wet conditions or poor curing and storage.','Harvest at maturity, cure bulbs thoroughly, handle gently and store in dry ventilated conditions.','Do not store damaged or rotten bulbs with healthy ones.']
    ]},
    sweet_potato: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tomato"/></svg> Sweet potato', cases:[
      ['yellow_wilt','Yellowing, wilting or poor vine growth','Virus complex, nutrient stress or root problems','Several viruses and growing conditions can cause stunting and leaf discoloration.','Use healthy vines, remove severely affected plants, control volunteer hosts and check soil moisture and fertility.','Do not keep planting vines from weak or symptomatic plants.'],
      ['holes','Holes, tunnels or bitter damaged roots','Sweet potato weevils','Weevil larvae tunnel in roots and vines and can cause severe quality loss.','Earth up to prevent exposed roots, harvest on time, rotate crops and destroy infested residues.','Do not store infested roots with healthy harvest.'],
      ['leaf_spots','Leaf spots or blighted patches','Leaf-spot disease','Fungal leaf diseases may cause spots and defoliation in wet conditions.','Improve airflow, remove badly affected residues and seek local identification if spread is rapid.','Avoid unnecessary pesticide use.']
    ]},
    sorghum: {label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Sorghum', cases:[
      ['red_leaf','Reddening, streaking or leaf yellowing','Sorghum diseases or nutrient/moisture stress','Reddening may be associated with disease, stress or nutrient imbalance.','Check field pattern, roots, moisture and insect presence; seek extension diagnosis before treatment.','Do not assume red leaves identify one disease.'],
      ['head_mould','Mouldy or discoloured grain heads','Grain moulds','Fungi may colonize grain heads in wet conditions, reducing quality and sometimes creating food-safety risks.','Harvest promptly when mature, dry grain thoroughly and store in clean, dry, ventilated conditions.','Do not consume visibly mouldy grain or feed it to livestock.'],
      ['holes','Dead central shoot or stem tunnelling','Stem borers','Larvae may tunnel inside stems and weaken plants or reduce grain formation.','Inspect stems, remove badly damaged residues after harvest and seek integrated pest-management advice.','Avoid unlabelled insecticides.']
    ]}
  },
  animal: {
    cattle:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg> Cattle',cases:[
      ['cough','Coughing, nasal discharge or fever','Respiratory infection complex, including pneumonia','Infections, dust, poor ventilation and other conditions can cause coughing and fever.','Separate sick animals where practical, improve ventilation, provide clean water and arrange veterinary examination, especially with fever or fast breathing.','Do not give human cough medicines or antibiotics without a veterinarian.'],
      ['breathing','Laboured or open-mouth breathing','Severe respiratory distress or other emergency','Breathing difficulty can become life-threatening and has several possible causes.','Seek veterinary help immediately, minimize handling and keep the animal calm in a well-ventilated place.','Do not force-feed, drench or transport an animal in severe distress unless advised.'],
      ['diarrhoea','Watery or bloody diarrhoea','Gastrointestinal infection, parasites, feed problem or calf scours','Fluid loss can cause rapid dehydration, especially in calves.','Provide clean water, isolate where possible, keep bedding clean and contact a vet promptly if blood, fever, weakness or persistent diarrhoea occurs.','Do not use random antibiotics or anti-diarrhoeal drugs.'],
      ['bloat','Left-side abdominal swelling, discomfort or repeated lying down','Ruminal bloat','Gas build-up can compress the lungs and become rapidly fatal.','Treat sudden abdominal swelling as an emergency and call a veterinarian immediately.','Do not attempt risky home procedures or force liquids into the animal.'],
      ['lameness','Limping, swollen foot or reluctance to stand','Foot rot, hoof injury or joint problem','Painful hoof or limb conditions can worsen and reduce feeding.','Keep the animal on dry, comfortable footing and arrange an examination; check the hoof only if safe.','Do not cut deeply into the hoof or apply caustic substances.'],
      ['udder','Hot, swollen udder or abnormal milk','Mastitis','Udder infection can cause pain, clots or watery milk and may affect the whole animal.','Use clean milking practices, separate abnormal milk from the food supply and arrange veterinary assessment and milk testing.','Do not sell or consume milk during treatment or withdrawal periods.'],
      ['wounds','Wounds, maggots or foul-smelling skin lesions','Wound infection or myiasis','Open wounds can become infected and attract flies.','Protect the animal from flies, keep the area clean and seek veterinary wound care, especially for deep wounds or maggots.','Do not pour fuel, used oil or harsh chemicals on wounds.'],
      ['abortion','Abortion, retained placenta or reproductive discharge','Reproductive infection or other pregnancy complication','Some causes can spread to people or other animals, including zoonotic infections.','Isolate the animal, use gloves, keep people away from aborted material and contact a veterinarian/public veterinary officer promptly.','Do not handle or dispose of aborted material casually; do not drink raw milk from suspect animals.']
    ]},
    goat:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-goat"/></svg> Goats',cases:[
      ['cough','Coughing, nasal discharge, fever or mouth sores','Peste des petits ruminants (PPR) or other respiratory disease','PPR is a serious contagious disease in goats and sheep; signs may include fever, mouth lesions, diarrhoea and pneumonia.','Isolate the animal, stop movement of the herd and contact a veterinary officer immediately if PPR is suspected.','Do not move, sell or mix sick animals; do not self-medicate.'],
      ['diarrhoea','Diarrhoea, weakness or soiling around tail','Worm burden, coccidiosis, PPR or feed-related illness','Young goats can dehydrate quickly; several diseases cause similar signs.','Provide clean water, keep kids warm and dry, isolate sick animals and arrange veterinary assessment and faecal testing.','Do not deworm repeatedly without weight-based veterinary advice.'],
      ['bloat','Swollen abdomen, discomfort or difficulty breathing','Bloat / ruminal tympany','Rapid gas build-up can be fatal.','Call a veterinarian urgently, especially if swelling is rapid or breathing is affected.','Do not force oil, water or other liquids into a distressed goat.'],
      ['skin','Intense itching, hair loss, scabs or crusts','Mange, lice, ticks or fungal skin disease','External parasites and contagious skin conditions can spread within the herd.','Separate affected animals, clean housing and seek identification and a correctly dosed treatment plan.','Do not apply engine oil, pesticides or human creams.'],
      ['lameness','Limping, swollen feet or foul smell between claws','Foot rot or hoof injury','Infection and overgrown hooves can cause pain and reduced feeding.','Keep the goat on dry ground and arrange hoof examination and treatment by a trained person.','Avoid cutting live tissue or sharing contaminated hoof tools.'],
      ['neurological','Staggering, circling, head pressing or seizures','Neurological emergency: poisoning, infection or metabolic disease','These signs can indicate serious and sometimes zoonotic conditions.','Keep people and other animals safe, avoid contact with saliva, isolate and call a veterinarian urgently.','Do not put hands in the mouth or attempt oral dosing.']
    ]},
    sheep:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-sheep"/></svg> Sheep',cases:[
      ['cough','Fever, nasal discharge, coughing or mouth sores','PPR or contagious respiratory disease','PPR can affect sheep and goats and may cause fever, mouth lesions, diarrhoea and pneumonia.','Isolate the flock, stop animal movement and contact a veterinary officer promptly if suspected.','Do not sell or move affected animals.'],
      ['diarrhoea','Diarrhoea, weakness or sudden weight loss','Worms, coccidiosis or infectious enteritis','Parasites and infections can cause poor condition and dehydration.','Provide clean water, isolate sick sheep and request faecal testing and a treatment plan.','Do not guess dewormer doses or use the same drug repeatedly without advice.'],
      ['skin','Wool loss, rubbing, scabs or intense itching','Sheep scab, lice or other skin parasites','Contagious parasites can spread through flock contact and shared equipment.','Separate affected sheep, avoid sharing equipment and seek veterinary diagnosis and flock-level control advice.','Do not use dips or pesticides at unlabelled concentrations.'],
      ['lameness','Limping, swollen feet or foul-smelling hoof','Foot rot or foot scald','Bacterial hoof infections spread in wet, muddy conditions.','Move affected sheep to dry footing and arrange hoof inspection and veterinary advice.','Do not trim aggressively or allow untreated sheep to mix with the flock.'],
      ['sudden_death','Sudden death, sometimes with bleeding or bloating','Possible anthrax or another notifiable disease','Sudden unexplained death may be a serious animal and public-health emergency.','Do not open the carcass or touch blood/tissues; keep people and animals away and contact veterinary authorities immediately.','Never butcher, eat, sell or move the carcass.']
    ]},
    poultry:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-chicken"/></svg> Poultry (chickens, layers and broilers)',cases:[
      ['breathing','Sneezing, coughing, gasping or swollen face','Newcastle disease, infectious bronchitis or other respiratory disease','Several contagious poultry diseases cause respiratory signs and egg-production drops.','Separate sick birds, improve ventilation, restrict visitors and contact a veterinary/livestock officer if many birds are affected.','Do not mix birds from different sources or use antibiotics without diagnosis.'],
      ['green_diarrhoea','Green, watery or bloody droppings','Newcastle disease, coccidiosis or other gut disease','Droppings alone cannot identify the cause; dehydration and rapid spread are concerns.','Provide clean water, isolate sick birds, clean drinkers and seek veterinary diagnosis, especially if blood or high mortality occurs.','Do not assume all diarrhoea is worms or administer random drugs.'],
      ['sudden_death','Sudden deaths or sharp fall in egg production','Possible Newcastle disease, avian influenza or other serious outbreak','Rapid deaths or unusual flock illness may require official investigation.','Stop movement of birds, eggs, manure and equipment; keep visitors away and contact veterinary authorities immediately.','Do not sell, slaughter, eat or transport sick/dead birds pending official advice.'],
      ['weakness','Ruffled feathers, drooping wings, weakness or not eating','Coccidiosis, parasites, nutritional or infectious disease','Weakness can have many causes and can progress quickly in young chicks.','Keep birds warm and dry, provide clean water and feed, isolate severely affected birds and request diagnosis.','Do not mix medications or vitamins at excessive concentrations.'],
      ['mites','Pale combs, feather damage, irritation or tiny crawling insects','Red mites, lice or other external parasites','External parasites can cause irritation, anaemia and reduced production.','Inspect birds and housing at night and during the day, clean housing and seek a poultry-safe control plan.','Never apply livestock dips or household insecticides directly to birds unless specifically labelled.'],
      ['lameness','Lameness, swollen joints or foot sores','Bumblefoot, joint infection or leg disorder','Foot injuries, infection and housing conditions can cause pain and reduced movement.','Provide dry, clean litter, check perches and flooring and seek veterinary assessment for swelling or severe pain.','Do not cut into swollen feet or give human painkillers.']
    ]},
    pig:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-pig"/></svg> Pigs',cases:[
      ['fever','High fever, red skin, weakness or sudden deaths','Possible African swine fever (ASF) or other serious infection','ASF is a highly contagious pig disease; fever, weakness, skin discoloration and deaths may occur.','Stop all movement of pigs, pork, feed equipment and visitors; isolate the unit and contact veterinary authorities immediately.','Do not slaughter, sell, eat or transport sick/dead pigs; do not attempt treatment for suspected ASF.'],
      ['diarrhoea','Watery or bloody diarrhoea, especially in piglets','Infectious enteritis, parasites or feed-related disease','Piglets can dehydrate rapidly and some infections spread quickly.','Keep piglets warm, provide clean water, improve hygiene and call a veterinarian promptly if blood, vomiting or deaths occur.','Do not use human medicines or random antibiotics.'],
      ['cough','Coughing, laboured breathing or poor growth','Enzootic pneumonia or other respiratory disease','Respiratory infections and poor ventilation can cause coughing and reduced weight gain.','Improve ventilation, reduce overcrowding and arrange veterinary examination if persistent or affecting several pigs.','Do not mix new pigs without quarantine.'],
      ['skin','Diamond-shaped red/purple skin patches or raised lesions','Erysipelas or other skin/systemic infection','Some bacterial infections cause skin lesions, fever and joint pain.','Isolate the pig and seek veterinary assessment quickly; use gloves when handling sick animals.','Do not handle or consume meat from a sick pig without veterinary/public-health clearance.'],
      ['lameness','Stiff walking, swollen joints or reluctance to stand','Joint infection, injury or nutritional problem','Painful joints may follow infection, injury or poor flooring.','Provide dry non-slip flooring, limit movement and arrange veterinary examination.','Do not give human painkillers or inject medicines without professional advice.']
    ]},
    rabbit:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-rabbit"/></svg> Rabbits',cases:[
      ['diarrhoea','Wet stool, soiling or sudden change in droppings','Enteritis, coccidiosis or diet-related digestive upset','Rabbits can deteriorate quickly from gut disease; true watery diarrhoea is urgent.','Seek veterinary help promptly, keep the rabbit warm and quiet, provide fresh water and its usual hay if it is eating.','Do not fast a rabbit or give human anti-diarrhoea medicine.'],
      ['not_eating','Not eating, few/no droppings or hunched posture','Gastrointestinal stasis or dental disease','A rabbit that stops eating can develop a life-threatening gut slowdown.','Treat as urgent: contact a rabbit-experienced veterinarian immediately and keep the animal calm.','Do not wait overnight or force-feed if obstruction/bloating is possible.'],
      ['sneezing','Sneezing, nasal discharge or noisy breathing','Respiratory infection or dental-related nasal disease','Respiratory infections can spread and may become chronic.','Separate from other rabbits, keep bedding dust-free and arrange veterinary examination.','Do not use human cold medicines or leftover antibiotics.'],
      ['head_tilt','Head tilt, rolling, loss of balance or seizures','Ear infection, E. cuniculi or neurological disease','Balance problems have several causes and require prompt assessment.','Keep the rabbit in a padded, low-sided safe space and contact a veterinarian urgently.','Do not handle roughly or attempt home ear treatments.'],
      ['skin','Crusty ears, itching, hair loss or scaly skin','Mites, ringworm or other skin disease','Some skin conditions spread to other animals and people.','Use gloves, separate affected rabbits and get veterinary diagnosis before treatment.','Do not apply dog/cat flea products unless a vet confirms they are safe for rabbits.']
    ]},
    fish:{label:'🐟 Farmed fish (tilapia/catfish)',cases:[
      ['surface_gasping','Fish gasping at the water surface','Low dissolved oxygen or gill disease','Low oxygen, high temperature, pollution or gill damage can cause surface gulping.','Increase aeration immediately, stop feeding temporarily, check water quality and contact an aquaculture officer if it continues.','Do not add chemicals or exchange water from an unsafe source without guidance.'],
      ['spots_ulcers','White spots, ulcers, sores or damaged fins','Parasites, bacterial infection or water-quality stress','Skin and gill problems may follow poor water quality or infectious agents.','Check water quality, remove dead fish promptly using safe hygiene, reduce stress and seek fish-health diagnosis.','Do not use human medicines or unapproved pond chemicals.'],
      ['not_feeding','Fish stop feeding or swim abnormally','Water-quality problem, stress or disease','Poor appetite is an early warning sign but does not identify one disease.','Check dissolved oxygen, temperature, pH and ammonia where possible; remove uneaten feed and seek aquaculture advice.','Do not increase feed or medicate the pond blindly.'],
      ['sudden_death','Many fish die suddenly','Oxygen crash, toxic contamination or infectious outbreak','Mass mortality may be caused by oxygen depletion, toxins or contagious disease.','Stop feeding, aerate, record water conditions and contact an aquaculture/veterinary officer urgently.','Do not sell or eat fish from a suspected chemical contamination or unexplained mass-death event until cleared.']
    ]},
    dogs:{label:'<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dog"/></svg> Dogs (farm/domestic)',cases:[
      ['vomiting','Repeated vomiting, diarrhoea or blood in stool','Gastrointestinal infection, poisoning or obstruction','Repeated vomiting or bloody diarrhoea can cause rapid dehydration and may be an emergency.','Keep the dog away from suspected toxins, provide access to water if alert and contact a veterinarian urgently.','Do not induce vomiting or give human medicines unless instructed by a vet.'],
      ['cough','Persistent cough, nasal discharge or fever','Kennel cough, pneumonia or other respiratory illness','Respiratory infections can spread between dogs and may become severe.','Separate from other dogs and arrange veterinary assessment, especially with fever or breathing changes.','Do not give human cough syrups.'],
      ['skin','Itching, hair loss, scabs or wounds','Mange, fleas, ticks, ringworm or allergy','Some causes are contagious to animals or people.','Use gloves, limit contact with other animals and seek diagnosis and a dog-safe treatment plan.','Do not use livestock pesticides or random home remedies.'],
      ['weakness','Weakness, pale gums, collapse or seizures','Poisoning, tick-borne disease, anaemia or other emergency','Collapse, seizures or pale gums can signal life-threatening illness.','Seek emergency veterinary care; take packaging of any suspected toxin without touching the substance.','Do not wait for symptoms to pass or give food/water to an unconscious dog.']
    ]}
  }
};

let selectedMode = '';
function selectMode(mode) {
  selectedMode = mode;
  document.getElementById('helperCard').style.display = 'block';
  document.getElementById('cropMode').classList.toggle('active', mode === 'crop');
  document.getElementById('animalMode').classList.toggle('active', mode === 'animal');
  document.getElementById('helperTitle').textContent = mode === 'crop' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Crop Health Check' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg> Animal Health Check';
  document.getElementById('cropGroup').style.display = mode === 'crop' ? 'flex' : 'none';
  document.getElementById('animalGroup').style.display = mode === 'animal' ? 'flex' : 'none';
  document.getElementById('result').style.display = 'none';
  document.getElementById('crop').value = '';
  document.getElementById('animal').value = '';
  resetSymptoms();
  document.getElementById('symptomHint').textContent = mode === 'crop' ? 'Choose a crop to see crop-specific symptoms only.' : 'Choose an animal to see animal-specific symptoms only.';
}
function resetSymptoms() {
  const sel = document.getElementById('symptom');
  sel.innerHTML = '<option value="">First select a crop or animal</option>';
  sel.disabled = true;
}
function populateSymptoms() {
  const group = selectedMode === 'crop' ? document.getElementById('crop').value : document.getElementById('animal').value;
  const sel = document.getElementById('symptom');
  sel.innerHTML = '<option value="">Choose a symptom</option>';
  const item = group && healthCatalog[selectedMode][group];
  if (!item) { resetSymptoms(); return; }
  item.cases.forEach(c => {
    const opt = document.createElement('option'); opt.value = c[0]; opt.textContent = c[1]; sel.appendChild(opt);
  });
  sel.disabled = false;
  document.getElementById('symptomHint').textContent = item.label + ': select the sign closest to what you observe. More than one condition can cause similar signs.';
  document.getElementById('result').style.display = 'none';
}
function analyzeHealth() {
  if (!selectedMode) { alert('Choose Crop Health or Animal Health first.'); return; }
  const group = selectedMode === 'crop' ? document.getElementById('crop').value : document.getElementById('animal').value;
  const symptom = document.getElementById('symptom').value;
  if (!group) { alert(selectedMode === 'crop' ? 'Please select a crop.' : 'Please select an animal.'); return; }
  if (!symptom) { alert('Please select the main symptom.'); return; }
  const item = healthCatalog[selectedMode][group];
  const c = item.cases.find(x => x[0] === symptom);
  if (!c) { alert('Please select a symptom from the displayed list.'); return; }
  showResult({title:c[2], type:item.label, severity:severityFor(c[0], selectedMode), signs:c[3], concern:c[2] + '. ' + c[3], action:c[4], avoid:c[5]});
}
function severityFor(symptom, mode) {
  const urgent = ['breathing','bloat','sudden_death','fever','not_eating','head_tilt','abortion','neurological','surface_gasping'];
  const high = ['yellow_streaks','dark_blight','wilting','mosaic','brown_streak','yellow_wilt','fruit_rot','poor_cobs','green_diarrhoea','diarrhoea','vomiting','weakness','udder','skin'];
  if (urgent.includes(symptom)) return 'Urgent — seek professional help promptly';
  if (high.includes(symptom)) return 'High — arrange timely assessment';
  return 'Moderate — monitor and seek advice if it persists or spreads';
}
function showResult(data) {
  document.getElementById('resultTitle').textContent = data.title;
  document.getElementById('resultType').textContent = data.type + ' • Possible concern, not a confirmed diagnosis';
  document.getElementById('severity').textContent = data.severity;
  document.getElementById('signs').textContent = data.signs;
  document.getElementById('concern').textContent = data.concern;
  document.getElementById('action').textContent = data.action;
  document.getElementById('avoid').textContent = data.avoid;
  document.getElementById('result').style.display = 'block';
  document.getElementById('result').scrollIntoView({behavior:'smooth', block:'start'});
}
function resetHelper() {
  document.getElementById('result').style.display = 'none';
  document.getElementById('crop').value = '';
  document.getElementById('animal').value = '';
  resetSymptoms();
  document.getElementById('symptomHint').textContent = 'Choose a crop or animal to load its relevant symptoms.';
}
document.getElementById('crop').addEventListener('change', () => { if (selectedMode === 'crop') populateSymptoms(); });
document.getElementById('animal').addEventListener('change', () => { if (selectedMode === 'animal') populateSymptoms(); });
</script>

</body>

</html>