<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Hiển thị danh sách tiền tệ
     */
    public function index()
    {
        $currencies = Currency::orderBy('sort_order')->orderBy('code')->get();
        return view('currencies.index', compact('currencies'));
    }

    /**
     * Toggle bật/tắt tiền tệ
     */
    public function toggle(Currency $currency)
    {
        // Không cho phép tắt base currency (VND)
        if ($currency->isBase()) {
            return redirect()->route('currencies.index')
                ->with('error', 'Không thể tắt tiền tệ cơ sở (VND).');
        }

        $currency->update(['is_active' => !$currency->is_active]);

        $status = $currency->is_active ? 'bật' : 'tắt';
        return redirect()->route('currencies.index')
            ->with('success', "Đã {$status} tiền tệ {$currency->code}.");
    }

    /**
     * Cập nhật thông tin tiền tệ
     */
    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'name_vi' => 'required|string|max:100',
            'symbol' => 'required|string|max:10',
            'decimal_places' => 'required|integer|min:0|max:4',
            'symbol_position' => 'required|in:before,after',
        ]);

        $currency->update($validated);

        return redirect()->route('currencies.index')
            ->with('success', "Đã cập nhật tiền tệ {$currency->code}.");
    }
}
