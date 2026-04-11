<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Обложки карточек программ (expert_auto)
    |--------------------------------------------------------------------------
    |
    | Если true и в site/brand/ есть фото (hero, portrait, …), artisan sync
    | генерирует WebP обложек из них (кроп «как object-fit: cover»), иначе
    | используются файлы из tenants/_system/themes/expert_auto/program-covers/.
    |
    */
    'program_covers_prefer_brand_photography' => (bool) env('EXPERT_AUTO_COVERS_FROM_BRAND', true),

];
