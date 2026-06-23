<?php

namespace Database\Seeders;

use App\Models\SelectOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SelectOptionSeeder extends Seeder
{
    public function run(): void
    {
        SelectOption::truncate();

        $i = fn(array $data) => SelectOption::create($data);

        // ── Profile Created By ────────────────────────────────────────
        foreach ([
                     ['self', 'Self'], ['parents', 'Parents / Guardian'], ['siblings', 'Sibling'],
                     ['relative', 'Relative'], ['friend', 'Friend'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'profile_created_by', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Profile Created For ───────────────────────────────────────
        foreach ([
                     ['self', 'Self'], ['son', 'Son'], ['daughter', 'Daughter'],
                     ['brother', 'Brother'], ['sister', 'Sister'], ['relative', 'Relative'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'profile_created_for', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Looking For ───────────────────────────────────────────────
        foreach ([
                     ['bride', 'Bride (Female)'], ['groom', 'Groom (Male)'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'looking_for', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Marital Status ────────────────────────────────────────────
        foreach ([
                     ['never_married', 'Never Married'], ['divorced', 'Divorced'],
                     ['widowed', 'Widowed'], ['awaiting_divorce', 'Awaiting Divorce'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'marital_status', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Have Children ─────────────────────────────────────────────
        foreach ([['no', 'No'], ['yes', 'Yes']] as $n => [$v, $l]) {
            $i(['group_key' => 'have_children', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Child Living Status ───────────────────────────────────────
        foreach ([
                     ['no_child', 'No Child'],
                     ['child_living_with_me', 'Child Living With Me'],
                     ['child_not_living_with_me', 'Child Not Living With Me'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'child_living_status', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Body Type ─────────────────────────────────────────────────
        foreach ([
                     ['slim', 'Slim'], ['average', 'Average'],
                     ['athletic', 'Athletic / Fit'], ['heavy', 'Heavy / Overweight'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'body_type', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Eye Color ─────────────────────────────────────────────────
        foreach ([
                     ['black', 'Black'], ['dark_brown', 'Dark Brown'], ['brown', 'Brown'],
                     ['hazel', 'Hazel'], ['grey', 'Grey'], ['blue', 'Blue'], ['green', 'Green'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'eye_color', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Hair Color ────────────────────────────────────────────────
        foreach ([
                     ['black', 'Black'], ['dark_brown', 'Dark Brown'], ['brown', 'Brown'],
                     ['blonde', 'Blonde'], ['red', 'Red'], ['grey', 'Grey'], ['white', 'White'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'hair_color', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Complexion ────────────────────────────────────────────────
        foreach ([
                     ['very_fair', 'Very Fair'], ['fair', 'Fair'],
                     ['wheatish', 'Wheatish / Medium'], ['dark', 'Dark'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'complexion', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Blood Group ───────────────────────────────────────────────
        foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $n => $bg) {
            $i(['group_key' => 'blood_group', 'value' => $bg, 'label' => $bg, 'sort_order' => $n + 1]);
        }

        // ── Disability ────────────────────────────────────────────────
        foreach ([
                     ['none', 'None'], ['visual', 'Visual Impairment'], ['hearing', 'Hearing Impairment'],
                     ['physical', 'Physical Disability'], ['speech', 'Speech Impairment'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'disability', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Smoking ───────────────────────────────────────────────────
        foreach ([
                     ['non_smoker', 'No'], ['smoker', 'Yes'], ['occasionally', 'Occasionally'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'smoking', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Drinking ──────────────────────────────────────────────────
        foreach ([
                     ['non_drinker', 'No'], ['drinker', 'Yes'], ['occasionally', 'Occasionally'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'drinking', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Religion (top-level) → Caste (children) ───────────────────
        $islam = $i(['group_key' => 'religion', 'value' => 'islam', 'label' => 'Islam', 'sort_order' => 1]);
        $hindu = $i(['group_key' => 'religion', 'value' => 'hinduism', 'label' => 'Hinduism', 'sort_order' => 2]);
        $buddhism = $i(['group_key' => 'religion', 'value' => 'buddhism', 'label' => 'Buddhism', 'sort_order' => 3]);
        $chrst = $i(['group_key' => 'religion', 'value' => 'christianity', 'label' => 'Christianity', 'sort_order' => 4]);
        $sikh = $i(['group_key' => 'religion', 'value' => 'sikhism', 'label' => 'Sikhism', 'sort_order' => 5]);
        $jain = $i(['group_key' => 'religion', 'value' => 'jainism', 'label' => 'Jainism', 'sort_order' => 6]);
        $juda = $i(['group_key' => 'religion', 'value' => 'judaism', 'label' => 'Judaism', 'sort_order' => 7]);
        $i(['group_key' => 'religion', 'value' => 'other', 'label' => 'Other', 'sort_order' => 8]);

        // Caste under Islam
        foreach ([
                     ['sunni', 'Sunni'], ['shia', 'Shia'], ['ahmadiyya', 'Ahmadiyya'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'caste', 'parent_id' => $islam->id, 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }
        // Caste under Hinduism
        foreach ([
                     ['brahmin', 'Brahmin'], ['kshatriya', 'Kshatriya'], ['vaishya', 'Vaishya'],
                     ['shudra', 'Shudra'], ['kayastha', 'Kayastha'], ['varna_other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'caste', 'parent_id' => $hindu->id, 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }
        // Caste under Buddhism
        foreach ([
                     ['theravada', 'Theravada'], ['mahayana', 'Mahayana'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'caste', 'parent_id' => $buddhism->id, 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }
        // Caste under Christianity
        foreach ([
                     ['catholic', 'Catholic'], ['protestant', 'Protestant'],
                     ['orthodox', 'Orthodox'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'caste', 'parent_id' => $chrst->id, 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }
        // Default caste for Sikhism, Jainism, Judaism, Other
        foreach ([$sikh, $jain, $juda] as $rel) {
            $i(['group_key' => 'caste', 'parent_id' => $rel->id, 'value' => 'other', 'label' => 'Other', 'sort_order' => 1]);
        }

        // ── Religiousness ─────────────────────────────────────────────
        foreach ([
                     ['very_religious', 'Very Religious'], ['religious', 'Religious'],
                     ['moderate', 'Moderate'], ['not_religious', 'Not Religious'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'religiousness', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Pray ──────────────────────────────────────────────────────
        foreach ([
                     ['always', 'Always'], ['usually', 'Usually'], ['sometimes', 'Sometimes'],
                     ['rarely', 'Rarely'], ['never', 'Never'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'pray', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Manglik Status ────────────────────────────────────────────
        foreach ([
                     ['yes', 'Yes'], ['no', 'No'], ['partial', 'Partial'], ['dont_know', "Don't Know"],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'manglik_status', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Mother Tongue ─────────────────────────────────────────────
        foreach ([
                     ['bengali', 'Bengali (Bangla)'], ['english', 'English'], ['hindi', 'Hindi'],
                     ['urdu', 'Urdu'], ['arabic', 'Arabic'], ['punjabi', 'Punjabi'], ['tamil', 'Tamil'],
                     ['telugu', 'Telugu'], ['marathi', 'Marathi'], ['gujarati', 'Gujarati'],
                     ['kannada', 'Kannada'], ['malayalam', 'Malayalam'], ['odia', 'Odia'],
                     ['assamese', 'Assamese'], ['sindhi', 'Sindhi'], ['nepali', 'Nepali'],
                     ['sinhala', 'Sinhala'], ['burmese', 'Burmese'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'mother_tongue', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Family Values ─────────────────────────────────────────────
        foreach ([
                     ['traditional', 'Traditional'], ['moderate', 'Moderate'],
                     ['liberal', 'Liberal'], ['religious', 'Religious'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'family_values', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Occupation ────────────────────────────────────────────────
        foreach ([
                     ['business', 'Business / Entrepreneur'], ['government_service', 'Government Service'],
                     ['private_service', 'Private Service'], ['defense', 'Defense / Military'],
                     ['doctor', 'Doctor / Medical'], ['engineer', 'Engineer'],
                     ['teacher', 'Teacher / Professor'], ['lawyer', 'Lawyer / Advocate'],
                     ['it_professional', 'IT Professional'], ['banker', 'Banker / Finance'],
                     ['journalist', 'Journalist / Media'], ['artist', 'Artist / Creative'],
                     ['farmer', 'Farmer / Agriculture'], ['skilled_worker', 'Skilled Worker'],
                     ['student', 'Student'], ['retired', 'Retired'],
                     ['homemaker', 'Homemaker'], ['not_working', 'Not Working'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'occupation', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Profession ────────────────────────────────────────────────
        foreach ([
                     ['doctor_physician', 'Doctor / Physician'], ['dentist', 'Dentist'],
                     ['nurse', 'Nurse / Paramedic'], ['pharmacist', 'Pharmacist'],
                     ['software_engineer', 'Software Engineer'], ['hardware_engineer', 'Hardware Engineer'],
                     ['data_scientist', 'Data Scientist / AI'], ['civil_engineer', 'Civil Engineer'],
                     ['electrical_engineer', 'Electrical Engineer'], ['mechanical_engineer', 'Mechanical Engineer'],
                     ['architect', 'Architect'], ['teacher_professor', 'Teacher / Professor'],
                     ['lawyer', 'Lawyer / Advocate'], ['accountant', 'Accountant / CA'],
                     ['banker', 'Banker'], ['financial_analyst', 'Financial Analyst'],
                     ['marketing_professional', 'Marketing Professional'], ['hr_professional', 'HR Professional'],
                     ['journalist', 'Journalist / Media'], ['government_officer', 'Government Officer'],
                     ['defense_forces', 'Defense / Military'], ['police', 'Police / Law Enforcement'],
                     ['businessman', 'Businessman / Entrepreneur'], ['artist_designer', 'Artist / Designer'],
                     ['fashion_designer', 'Fashion Designer'], ['chef_cook', 'Chef / Cook'],
                     ['pilot', 'Pilot'], ['sailor', 'Sailor / Navy'], ['student', 'Student'],
                     ['freelancer', 'Freelancer'], ['farmer', 'Farmer'], ['driver', 'Driver'],
                     ['skilled_worker', 'Skilled Worker'], ['homemaker', 'Homemaker'],
                     ['not_working', 'Not Working'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'profession', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Education Level ───────────────────────────────────────────
        foreach ([
                     ['below_ssc', 'Below SSC / Secondary'], ['ssc', 'SSC / Secondary School Certificate'],
                     ['hsc', 'HSC / Higher Secondary Certificate'], ['diploma', 'Diploma'],
                     ['bachelors', 'Bachelors / Graduate'], ['masters', 'Masters / Post Graduate'],
                     ['phd', 'PhD / Doctorate'], ['postdoctoral', 'Post Doctoral'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'education_level', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Employed In ───────────────────────────────────────────────
        foreach ([
                     ['private', 'Private Sector'], ['government', 'Government'],
                     ['business', 'Business / Self-Owned'], ['self_employed', 'Self-Employed / Freelance'],
                     ['not_working', 'Not Working'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'employed_in', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Diet ──────────────────────────────────────────────────────
        foreach ([
                     ['non_vegetarian', 'Non-Vegetarian'], ['vegetarian', 'Vegetarian'],
                     ['vegan', 'Vegan'], ['jain', 'Jain'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'diet', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Eye-Wear ──────────────────────────────────────────────────
        foreach ([
                     ['none', 'None'], ['glasses', 'Glasses / Spectacles'], ['contact_lens', 'Contact Lens'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'eye_wear', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Hobbies ───────────────────────────────────────────────────
        foreach ([
                     ['reading', 'Reading'], ['cooking', 'Cooking'], ['traveling', 'Traveling'],
                     ['music', 'Music / Singing'], ['movies', 'Movies / Web Series'],
                     ['sports', 'Sports / Fitness'], ['photography', 'Photography'],
                     ['gardening', 'Gardening'], ['art_craft', 'Art & Craft'], ['dancing', 'Dancing'],
                     ['writing', 'Writing / Blogging'], ['gaming', 'Gaming'],
                     ['volunteering', 'Volunteering'], ['yoga_meditation', 'Yoga / Meditation'],
                     ['cycling', 'Cycling'], ['swimming', 'Swimming'], ['fishing', 'Fishing'],
                     ['cricket', 'Cricket'], ['football', 'Football'], ['badminton', 'Badminton'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'hobbies', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Nationality ───────────────────────────────────────────────
        foreach ([
                     ['bangladeshi', 'Bangladeshi'],
                     ['american', 'American'], ['canadian', 'Canadian'], ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'nationality', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Country (top-level) → State/Division (children) → District/City ──

        $seedCountryChildren = function (SelectOption $parent, array $items) use ($i): array {
            $created = [];

            foreach ($items as $n => $item) {
                [$value, $label, $metadata] = array_pad($item, 3, null);

                $created[$value] = $i([
                    'group_key' => 'country',
                    'parent_id' => $parent->id,
                    'value' => $value,
                    'label' => $label,
                    'metadata' => $metadata,
                    'sort_order' => $n + 1,
                ]);
            }

            return $created;
        };

        $countries = [];
        foreach ([
                     ['bangladesh', 'Bangladesh', ['iso' => 'BD', 'dial' => '+880', 'level_2_label' => 'Division', 'level_3_label' => 'District / City']],
                     ['united_states', 'United States of America', ['iso' => 'US', 'dial' => '+1', 'level_2_label' => 'State']],
                     ['canada', 'Canada', ['iso' => 'CA', 'dial' => '+1', 'level_2_label' => 'Province / Territory']],
                 ] as $n => [$value, $label, $metadata]) {
            $countries[$value] = $i([
                'group_key' => 'country',
                'value' => $value,
                'label' => $label,
                'metadata' => $metadata,
                'sort_order' => $n + 1,
            ]);
        }

        $bd = $countries['bangladesh'];
        $us = $countries['united_states'];
        $ca = $countries['canada'];

        // Bangladesh divisions (parent = Bangladesh country row)
        $bdDivisions = $seedCountryChildren($bd, [
            ['dhaka', 'Dhaka'], ['chittagong', 'Chittagong'], ['rajshahi', 'Rajshahi'], ['khulna', 'Khulna'],
            ['barisal', 'Barisal'], ['sylhet', 'Sylhet'], ['rangpur', 'Rangpur'], ['mymensingh', 'Mymensingh'],
        ]);

        $seedCountryChildren($bdDivisions['dhaka'], [
            ['dhaka', 'Dhaka'], ['faridpur', 'Faridpur'], ['gazipur', 'Gazipur'], ['gopalganj', 'Gopalganj'],
            ['kishoreganj', 'Kishoreganj'], ['madaripur', 'Madaripur'], ['manikganj', 'Manikganj'], ['munshiganj', 'Munshiganj'],
            ['narayanganj', 'Narayanganj'], ['narsingdi', 'Narsingdi'], ['rajbari', 'Rajbari'], ['shariatpur', 'Shariatpur'], ['tangail', 'Tangail'],
        ]);
        $seedCountryChildren($bdDivisions['chittagong'], [
            ['bandarban', 'Bandarban'], ['brahmanbaria', 'Brahmanbaria'], ['chandpur', 'Chandpur'], ['chittagong', 'Chittagong'],
            ['comilla', 'Comilla'], ["cox's_bazar", "Cox's Bazar"], ['feni', 'Feni'], ['khagrachhari', 'Khagrachhari'],
            ['lakshmipur', 'Lakshmipur'], ['noakhali', 'Noakhali'], ['rangamati', 'Rangamati'],
        ]);
        $seedCountryChildren($bdDivisions['rajshahi'], [
            ['bogra', 'Bogra'], ['chapai_nawabganj', 'Chapai Nawabganj'], ['joypurhat', 'Joypurhat'], ['naogaon', 'Naogaon'],
            ['natore', 'Natore'], ['pabna', 'Pabna'], ['rajshahi', 'Rajshahi'], ['sirajganj', 'Sirajganj'],
        ]);
        $seedCountryChildren($bdDivisions['khulna'], [
            ['bagerhat', 'Bagerhat'], ['chuadanga', 'Chuadanga'], ['jessore', 'Jessore'], ['jhenaidah', 'Jhenaidah'],
            ['khulna', 'Khulna'], ['kushtia', 'Kushtia'], ['magura', 'Magura'], ['meherpur', 'Meherpur'],
            ['narail', 'Narail'], ['satkhira', 'Satkhira'],
        ]);
        $seedCountryChildren($bdDivisions['barisal'], [
            ['barguna', 'Barguna'], ['barisal', 'Barisal'], ['bhola', 'Bhola'],
            ['jhalokati', 'Jhalokati'], ['patuakhali', 'Patuakhali'], ['pirojpur', 'Pirojpur'],
        ]);
        $seedCountryChildren($bdDivisions['sylhet'], [
            ['habiganj', 'Habiganj'], ['moulvibazar', 'Moulvibazar'], ['sunamganj', 'Sunamganj'], ['sylhet', 'Sylhet'],
        ]);
        $seedCountryChildren($bdDivisions['rangpur'], [
            ['dinajpur', 'Dinajpur'], ['gaibandha', 'Gaibandha'], ['kurigram', 'Kurigram'], ['lalmonirhat', 'Lalmonirhat'],
            ['nilphamari', 'Nilphamari'], ['panchagarh', 'Panchagarh'], ['rangpur', 'Rangpur'], ['thakurgaon', 'Thakurgaon'],
        ]);
        $seedCountryChildren($bdDivisions['mymensingh'], [
            ['jamalpur', 'Jamalpur'], ['mymensingh', 'Mymensingh'], ['netrokona', 'Netrokona'], ['sherpur', 'Sherpur'],
        ]);

        // United States states + federal district
        $seedCountryChildren($us, [
            ['alabama', 'Alabama'], ['alaska', 'Alaska'], ['arizona', 'Arizona'], ['arkansas', 'Arkansas'], ['california', 'California'],
            ['colorado', 'Colorado'], ['connecticut', 'Connecticut'], ['delaware', 'Delaware'], ['district_of_columbia', 'District of Columbia'],
            ['florida', 'Florida'], ['georgia', 'Georgia'], ['hawaii', 'Hawaii'], ['idaho', 'Idaho'], ['illinois', 'Illinois'],
            ['indiana', 'Indiana'], ['iowa', 'Iowa'], ['kansas', 'Kansas'], ['kentucky', 'Kentucky'], ['louisiana', 'Louisiana'],
            ['maine', 'Maine'], ['maryland', 'Maryland'], ['massachusetts', 'Massachusetts'], ['michigan', 'Michigan'], ['minnesota', 'Minnesota'],
            ['mississippi', 'Mississippi'], ['missouri', 'Missouri'], ['montana', 'Montana'], ['nebraska', 'Nebraska'], ['nevada', 'Nevada'],
            ['new_hampshire', 'New Hampshire'], ['new_jersey', 'New Jersey'], ['new_mexico', 'New Mexico'], ['new_york', 'New York'], ['north_carolina', 'North Carolina'],
            ['north_dakota', 'North Dakota'], ['ohio', 'Ohio'], ['oklahoma', 'Oklahoma'], ['oregon', 'Oregon'], ['pennsylvania', 'Pennsylvania'],
            ['rhode_island', 'Rhode Island'], ['south_carolina', 'South Carolina'], ['south_dakota', 'South Dakota'], ['tennessee', 'Tennessee'], ['texas', 'Texas'],
            ['utah', 'Utah'], ['vermont', 'Vermont'], ['virginia', 'Virginia'], ['washington', 'Washington'], ['west_virginia', 'West Virginia'],
            ['wisconsin', 'Wisconsin'], ['wyoming', 'Wyoming'],
        ]);

        // Canada provinces + territories
        $seedCountryChildren($ca, [
            ['alberta', 'Alberta'], ['british_columbia', 'British Columbia'], ['manitoba', 'Manitoba'], ['new_brunswick', 'New Brunswick'],
            ['newfoundland_and_labrador', 'Newfoundland and Labrador'], ['northwest_territories', 'Northwest Territories'], ['nova_scotia', 'Nova Scotia'],
            ['nunavut', 'Nunavut'], ['ontario', 'Ontario'], ['prince_edward_island', 'Prince Edward Island'], ['quebec', 'Quebec'],
            ['saskatchewan', 'Saskatchewan'], ['yukon', 'Yukon'],
        ]);

        // ── Residing Status ───────────────────────────────────────────
        foreach ([
                     ['citizen', 'Citizen'], ['permanent_resident', 'Permanent Resident (PR)'],
                     ['work_permit', 'Work Permit / Visa'], ['student_visa', 'Student Visa'],
                     ['visitor_visa', 'Visitor / Tourist Visa'], ['refugee', 'Refugee / Asylum'],
                     ['other', 'Other'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'residing_status', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Family Type ───────────────────────────────────────────────
        foreach ([
                     ['nuclear', 'Nuclear'], ['joint', 'Joint'], ['extended', 'Extended'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'family_type', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Family Status ─────────────────────────────────────────────
        foreach ([
                     ['middle_class', 'Middle Class'], ['upper_middle_class', 'Upper Middle Class'],
                     ['rich', 'Rich / Well Off'], ['affluent', 'Affluent / Very Rich'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'family_status', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Rashi (Zodiac / Moon Sign) ────────────────────────────────
        foreach ([
                     ['aries', 'Aries (Mesh)'], ['taurus', 'Taurus (Vrishabha)'], ['gemini', 'Gemini (Mithun)'],
                     ['cancer', 'Cancer (Karka)'], ['leo', 'Leo (Simha)'], ['virgo', 'Virgo (Kanya)'],
                     ['libra', 'Libra (Tula)'], ['scorpio', 'Scorpio (Vrishchik)'],
                     ['sagittarius', 'Sagittarius (Dhanu)'], ['capricorn', 'Capricorn (Makar)'],
                     ['aquarius', 'Aquarius (Kumbha)'], ['pisces', 'Pisces (Meen)'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'rashi', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Working Status ────────────────────────────────────────────
        foreach ([
                     ['working', 'Working / Employed'], ['homemaker', 'Homemaker'],
                     ['student', 'Student'], ['not_working', 'Not Working'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'working_status', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        // ── Pref Has Children (with "Any") ────────────────────────────
        foreach ([
                     ['no', 'No Children'], ['yes', 'Has Children'], ['any', 'Any / Does Not Matter'],
                 ] as $n => [$v, $l]) {
            $i(['group_key' => 'pref_has_children', 'value' => $v, 'label' => $l, 'sort_order' => $n + 1]);
        }

        Cache::flush();
    }
}

