<?php
session_start();
require_once __DIR__ . '/includes/language.php';

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

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Budget Planner | VAYUMA</title>

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
<?php vayuma_language_selector(); ?>

<header class="header">

    <h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> VAYUMA</h1>

    <p>Smart Farming Ecosystem</p>

</header>


<main class="container">


    <!-- ==============================
         TITLE
    ============================== -->

    <div class="page-title">

        <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg> Budget Planner</h2>

        <p>

            Hello
            <?php echo htmlspecialchars($full_name); ?>.

            Plan your farm expenses, estimate your revenue
            and understand your expected profit before selling.

        </p>

    </div>


    <div class="main-grid">


        <!-- ==============================
             FARM INFORMATION
        ============================== -->

        <section class="va-profile-card">

            <h3><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Farm Information</h3>


            <div class="va-profile-form-group">

                <label for="crop">
                    Crop / Enterprise
                </label>

                <select class="va-profile-input" id="crop">

                    <option value="">
                        Select crop
                    </option>

                    <option value="Maize">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-maize"/></svg> Maize
                    </option>

                    <option value="Beans">
                        🫘 Beans
                    </option>

                    <option value="Tomatoes">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tomato"/></svg> Tomatoes
                    </option>

                    <option value="Potatoes">
                        🥔 Potatoes
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>


            <div class="va-profile-form-group">

                <label for="farmSize">
                    Farm Size (acres)
                </label>

                <input
                    type="number"
                    id="farmSize"
                    min="0"
                    step="0.1"
                    placeholder="e.g. 2"
                >

            </div>


            <div class="va-profile-form-group">

                <label for="expectedYield">
                    Expected Yield (kg)
                </label>

                <input
                    type="number"
                    id="expectedYield"
                    min="0"
                    step="0.1"
                    placeholder="e.g. 1000"
                >

            </div>


            <div class="va-profile-form-group">

                <label for="pricePerKg">
                    Expected Selling Price (KSh/kg)
                </label>

                <input
                    type="number"
                    id="pricePerKg"
                    min="0"
                    step="0.01"
                    placeholder="e.g. 80"
                >

            </div>


        </section>


        <!-- ==============================
             EXPENSES
        ============================== -->

        <section class="va-profile-card">

            <h3><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg> Farm Expenses</h3>


            <div class="expense-row">

                <input
                    type="text"
                    value="Seeds"
                    readonly
                >

                <input
                    type="number"
                    id="seeds"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <div class="expense-row">

                <input
                    type="text"
                    value="Fertilizer"
                    readonly
                >

                <input
                    type="number"
                    id="fertilizer"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <div class="expense-row">

                <input
                    type="text"
                    value="Pesticides"
                    readonly
                >

                <input
                    type="number"
                    id="pesticides"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <div class="expense-row">

                <input
                    type="text"
                    value="Labour"
                    readonly
                >

                <input
                    type="number"
                    id="labour"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <div class="expense-row">

                <input
                    type="text"
                    value="Transport"
                    readonly
                >

                <input
                    type="number"
                    id="transport"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <div class="expense-row">

                <input
                    type="text"
                    value="Irrigation"
                    readonly
                >

                <input
                    type="number"
                    id="irrigation"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <div class="expense-row">

                <input
                    type="text"
                    value="Other"
                    readonly
                >

                <input
                    type="number"
                    id="other"
                    min="0"
                    placeholder="KSh"
                >

            </div>


            <button
                type="button"
                class="calculate-button"
                onclick="calculateBudget()"
            >

                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Calculate Farm Budget

            </button>

        </section>

    </div>


    <!-- ==============================
         RESULTS
    ============================== -->

    <section
        class="card results"
        id="results"
        style="display:none;"
    >

        <h3><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Your Farm Budget</h3>


        <div class="result-grid">


            <div class="result-box">

                <h4>
                    Total Expenses
                </h4>

                <strong id="totalExpenses">
                    KSh 0
                </strong>

            </div>


            <div class="result-box">

                <h4>
                    Expected Revenue
                </h4>

                <strong id="revenue">
                    KSh 0
                </strong>

            </div>


        </div>


        <div
            class="profit-box"
            id="profitBox"
        >

            <h3>
                Estimated Profit
            </h3>

            <div
                class="profit-value"
                id="profit"
            >
                KSh 0
            </div>

            <p id="margin">
                Profit Margin: 0%
            </p>

        </div>


        <!-- ==============================
             BREAKDOWN
        ============================== -->

        <div class="breakdown">

            <h3>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Expense Breakdown
            </h3>


            <div class="breakdown-item">

                <span>
                    Seeds
                </span>

                <strong id="showSeeds">
                    KSh 0
                </strong>

            </div>


            <div class="breakdown-item">

                <span>
                    Fertilizer
                </span>

                <strong id="showFertilizer">
                    KSh 0
                </strong>

            </div>


            <div class="breakdown-item">

                <span>
                    Pesticides
                </span>

                <strong id="showPesticides">
                    KSh 0
                </strong>

            </div>


            <div class="breakdown-item">

                <span>
                    Labour
                </span>

                <strong id="showLabour">
                    KSh 0
                </strong>

            </div>


            <div class="breakdown-item">

                <span>
                    Transport
                </span>

                <strong id="showTransport">
                    KSh 0
                </strong>

            </div>


            <div class="breakdown-item">

                <span>
                    Irrigation
                </span>

                <strong id="showIrrigation">
                    KSh 0
                </strong>

            </div>


            <div class="breakdown-item">

                <span>
                    Other
                </span>

                <strong id="showOther">
                    KSh 0
                </strong>

            </div>

        </div>


        <div class="tip" id="budgetTip">

            💡 <strong>VAYUMA Tip:</strong>

            Keep your farm expenses below your expected
            revenue and regularly update your budget as
            market prices change.

        </div>

    </section>


    <a
        href="farmer_dashboard.php"
        class="back-button"
    >

        ← Back to Farmer Dashboard

    </a>


</main>


<script>

/* =========================================================
   FORMAT MONEY
========================================================= */

function formatMoney(amount) {

    return "KSh " +
        amount.toLocaleString(
            "en-KE",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


/* =========================================================
   GET VALUE
========================================================= */

function getValue(id) {

    const value =
        parseFloat(
            document.getElementById(id).value
        );

    return isNaN(value) ? 0 : value;

}


/* =========================================================
   CALCULATE BUDGET
========================================================= */

function calculateBudget() {


    /* ==========================
       FARM INFORMATION
    ========================== */

    const crop =
        document.getElementById("crop").value;


    const farmSize =
        getValue("farmSize");


    const expectedYield =
        getValue("expectedYield");


    const pricePerKg =
        getValue("pricePerKg");


    if (crop === "") {

        alert(
            "Please select your crop or enterprise."
        );

        return;

    }


    if (expectedYield <= 0) {

        alert(
            "Please enter the expected yield."
        );

        return;

    }


    if (pricePerKg <= 0) {

        alert(
            "Please enter the expected selling price."
        );

        return;

    }


    /* ==========================
       EXPENSES
    ========================== */

    const seeds =
        getValue("seeds");


    const fertilizer =
        getValue("fertilizer");


    const pesticides =
        getValue("pesticides");


    const labour =
        getValue("labour");


    const transport =
        getValue("transport");


    const irrigation =
        getValue("irrigation");


    const other =
        getValue("other");


    /* ==========================
       TOTAL EXPENSES
    ========================== */

    const totalExpenses =

        seeds +
        fertilizer +
        pesticides +
        labour +
        transport +
        irrigation +
        other;


    /* ==========================
       REVENUE
    ========================== */

    const revenue =
        expectedYield * pricePerKg;


    /* ==========================
       PROFIT
    ========================== */

    const profit =
        revenue - totalExpenses;


    /* ==========================
       PROFIT MARGIN
    ========================== */

    let margin = 0;


    if (revenue > 0) {

        margin =
            (profit / revenue) * 100;

    }


    /* ==========================
       DISPLAY TOTALS
    ========================== */

    document.getElementById(
        "totalExpenses"
    ).textContent =
        formatMoney(totalExpenses);


    document.getElementById(
        "revenue"
    ).textContent =
        formatMoney(revenue);


    document.getElementById(
        "profit"
    ).textContent =
        formatMoney(profit);


    document.getElementById(
        "margin"
    ).textContent =
        "Profit Margin: " +
        margin.toFixed(1) +
        "%";


    /* ==========================
       EXPENSE BREAKDOWN
    ========================== */

    document.getElementById(
        "showSeeds"
    ).textContent =
        formatMoney(seeds);


    document.getElementById(
        "showFertilizer"
    ).textContent =
        formatMoney(fertilizer);


    document.getElementById(
        "showPesticides"
    ).textContent =
        formatMoney(pesticides);


    document.getElementById(
        "showLabour"
    ).textContent =
        formatMoney(labour);


    document.getElementById(
        "showTransport"
    ).textContent =
        formatMoney(transport);


    document.getElementById(
        "showIrrigation"
    ).textContent =
        formatMoney(irrigation);


    document.getElementById(
        "showOther"
    ).textContent =
        formatMoney(other);


    /* ==========================
       PROFIT / LOSS STYLE
    ========================== */

    const profitBox =
        document.getElementById(
            "profitBox"
        );


    if (profit < 0) {

        profitBox.classList.add("loss");

        document.getElementById(
            "budgetTip"
        ).innerHTML =

            "<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <strong>VAYUMA Warning:</strong> " +

            "Your estimated expenses are higher than " +

            "your expected revenue. Consider reviewing " +

            "your input costs, expected yield or selling price.";

    }

    else if (profit === 0) {

        profitBox.classList.remove("loss");

        document.getElementById(
            "budgetTip"
        ).innerHTML =

            "⚖️ <strong>VAYUMA Notice:</strong> " +

            "Your estimated revenue equals your expenses. " +

            "There is currently no estimated profit.";

    }

    else {

        profitBox.classList.remove("loss");

        document.getElementById(
            "budgetTip"
        ).innerHTML =

            "💡 <strong>VAYUMA Tip:</strong> " +

            "Your current plan shows an estimated profit. " +

            "Continue monitoring input costs and market " +

            "prices because actual results may change.";

    }


    /* ==========================
       SHOW RESULTS
    ========================== */

    document.getElementById(
        "results"
    ).style.display =
        "block";


    document.getElementById(
        "results"
    ).scrollIntoView({
        behavior: "smooth"
    });

}

</script>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>