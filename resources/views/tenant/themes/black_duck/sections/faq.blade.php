{{--
  Black Duck: блок FAQ в т.ч. service_faq с data.source = faqs_table_service и data.faq_category = slug (см. expert_auto).
--}}
@include('tenant.themes.expert_auto.sections.faq', ['section' => $section, 'data' => $data, 'page' => $page ?? null])
