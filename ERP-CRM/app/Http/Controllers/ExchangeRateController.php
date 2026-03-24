<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExchangeRateController extends Controller
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Hiển thị danh sách tỷ giá
     */
    public function index(Request $request)
    {
        $query = ExchangeRate::with(['currency', 'creator']);

        // Filter by currency
        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('effective_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('effective_date', '<=', $request->date_to);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $rates = $query->orderBy('effective_date', 'desc')
                       ->orderBy('currency_id')
                       ->paginate(20);

        $currencies = Currency::active()->foreign()->orderBy('sort_order')->get();

        return view('exchange-rates.index', compact('rates', 'currencies'));
    }

    /**
     * Lưu tỷ giá thủ công
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
        ]);

        $this->exchangeRateService->storeManualRate(
            $validated['currency_id'],
            $validated['rate'],
            Carbon::parse($validated['effective_date']),
            auth()->id()
        );

        return redirect()->route('exchange-rates.index')
            ->with('success', 'Đã lưu tỷ giá thành công.');
    }

    /**
     * Cập nhật tỷ giá
     */
    public function update(Request $request, ExchangeRate $exchangeRate)
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:0.000001',
        ]);

        $exchangeRate->update([
            'rate' => $validated['rate'],
            'source' => 'manual',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('exchange-rates.index')
            ->with('success', 'Đã cập nhật tỷ giá thành công.');
    }

    /**
     * Fetch tỷ giá từ Vietcombank (on-demand)
     */
    public function fetchToday()
    {
        $result = $this->exchangeRateService->fetchAndStore(Carbon::today());

        if ($result['success']) {
            return redirect()->route('exchange-rates.index')
                ->with('success', "Đã cập nhật {$result['currencies_updated']} tỷ giá từ Vietcombank.");
        }

        $errorMsg = implode('; ', $result['errors']);
        return redirect()->route('exchange-rates.index')
            ->with('error', "Lỗi fetch tỷ giá: {$errorMsg}");
    }

    /**
     * API: Lấy tỷ giá cho 1 loại tiền tệ tại 1 ngày (dùng cho AJAX)
     */
    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'date' => 'required|date',
        ]);

        $currency = Currency::find($validated['currency_id']);

        if ($currency->isBase()) {
            return response()->json([
                'rate' => 1,
                'currency' => $currency,
                'is_base' => true,
            ]);
        }

        $exchangeRate = ExchangeRate::getRateForDate($currency->id, $validated['date']);

        return response()->json([
            'rate' => $exchangeRate ? (float) $exchangeRate->rate : null,
            'effective_date' => $exchangeRate ? $exchangeRate->effective_date->toDateString() : null,
            'source' => $exchangeRate ? $exchangeRate->source : null,
            'currency' => $currency,
            'is_base' => false,
        ]);
    }
}
