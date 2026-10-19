<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TheatreSeatMap
{
    public static function validate(string $json): array
    {
        $config = json_decode($json, true);
        if (! is_array($config)) {
            throw ValidationException::withMessages(['seat_map_config' => 'Enter a valid seat map.']);
        }

        $validator = Validator::make($config, [
            'layout_type' => ['sometimes', 'in:grid,curve'],
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*' => ['required', 'array'],
            'rows.*.label' => ['required', 'string', 'regex:/^[A-Za-z]{1,4}$/'],
            'rows.*.seat_count' => ['required', 'integer', 'min:1', 'max:60'],
            'rows.*.gaps' => ['sometimes', 'array'],
            'rows.*.gaps.*' => ['integer', 'min:0', 'max:59'],
            'rows.*.column_blocks' => ['sometimes', 'array', 'min:1'],
            'rows.*.column_blocks.*' => ['integer', 'min:1', 'max:60'],
            'rows.*.section' => ['sometimes', 'string', 'max:80'],
            'rows.*.start' => ['sometimes', 'array'],
            'rows.*.start.x' => ['required_with:rows.*.start', 'numeric'],
            'rows.*.start.y' => ['required_with:rows.*.start', 'numeric'],
            'rows.*.end' => ['sometimes', 'array'],
            'rows.*.end.x' => ['required_with:rows.*.end', 'numeric'],
            'rows.*.end.y' => ['required_with:rows.*.end', 'numeric'],
            'rows.*.curve_offset' => ['sometimes', 'numeric'],
            'sections' => ['sometimes', 'array'],
            'sections.*.label' => ['required', 'string', 'max:80'],
            'sections.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'row_group_gap_after' => ['sometimes', 'array'],
            'row_group_gap_after.*' => ['string', 'max:4'],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['seat_map_config' => $validator->errors()->first()]);
        }

        $labels = [];
        foreach ($config['rows'] as &$row) {
            $row['label'] = strtoupper($row['label']);
            $row['seat_count'] = (int) $row['seat_count'];
            $row['gaps'] = array_values(array_unique(array_map('intval', $row['gaps'] ?? [])));
            if (in_array($row['label'], $labels, true)) {
                throw ValidationException::withMessages(['seat_map_config' => 'Each row must have a unique label.']);
            }
            $labels[] = $row['label'];
            if (count($row['gaps']) >= $row['seat_count'] || ($row['gaps'] && max($row['gaps']) >= $row['seat_count'])) {
                throw ValidationException::withMessages(['seat_map_config' => 'Gaps must be within the row and leave at least one seat.']);
            }
            if (isset($row['column_blocks'])) {
                $row['column_blocks'] = array_map('intval', $row['column_blocks']);
                if (array_sum($row['column_blocks']) !== $row['seat_count']) {
                    throw ValidationException::withMessages(['seat_map_config' => 'Column blocks must add up to the row seat count.']);
                }
            }
        }
        unset($row);

        return $config;
    }
}
