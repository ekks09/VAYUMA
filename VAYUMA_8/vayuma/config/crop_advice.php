<?php
/**
 * VAYUMA Crop Advice Database
 *
 * Central crop catalogue used by USSD/SMS crop advice.
 * This allows the crop list to be expanded without
 * rewriting the USSD logic.
 */

return [

    // =========================================================
    // CEREALS
    // =========================================================
    'cereals' => [

        1 => [
            'name' => 'Maize',
            'water' => 'Moderate',
            'climate' => 'Warm to moderate',
            'market' => 'High',
            'advice' => 'Plant certified seed suited to your area. Maintain good spacing, control weeds early and monitor for fall armyworm and other pests.',
            'weather' => 'Maize generally performs well with adequate rainfall and moderate temperatures. Avoid waterlogging.'
        ],

        2 => [
            'name' => 'Wheat',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use certified seed, prepare the soil well and monitor for rust diseases and other pests.',
            'weather' => 'Wheat generally prefers cooler conditions and adequate moisture during establishment.'
        ],

        3 => [
            'name' => 'Rice',
            'water' => 'High',
            'climate' => 'Warm',
            'market' => 'High',
            'advice' => 'Use suitable rice varieties and maintain proper water management. Monitor weeds, pests and diseases.',
            'weather' => 'Rice requires reliable water availability. Irrigated production can reduce dependence on rainfall.'
        ],

        4 => [
            'name' => 'Sorghum',
            'water' => 'Low',
            'climate' => 'Warm and relatively dry',
            'market' => 'Good',
            'advice' => 'Choose a variety suitable for your area and maintain proper plant spacing. Monitor birds and pests.',
            'weather' => 'Sorghum is relatively drought tolerant and can be useful in areas with unreliable rainfall.'
        ],

        5 => [
            'name' => 'Millet',
            'water' => 'Low',
            'climate' => 'Warm and dry',
            'market' => 'Good',
            'advice' => 'Use locally suitable varieties, control weeds early and protect the crop from birds.',
            'weather' => 'Millet can tolerate relatively dry conditions better than many cereals.'
        ],

        6 => [
            'name' => 'Barley',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use suitable certified seed and monitor the crop for fungal diseases and nutrient deficiencies.',
            'weather' => 'Barley generally performs better under cooler growing conditions.'
        ],
    ],


    // =========================================================
    // LEGUMES
    // =========================================================
    'legumes' => [

        1 => [
            'name' => 'Beans',
            'water' => 'Low to Moderate',
            'climate' => 'Moderate',
            'market' => 'High',
            'advice' => 'Use clean certified seed, maintain proper spacing and control weeds early. Monitor for bean diseases and pests.',
            'weather' => 'Beans need adequate moisture but excessive rain and waterlogging can increase disease problems.'
        ],

        2 => [
            'name' => 'Green Grams',
            'water' => 'Low',
            'climate' => 'Warm and relatively dry',
            'market' => 'Good',
            'advice' => 'Plant quality seed, control weeds early and harvest promptly when pods mature.',
            'weather' => 'Green grams can perform well in relatively dry areas when moisture is available during establishment.'
        ],

        3 => [
            'name' => 'Cowpeas',
            'water' => 'Low',
            'climate' => 'Warm and dry',
            'market' => 'Good',
            'advice' => 'Choose varieties adapted to your area and monitor for pests, especially during flowering and pod formation.',
            'weather' => 'Cowpeas are relatively tolerant of dry conditions.'
        ],

        4 => [
            'name' => 'Pigeon Peas',
            'water' => 'Low',
            'climate' => 'Warm',
            'market' => 'Good',
            'advice' => 'Use suitable varieties and maintain good weed control. Monitor for pod pests.',
            'weather' => 'Pigeon peas are relatively drought tolerant once established.'
        ],

        5 => [
            'name' => 'Peas',
            'water' => 'Moderate',
            'climate' => 'Cool',
            'market' => 'Good',
            'advice' => 'Use suitable varieties, provide support where necessary and monitor for fungal diseases.',
            'weather' => 'Peas generally prefer cooler growing conditions.'
        ],

        6 => [
            'name' => 'Chickpeas',
            'water' => 'Low',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use suitable seed and avoid excessive moisture. Monitor for pests and diseases.',
            'weather' => 'Chickpeas generally perform better under relatively dry conditions.'
        ],
    ],


    // =========================================================
    // ROOTS AND TUBERS
    // =========================================================
    'roots_tubers' => [

        1 => [
            'name' => 'Potatoes',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use certified seed potatoes, prepare well-drained soil and monitor for late blight and pests.',
            'weather' => 'Potatoes generally prefer cooler conditions and well-drained soils.'
        ],

        2 => [
            'name' => 'Sweet Potatoes',
            'water' => 'Low to Moderate',
            'climate' => 'Warm',
            'market' => 'Good',
            'advice' => 'Use healthy planting material and maintain good weed control. Harvest according to variety maturity.',
            'weather' => 'Sweet potatoes can tolerate relatively dry conditions once established.'
        ],

        3 => [
            'name' => 'Cassava',
            'water' => 'Low',
            'climate' => 'Warm',
            'market' => 'Good',
            'advice' => 'Use healthy disease-free planting material and monitor for cassava pests and diseases.',
            'weather' => 'Cassava is relatively tolerant of dry conditions once established.'
        ],

        4 => [
            'name' => 'Arrowroots',
            'water' => 'High',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Provide adequate moisture and fertile soil. Monitor the crop regularly for pests and diseases.',
            'weather' => 'Arrowroots perform better where sufficient moisture is available.'
        ],
    ],


    // =========================================================
    // VEGETABLES
    // =========================================================
    'vegetables' => [

        1 => [
            'name' => 'Tomatoes',
            'water' => 'High',
            'climate' => 'Warm to moderate',
            'market' => 'Very High',
            'advice' => 'Use healthy seedlings, maintain consistent watering and monitor for blight, bacterial diseases and insect pests.',
            'weather' => 'Tomatoes require reliable moisture, but excessive rainfall can increase disease pressure.'
        ],

        2 => [
            'name' => 'Onions',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'High',
            'advice' => 'Use suitable varieties, maintain good spacing and manage weeds carefully. Reduce excess moisture near maturity.',
            'weather' => 'Onions need adequate moisture during growth but relatively dry conditions are useful during maturity and curing.'
        ],

        3 => [
            'name' => 'Cabbage',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'High',
            'advice' => 'Use healthy seedlings, maintain proper spacing and monitor for caterpillars, aphids and diseases.',
            'weather' => 'Cabbage generally prefers moderate to cool growing conditions.'
        ],

        4 => [
            'name' => 'Kale',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'High',
            'advice' => 'Maintain regular watering, control weeds and inspect leaves regularly for pests.',
            'weather' => 'Kale performs well under moderate temperatures with adequate moisture.'
        ],

        5 => [
            'name' => 'Spinach',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use fertile soil, maintain regular moisture and harvest leaves appropriately.',
            'weather' => 'Spinach generally prefers cooler conditions and adequate moisture.'
        ],

        6 => [
            'name' => 'Carrots',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use loose well-prepared soil, maintain even moisture and control weeds.',
            'weather' => 'Carrots generally prefer moderate to cool conditions and consistent moisture.'
        ],

        7 => [
            'name' => 'Capsicum',
            'water' => 'Moderate',
            'climate' => 'Warm',
            'market' => 'High',
            'advice' => 'Use healthy seedlings, provide adequate water and monitor for aphids, thrips and fungal diseases.',
            'weather' => 'Capsicum generally performs well in warm conditions with reliable moisture.'
        ],

        8 => [
            'name' => 'French Beans',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'High',
            'advice' => 'Use quality seed, maintain proper spacing and monitor closely for pests and diseases.',
            'weather' => 'French beans perform best with moderate temperatures and reliable moisture.'
        ],
    ],


    // =========================================================
    // FRUITS
    // =========================================================
    'fruits' => [

        1 => [
            'name' => 'Avocado',
            'water' => 'Moderate',
            'climate' => 'Moderate',
            'market' => 'High',
            'advice' => 'Plant healthy grafted seedlings suited to your area. Ensure good drainage and monitor for pests and diseases.',
            'weather' => 'Avocado requires adequate moisture but performs poorly in waterlogged soils.'
        ],

        2 => [
            'name' => 'Mango',
            'water' => 'Moderate',
            'climate' => 'Warm',
            'market' => 'High',
            'advice' => 'Use healthy grafted seedlings, maintain good orchard hygiene and prune appropriately.',
            'weather' => 'Mango generally performs well in warm areas with suitable rainfall patterns.'
        ],

        3 => [
            'name' => 'Banana',
            'water' => 'High',
            'climate' => 'Warm and humid',
            'market' => 'High',
            'advice' => 'Maintain adequate moisture, use healthy planting material and remove diseased leaves and plants.',
            'weather' => 'Bananas require reliable moisture and perform poorly under prolonged drought.'
        ],

        4 => [
            'name' => 'Passion Fruit',
            'water' => 'Moderate',
            'climate' => 'Moderate',
            'market' => 'High',
            'advice' => 'Use healthy seedlings, provide strong support structures and monitor for diseases and pests.',
            'weather' => 'Passion fruit requires adequate moisture but good drainage is important.'
        ],

        5 => [
            'name' => 'Pineapple',
            'water' => 'Moderate',
            'climate' => 'Warm',
            'market' => 'Good',
            'advice' => 'Use healthy planting material, maintain weed control and avoid waterlogged soils.',
            'weather' => 'Pineapple can tolerate relatively dry periods but requires moisture for good establishment and growth.'
        ],

        6 => [
            'name' => 'Watermelon',
            'water' => 'Moderate to High',
            'climate' => 'Warm',
            'market' => 'High',
            'advice' => 'Use quality seed, provide adequate moisture during establishment and fruit development and monitor pests.',
            'weather' => 'Watermelon prefers warm conditions. Excessive rainfall can increase disease risk.'
        ],

        7 => [
            'name' => 'Citrus',
            'water' => 'Moderate',
            'climate' => 'Warm to moderate',
            'market' => 'Good',
            'advice' => 'Use healthy grafted planting material and maintain orchard hygiene. Monitor for pests and diseases.',
            'weather' => 'Citrus requires adequate moisture and well-drained soil.'
        ],
    ],


    // =========================================================
    // CASH AND INDUSTRIAL CROPS
    // =========================================================
    'cash_industrial' => [

        1 => [
            'name' => 'Tea',
            'water' => 'High',
            'climate' => 'Cool and humid',
            'market' => 'High',
            'advice' => 'Use varieties suitable for your area, maintain soil fertility and manage weeds carefully.',
            'weather' => 'Tea generally requires reliable rainfall and humid growing conditions.'
        ],

        2 => [
            'name' => 'Coffee',
            'water' => 'Moderate',
            'climate' => 'Moderate',
            'market' => 'High',
            'advice' => 'Use healthy planting material, maintain soil fertility and monitor for coffee pests and diseases.',
            'weather' => 'Coffee requires suitable temperatures and adequate moisture without prolonged waterlogging.'
        ],

        3 => [
            'name' => 'Sugarcane',
            'water' => 'High',
            'climate' => 'Warm',
            'market' => 'High',
            'advice' => 'Use healthy planting material, maintain soil fertility and manage weeds during establishment.',
            'weather' => 'Sugarcane requires substantial water during its growing period.'
        ],

        4 => [
            'name' => 'Sunflower',
            'water' => 'Low to Moderate',
            'climate' => 'Warm',
            'market' => 'Good',
            'advice' => 'Use suitable varieties, maintain proper spacing and monitor for birds and pests.',
            'weather' => 'Sunflower is relatively tolerant of moderate dry conditions once established.'
        ],

        5 => [
            'name' => 'Cotton',
            'water' => 'Moderate',
            'climate' => 'Warm',
            'market' => 'Good',
            'advice' => 'Use suitable varieties and follow recommended pest-management practices.',
            'weather' => 'Cotton generally prefers warm conditions and adequate moisture during establishment.'
        ],

        6 => [
            'name' => 'Pyrethrum',
            'water' => 'Moderate',
            'climate' => 'Cool to moderate',
            'market' => 'Good',
            'advice' => 'Use suitable planting material and maintain good weed and pest management.',
            'weather' => 'Pyrethrum generally performs better in cooler highland conditions.'
        ],
    ],
];