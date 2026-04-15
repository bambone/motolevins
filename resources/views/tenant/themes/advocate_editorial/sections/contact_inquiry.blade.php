@include('tenant.partials.contact-inquiry-form', [
    'section' => $section,
    'data' => is_array($data ?? null) ? $data : [],
    'variant' => 'advocate',
])
