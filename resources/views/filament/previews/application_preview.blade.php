<div class="space-y-8">
    <h2 class="text-xl font-bold text-center mb-4">Review Your Application</h2>

    {{-- Parent Information --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold mt-6" style="border-bottom: 2px solid #af8b64;">Father's Information</h3>
        <p><strong>Name:</strong>
            {{ $evaluate(fn($get) => $get('father_first_name').' '.$get('father_last_name')) }}
        </p>
        <p><strong>Phone:</strong> {{ $evaluate(fn($get) => $get('father_phone')) }}</p>
        <p><strong>Email:</strong> {{ $evaluate(fn($get) => $get('father_email')) }}</p>
        <p><strong>Address:</strong>
            {{ $evaluate(fn($get) => $get('father_address').', '.$get('father_city').', '.$get('father_state').' '.$get('father_pincode')) }}
        </p>
    </div>

    <div class="space-y-4">
        <h3 class="text-lg font-semibold mt-6" style="border-bottom: 2px solid #af8b64;">Mother's Information</h3>
        <p><strong>Name:</strong>
            {{ $evaluate(fn($get) => $get('mother_first_name').' '.$get('mother_last_name')) }}
        </p>
        <p><strong>Phone:</strong> {{ $evaluate(fn($get) => $get('mother_phone')) }}</p>
        <p><strong>Email:</strong> {{ $evaluate(fn($get) => $get('mother_email')) }}</p>

        @if($evaluate(fn($get) => $get('mother_has_different_address')))
        <p><strong>Address:</strong>
            {{ $evaluate(fn($get) => $get('mother_address').', '.$get('mother_city').', '.$get('mother_state').' '.$get('mother_pincode')) }}
        </p>
        @endif
    </div>

    <div class="space-y-4">
        <h3 class="text-lg font-semibold mt-6" style="border-bottom: 2px solid #af8b64;">Family Information</h3>
        <p><strong>Status:</strong> {{ $evaluate(fn($get) => $get('family_status')) }}</p>
        <p><strong>Children in Household:</strong> {{ $evaluate(fn($get) => $get('no_of_children_in_household')) }}</p>
        <p><strong>Synagogue Affiliation:</strong> {{ $evaluate(fn($get) => $get('synagogue_affiliation')) }}</p>
    </div>

    {{-- Children Information --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold mt-6" style="border-bottom: 2px solid #af8b64;">Children's Information</h3>
        @php
        $children = $evaluate(fn($get) => $get('children') ?? []);
        @endphp

        @forelse($children as $index => $child)
        @php
        // Ensure we always have an array
        $child = is_array($child) ? $child : [];
        @endphp
        <div class="border rounded-lg p-3">
            <ul class="list-disc ml-5 pl-5" style="padding-left: 2rem;">
                <li>Name: {{ $child['first_name'] ?? '' }} {{ $child['last_name'] ?? '' }}</li>
                <li>Date of Birth: {{ $child['date_of_birth'] ?? '' }}</li>
                <li>Gender: {{ $child['gender'] ?? '' }}</li>
                <li>Current School: {{ $child['current_school_name'] ?? '' }} ({{ $child['current_school_location'] ?? '' }})</li>
                <li>Current Grade: {{ $child['current_grade'] ?? '' }}</li>

                @if(!empty($child['is_applying_for_grant']))
                <li>Applying for Grant: Yes</li>
                <li>Year Applying: {{ $child['school_year_applying_for'] ?? '' }}</li>
                <li>
                    Wish to Apply In:
                    {{ !empty($child['school_wish_to_apply_in']) ? implode(', ', (array) $child['school_wish_to_apply_in']) : '' }}
                </li>
                <li>Have you started the application process to this school: {{ isset($child['attended_school_past_year']) ? ($child['attended_school_past_year'] ? 'Yes' : 'No') : '' }}</li>
                @endif
            </ul>
        </div>
        @empty
        <p>No children entered.</p>
        @endforelse
    </div>

    {{-- Declaration --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold mt-6" style="border-bottom: 2px solid #af8b64;">Declaration</h3>
        <ul class="list-disc ml-5 pl-5" style="padding-left: 2rem;">
            <li>Info is true: {{ $evaluate(fn($get) => $get('info_is_true') ? 'Yes' : 'No') }}</li>
            <li>Applicants are Jewish: {{ $evaluate(fn($get) => $get('applicants_are_jewish') ? 'Yes' : 'No') }}</li>
            <li>Parent Bukharian descent: {{ $evaluate(fn($get) => $get('parent_is_of_bukharian_descent') ? 'Yes' : 'No') }}</li>
            <li>Date: {{ $evaluate(fn($get) => $get('declaration_date')) }}</li>
        </ul>
        <p><strong>Additional Notes:</strong> {{ $evaluate(fn($get) => $get('additional_notes')) }}</p>
    </div>
</div>