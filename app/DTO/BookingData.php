<?php

namespace App\DTO;

use Illuminate\Http\Request;

class BookingData
{
    public function __construct(
        public int $bike_id,
        public string $start_date,
        public string $end_date,
        public string $customer_name,
        public string $phone,
        public ?string $source = null,
        public ?string $customer_comment = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            bike_id: $request->validated('bike_id'),
            start_date: $request->validated('start_date'),
            end_date: $request->validated('end_date'),
            customer_name: $request->validated('customer_name'),
            phone: $request->validated('phone'),
            source: $request->validated('source'),
            customer_comment: $request->validated('customer_comment'),
        );
    }
}
