<?php

namespace App\Traits;

use App\Enums\UserRole;
use App\Models\Setting;
use Illuminate\Support\Str;

trait CommonTrait
{

    public static function states(): array
    {
        return [
            'Alabama' => 'Alabama',
            'Alaska' => 'Alaska',
            'Arizona' => 'Arizona',
            'Arkansas' => 'Arkansas',
            'California' => 'California',
            'Colorado' => 'Colorado',
            'Connecticut' => 'Connecticut',
            'Delaware' => 'Delaware',
            'Florida' => 'Florida',
            'Georgia' => 'Georgia',
            'Hawaii' => 'Hawaii',
            'Idaho' => 'Idaho',
            'Illinois' => 'Illinois',
            'Indiana' => 'Indiana',
            'Iowa' => 'Iowa',
            'Kansas' => 'Kansas',
            'Kentucky' => 'Kentucky',
            'Louisiana' => 'Louisiana',
            'Maine' => 'Maine',
            'Maryland' => 'Maryland',
            'Massachusetts' => 'Massachusetts',
            'Michigan' => 'Michigan',
            'Minnesota' => 'Minnesota',
            'Mississippi' => 'Mississippi',
            'Missouri' => 'Missouri',
            'Montana' => 'Montana',
            'Nebraska' => 'Nebraska',
            'Nevada' => 'Nevada',
            'New Hampshire' => 'New Hampshire',
            'New Jersey' => 'New Jersey',
            'New Mexico' => 'New Mexico',
            'New York' => 'New York',
            'North Carolina' => 'North Carolina',
            'North Dakota' => 'North Dakota',
            'Ohio' => 'Ohio',
            'Oklahoma' => 'Oklahoma',
            'Oregon' => 'Oregon',
            'Pennsylvania' => 'Pennsylvania',
            'Rhode Island' => 'Rhode Island',
            'South Carolina' => 'South Carolina',
            'South Dakota' => 'South Dakota',
            'Tennessee' => 'Tennessee',
            'Texas' => 'Texas',
            'Utah' => 'Utah',
            'Vermont' => 'Vermont',
            'Virginia' => 'Virginia',
            'Washington' => 'Washington',
            'West Virginia' => 'West Virginia',
            'Wisconsin' => 'Wisconsin',
            'Wyoming' => 'Wyoming',
        ];
    }

    static function familyStatuses(): array
    {
        return [
            'single_parent' => 'Single Parent',
            'married' => 'Married',
            'divorced' => 'Divorced',
            'other' => 'Other',
        ];
    }

    static function householdChildren(): array
    {
        return [
            '0' => '0',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
        ];
    }

    static function genders(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
        ];
    }
    static function schoolGrades(): array
    {
        return [
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
            '11' => '11',
            '12' => '12',
        ];
    }
    static function applyingSchools(): array
    {
        return [
            'School A' => 'School A',
            'School B' => 'School B',
            'School C' => 'School C',
            'School D' => 'School D',
            'School E' => 'School E',
            'School F' => 'School F',
        ];
    }
    static function applyingYears(): array
    {
        return [
            '2025-2026' => '2025-2026',
            '2026-2027' => '2026-2027',
        ];
    }
}
