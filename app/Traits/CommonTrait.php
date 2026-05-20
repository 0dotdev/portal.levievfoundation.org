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
            "Manhattan Day School (Yeshiva Ohr Torah)" => "Manhattan Day School (Yeshiva Ohr Torah)",
            "Park East Day School" => "Park East Day School",
            "Sephardic Academy of Manhattan" => "Sephardic Academy of Manhattan",
            "Yeshiva Rabbi Samson Raphael Hirsch" => "Yeshiva Rabbi Samson Raphael Hirsch",
            "Shefa School" => "Shefa School",
            "Yeshiva of Central Queens (YCQ)" => "Yeshiva of Central Queens (YCQ)",
            "Bnos Malka Academy" => "Bnos Malka Academy",
            "Yeshiva Ketana of Queens" => "Yeshiva Ketana of Queens",
            "Bais Yaakov Academy of Queens" => "Bais Yaakov Academy of Queens",
            "Yeshiva Har Torah" => "Yeshiva Har Torah",
            "Yeshiva Tiferes Moshe" => "Yeshiva Tiferes Moshe",
            "SOS Queens" => "SOS Queens",
            "Jewish Institute of Queens (JIQ)" => "Jewish Institute of Queens (JIQ)",
            "Jewish Institute of Queens (JIQ) Middle & High School Boys" => "Jewish Institute of Queens (JIQ) Middle & High School Boys",
            "Ezra Academy" => "Ezra Academy",
            "Yeshiva Shaar HaTorah" => "Yeshiva Shaar HaTorah",
            "Yeshiva of Far Rockaway (Derech Ayson)" => "Yeshiva of Far Rockaway (Derech Ayson)",
            "Yeshivas Chofetz Chaim (RSA)" => "Yeshivas Chofetz Chaim (RSA)",
            "Yeshivah of Flatbush" => "Yeshivah of Flatbush",
            "Bnos Esther Malka" => "Bnos Esther Malka",
            "SAR Academy" => "SAR Academy",
            "SAR High School" => "SAR High School",
            "North Shore Hebrew Academy" => "North Shore Hebrew Academy",
            "Hebrew Academy of Nassau County (HANC)" => "Hebrew Academy of Nassau County (HANC)",
            "Hebrew Academy of Long Beach (HALB)" => "Hebrew Academy of Long Beach (HALB)",
            "Schechter School of Long Island" => "Schechter School of Long Island",
            "Shulamith School for Girls" => "Shulamith School for Girls",
            "Rambam Mesivta" => "Rambam Mesivta",
            "Long Island Hebrew Academy (LIHA)" => "Long Island Hebrew Academy (LIHA)",
            "Magen Israel / Gan Israel Center" => "Magen Israel / Gan Israel Center",
            "Golda Och Academy" => "Golda Och Academy",
            "Reich Hebrew Academy" => "Reich Hebrew Academy",
            "Yeshivat Netivot Montessori" => "Yeshivat Netivot Montessori",
            "PK-8" => "PK-8",
            "Torah Academy of Bergen County" => "Torah Academy of Bergen County",
            "Yavneh Academy" => "Yavneh Academy",
            "Bais Yaakov High School" => "Bais Yaakov High School",
            "Ohr Chana High School Girls" => "Ohr Chana High School Girls",
            "Yeshiva Shaarei Zion PK-8" => "Yeshiva Shaarei Zion PK-8",
            "Yeshiva Shaarei Zion High School" => "Yeshiva Shaarei Zion High School",
            "Zucker Jewish Academy of Queens" => "Zucker Jewish Academy of Queens",
            "Yeshiva Primary" => "Yeshiva Primary",
            "Hadar bet Yaakov - High School for Girls" => "Hadar bet Yaakov - High School for Girls",
            "Lemaan Achai High School Boys" => "Lemaan Achai High School Boys",
            "Be'er Hagolah institutes" => "Be'er Hagolah institutes",
            "Silverstein Hebrew academy" => "Silverstein Hebrew academy",
            "Chabad of Northeast Queens" => "Chabad of Northeast Queens",
            "Shevach High School for Girls" => "Shevach High School for Girls",
            "Queens Hebrew Academy" => "Queens Hebrew Academy",
            "Westchester Hebrew High School" => "Westchester Hebrew High School",
            "The Richard and Jean Katz High School for Girls" => "The Richard and Jean Katz High School for Girls",
            "Torah Day School of Atlanta" => "Torah Day School of Atlanta",
            "School Not Listed / Other" => "School Not Listed / Other",
        ];
    }
    static function applyingYears(): array
    {
        return [
            '2025-2026' => '2025-2026',
            '2026-2027' => '2026-2027',
        ];
    }

    static function applicationStatus(): array
    {
        return [
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'fix_needed' => 'Fix Needed',
            'resubmitted' => 'Resubmitted',
        ];
    }
    static function applicationColors(): array
    {
        return [
            'primary' => 'submitted',
            'success' => 'approved',
            'danger' => 'rejected',
            'warning' => 'fix_needed',
            'info' => 'resubmitted',
        ];
    }
}
