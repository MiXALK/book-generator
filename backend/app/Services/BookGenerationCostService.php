<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;

readonly class BookGenerationCostService
{
    public function __construct(
        private BookGenerationRepositoryInterface $bookGenerations,
    ) {}

    /**
     * @param  array<string, float|int>  $costs
     */
    public function record(BookGeneration $generation, array $costs): void
    {
        $existing = $generation->cost_breakdown;

        if (! is_array($existing)) {
            $existing = [];
        }

        foreach ($costs as $category => $amount) {
            if (! is_numeric($amount)) {
                continue;
            }

            $existing[$category] = round(((float) ($existing[$category] ?? 0)) + (float) $amount, 6);
        }

        $total = round(array_sum(array_map('floatval', $existing)), 6);

        $this->bookGenerations->updateCostMetrics($generation, [
            'cost_breakdown' => $existing,
            'total_cost_usd' => $total,
        ]);

        $generation->cost_breakdown = $existing;
        $generation->total_cost_usd = $total;
    }

    public function recordTextTokens(BookGeneration $generation, ?int $promptTokens, ?int $completionTokens): void
    {
        if ($promptTokens === null && $completionTokens === null) {
            return;
        }

        $inputRate = (float) config('services.cost.text_input_token_usd', 0);
        $outputRate = (float) config('services.cost.text_output_token_usd', 0);
        $inputCost = ($promptTokens ?? 0) * $inputRate;
        $outputCost = ($completionTokens ?? 0) * $outputRate;

        $this->record($generation, [
            'text' => $inputCost + $outputCost,
        ]);
    }

    public function recordImageGenerations(BookGeneration $generation, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $unitCost = (float) config('services.cost.image_generation_usd', 0);
        $this->record($generation, [
            'images' => $count * $unitCost,
        ]);
    }

    public function recordLayoutDuration(BookGeneration $generation, int $durationMs): void
    {
        $ratePerSecond = (float) config('services.cost.layout_cpu_second_usd', 0);

        if ($ratePerSecond <= 0) {
            return;
        }

        $seconds = $durationMs / 1000;
        $this->record($generation, [
            'layout' => $seconds * $ratePerSecond,
        ]);
    }

    public function recordStorageBytes(BookGeneration $generation, int $bytes): void
    {
        if ($bytes <= 0) {
            return;
        }

        $gbMonthRate = (float) config('services.cost.storage_gb_month_usd', 0);

        if ($gbMonthRate <= 0) {
            return;
        }

        $gigabytes = $bytes / (1024 * 1024 * 1024);
        $this->record($generation, [
            'storage' => $gigabytes * $gbMonthRate,
        ]);
    }

    public function recordBandwidthBytes(BookGeneration $generation, int $bytes): void
    {
        if ($bytes <= 0) {
            return;
        }

        $gbRate = (float) config('services.cost.bandwidth_gb_usd', 0);

        if ($gbRate <= 0) {
            return;
        }

        $gigabytes = $bytes / (1024 * 1024 * 1024);
        $this->record($generation, [
            'bandwidth' => $gigabytes * $gbRate,
        ]);
    }
}
