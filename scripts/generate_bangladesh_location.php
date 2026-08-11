<?php

/**
 * Generate bondhon_backend/database/data/options/locations/bangladesh.json
 * from Open Admin Data hierarchy (CC-BY-4.0).
 *
 * Source: https://github.com/open-admin-data/bangladesh-administrative-divisions
 */

$sourcePath = __DIR__.'/../database/data/sources/bangladesh-hierarchy.json';
$outputPath = __DIR__.'/../database/data/options/locations/bangladesh.json';

if (! file_exists($sourcePath)) {
    fwrite(STDERR, "Source file not found: {$sourcePath}\n");
    exit(1);
}

$payload = json_decode(file_get_contents($sourcePath), true, 512, JSON_THROW_ON_ERROR);
$divisions = $payload['data'] ?? [];

function slugify(string $label): string
{
    $slug = strtolower($label);
    $slug = str_replace(["'", '.', '(', ')', '/', '&', ',', '–', '—'], '', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);

    return trim($slug, '_');
}

/** Preserve legacy values already stored on profiles. */
function legacyDivisionValue(string $englishName): string
{
    return match ($englishName) {
        'Barishal' => 'barisal',
        'Chattogram' => 'chittagong',
        default => slugify($englishName),
    };
}

/** @return array<string, string> */
function legacyDistrictAliases(): array
{
    return [
        'Bogura' => 'bogra',
        'Cumilla' => 'comilla',
        "Cox's Bazar" => 'coxs_bazar',
        'Chapainawabganj' => 'chapai_nawabganj',
        'Jashore' => 'jessore',
        'Barishal' => 'barisal',
        'Chattogram' => 'chittagong',
        'Jhenaidah' => 'jhenaidah',
        'Netrokona' => 'netrokona',
        'Moulvibazar' => 'moulvibazar',
        'Habiganj' => 'habiganj',
        'Sunamganj' => 'sunamganj',
        'Narayanganj' => 'narayanganj',
        'Narsingdi' => 'narsingdi',
        'Kishoreganj' => 'kishoreganj',
        'Gopalganj' => 'gopalganj',
        'Madaripur' => 'madaripur',
        'Shariatpur' => 'shariatpur',
        'Manikganj' => 'manikganj',
        'Munshiganj' => 'munshiganj',
        'Faridpur' => 'faridpur',
        'Rajbari' => 'rajbari',
        'Gazipur' => 'gazipur',
        'Tangail' => 'tangail',
        'Dhaka' => 'dhaka',
        'Bandarban' => 'bandarban',
        'Brahmanbaria' => 'brahmanbaria',
        'Chandpur' => 'chandpur',
        'Feni' => 'feni',
        'Khagrachhari' => 'khagrachhari',
        'Lakshmipur' => 'lakshmipur',
        'Noakhali' => 'noakhali',
        'Rangamati' => 'rangamati',
        'Joypurhat' => 'joypurhat',
        'Naogaon' => 'naogaon',
        'Natore' => 'natore',
        'Pabna' => 'pabna',
        'Rajshahi' => 'rajshahi',
        'Sirajganj' => 'sirajganj',
        'Bagerhat' => 'bagerhat',
        'Chuadanga' => 'chuadanga',
        'Khulna' => 'khulna',
        'Kushtia' => 'kushtia',
        'Magura' => 'magura',
        'Meherpur' => 'meherpur',
        'Narail' => 'narail',
        'Satkhira' => 'satkhira',
        'Barguna' => 'barguna',
        'Bhola' => 'bhola',
        'Jhalokati' => 'jhalokati',
        'Patuakhali' => 'patuakhali',
        'Pirojpur' => 'pirojpur',
        'Sylhet' => 'sylhet',
        'Dinajpur' => 'dinajpur',
        'Gaibandha' => 'gaibandha',
        'Kurigram' => 'kurigram',
        'Lalmonirhat' => 'lalmonirhat',
        'Nilphamari' => 'nilphamari',
        'Panchagarh' => 'panchagarh',
        'Rangpur' => 'rangpur',
        'Thakurgaon' => 'thakurgaon',
        'Jamalpur' => 'jamalpur',
        'Mymensingh' => 'mymensingh',
        'Sherpur' => 'sherpur',
    ];
}

function districtValue(string $englishName, array $aliases): string
{
    return $aliases[$englishName] ?? slugify($englishName);
}

function upazilaValue(string $englishName, string $districtValue): string
{
    $base = slugify($englishName);

    return $base !== '' ? $base : $districtValue.'_upazila';
}

$districtAliases = legacyDistrictAliases();
$divisionNodes = [];
$divisionSort = 1;

foreach ($divisions as $division) {
    $divisionLabel = $division['name']['en'];
    $divisionValue = legacyDivisionValue($divisionLabel);

    $districtNodes = [];
    $districtSort = 1;

    foreach ($division['district'] ?? [] as $district) {
        $districtLabel = $district['name']['en'];
        $districtVal = districtValue($districtLabel, $districtAliases);

        $upazilaNodes = [];
        $upazilaSort = 1;

        foreach ($district['upazila'] ?? [] as $upazila) {
            $upazilaLabel = $upazila['name']['en'];
            $upazilaVal = upazilaValue($upazilaLabel, $districtVal);

            $upazilaNodes[] = [
                'value' => $upazilaVal,
                'label' => $upazilaLabel,
                'sort_order' => $upazilaSort++,
            ];
        }

        $districtNodes[] = [
            'value' => $districtVal,
            'label' => $districtLabel,
            'sort_order' => $districtSort++,
            'children' => $upazilaNodes,
        ];
    }

    $divisionNodes[] = [
        'value' => $divisionValue,
        'label' => $divisionLabel,
        'sort_order' => $divisionSort++,
        'children' => $districtNodes,
    ];
}

$output = [
    'group_key' => 'country',
    'entry' => [
        'value' => 'bangladesh',
        'label' => 'Bangladesh',
        'sort_order' => 1,
        'metadata' => [
            'iso' => 'BD',
            'dial' => '+880',
            'hierarchy_type' => 'division_district_upazila',
            'level_2_label' => 'Division',
            'level_3_label' => 'District / City',
            'level_4_label' => 'Upazila / Thana',
            'field_map' => ['level_2' => 'city', 'level_3' => 'state', 'level_4' => 'upazila'],
            'data_source' => 'open-admin-data/bangladesh-administrative-divisions (CC-BY-4.0)',
        ],
        'children' => $divisionNodes,
    ],
];

file_put_contents(
    $outputPath,
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

$upazilaCount = 0;
$districtCount = 0;
foreach ($divisionNodes as $division) {
    foreach ($division['children'] as $district) {
        $districtCount++;
        $upazilaCount += count($district['children']);
    }
}

echo "Generated {$outputPath}\n";
echo "Divisions: ".count($divisionNodes).", Districts: {$districtCount}, Upazilas: {$upazilaCount}\n";
