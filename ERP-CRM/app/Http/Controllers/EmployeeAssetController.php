<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeAssetController extends Controller
{
    /**
     * Danh sách tài sản với filter & search.
     */
    public function index(Request $request)
    {
        $query = EmployeeAsset::query();

        $query->search($request->search)
              ->filterByCategory($request->category)
              ->filterByStatus($request->status)
              ->filterByTrackingType($request->tracking_type);

        $assets = $query->latest()->paginate(15)->withQueryString();

        $categories = EmployeeAsset::distinct()->orderBy('category')->pluck('category');

        return view('employee-assets.index', compact('assets', 'categories'));
    }

    /**
     * Form thêm tài sản mới.
     */
    public function create()
    {
        $categories = EmployeeAsset::distinct()->orderBy('category')->pluck('category');
        return view('employee-assets.create', compact('categories'));
    }

    /**
     * Lưu tài sản mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code'     => ['nullable', 'string', 'max:50'],
            'name'           => ['required', 'string', 'max:255'],
            'category'       => ['required', 'string', 'max:100'],
            'serial_list'    => ['nullable', 'string'],
            'quantity_total' => ['required', 'integer', 'min:1'],
            'brand'          => ['nullable', 'string', 'max:100'],
            'purchase_date'  => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_expiry'=> ['nullable', 'date'],
            'location'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'image'          => ['nullable', 'image', 'max:2048'],
        ]);

        $qty = $validated['quantity_total'];
        $serialList = [];
        if (!empty($validated['serial_list'])) {
            $serialList = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $validated['serial_list']))));
        }

        // Upload ảnh nếu có
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('employee-assets', 'public');
        }

        $baseCode = $validated['asset_code'] ?: 'TS-' . date('YmdHi');
        $createdCount = 0;

        if (count($serialList) > 0) {
            // Có serial -> Tracking Type = 'serial', chia thành N dòng
            for ($i = 0; $i < $qty; $i++) {
                $code = $baseCode . ($qty > 1 ? '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) : '');
                
                // Đảm bảo code unique
                $originalCode = $code;
                $counter = 1;
                while (EmployeeAsset::where('asset_code', $code)->exists()) {
                    $code = $originalCode . '-' . $counter;
                    $counter++;
                }

                EmployeeAsset::create([
                    'asset_code' => $code,
                    'name' => $validated['name'],
                    'category' => $validated['category'],
                    'tracking_type' => 'serial',
                    'serial_number' => $serialList[$i] ?? null,
                    'quantity_total' => 1,
                    'quantity_available' => 1,
                    'brand' => $validated['brand'] ?? null,
                    'purchase_date' => $validated['purchase_date'] ?? null,
                    'purchase_price' => $validated['purchase_price'] ?? null,
                    'warranty_expiry' => $validated['warranty_expiry'] ?? null,
                    'location' => $validated['location'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'image' => $imagePath,
                    'status' => 'available'
                ]);
                $createdCount++;
            }
        } else {
            // Không có serial -> Tracking Type = 'quantity', tạo 1 dòng tổng
            $code = $baseCode;
            $counter = 1;
            while (EmployeeAsset::where('asset_code', $code)->exists()) {
                $code = $baseCode . '-' . $counter;
                $counter++;
            }

            EmployeeAsset::create([
                'asset_code' => $code,
                'name' => $validated['name'],
                'category' => $validated['category'],
                'tracking_type' => 'quantity',
                'serial_number' => null,
                'quantity_total' => $qty,
                'quantity_available' => $qty,
                'brand' => $validated['brand'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'warranty_expiry' => $validated['warranty_expiry'] ?? null,
                'location' => $validated['location'] ?? null,
                'description' => $validated['description'] ?? null,
                'image' => $imagePath,
                'status' => 'available'
            ]);
            $createdCount = 1;
        }

        $msg = $createdCount > 1 
            ? "Đã tạo thành công {$createdCount} biến thể tài sản (Serial riêng biệt)."
            : 'Tài sản "' . $validated['name'] . '" đã được thêm thành công.';

        return redirect()->route('employee-assets.index', ['tracking_type' => count($serialList) > 0 ? 'serial' : 'quantity'])->with('success', $msg);
    }

    /**
     * Chi tiết tài sản + lịch sử cấp phát.
     */
    public function show(EmployeeAsset $employeeAsset)
    {
        $employeeAsset->load([
            'assignments.employee',
            'assignments.assignedByUser',
        ]);
        return view('employee-assets.show', compact('employeeAsset'));
    }

    /**
     * Form chỉnh sửa.
     */
    public function edit(EmployeeAsset $employeeAsset)
    {
        $categories = EmployeeAsset::distinct()->orderBy('category')->pluck('category');
        return view('employee-assets.edit', compact('employeeAsset', 'categories'));
    }

    /**
     * Cập nhật tài sản.
     */
    public function update(Request $request, EmployeeAsset $employeeAsset)
    {
        $validated = $request->validate([
            'asset_code'     => ['required', 'string', 'max:50', 'unique:employee_assets,asset_code,' . $employeeAsset->id],
            'name'           => ['required', 'string', 'max:255'],
            'category'       => ['required', 'string', 'max:100'],
            'serial_number'  => ['nullable', 'string', 'max:100'],
            'quantity_total' => ['nullable', 'integer', 'min:1'],
            'brand'          => ['nullable', 'string', 'max:100'],
            'purchase_date'  => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_expiry'=> ['nullable', 'date'],
            'status'         => ['required', 'in:available,assigned,maintenance,disposed'],
            'location'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'image'          => ['nullable', 'image', 'max:2048'],
        ]);

        // Cập nhật ảnh nếu upload mới
        if ($request->hasFile('image')) {
            if ($employeeAsset->image) {
                Storage::disk('public')->delete($employeeAsset->image);
            }
            $validated['image'] = $request->file('image')->store('employee-assets', 'public');
        }

        // Đồng bộ quantity_available khi thay đổi quantity_total (chỉ quantity-type)
        if ($employeeAsset->tracking_type === 'quantity' && isset($validated['quantity_total'])) {
            $delta = $validated['quantity_total'] - $employeeAsset->quantity_total;
            $validated['quantity_available'] = max(0, $employeeAsset->quantity_available + $delta);
        }

        $employeeAsset->update($validated);

        return redirect()->route('employee-assets.show', $employeeAsset)
            ->with('success', 'Tài sản đã được cập nhật thành công.');
    }

    /**
     * Xoá tài sản — chỉ khi không còn assignment đang active.
     */
    public function destroy(EmployeeAsset $employeeAsset)
    {
        if ($employeeAsset->activeAssignments()->exists()) {
            return back()->with('error', 'Không thể xoá tài sản đang được cấp phát cho nhân viên.');
        }

        if ($employeeAsset->image) {
            Storage::disk('public')->delete($employeeAsset->image);
        }

        $assetName = $employeeAsset->name;
        $employeeAsset->delete();

        return redirect()->route('employee-assets.index')
            ->with('success', "Tài sản \"{$assetName}\" đã được xoá.");
    }

    /**
     * Xuất danh sách tài sản ra Excel.
     */
    public function export(Request $request)
    {
        $query = EmployeeAsset::with('activeAssignments.employee')
            ->search($request->search)
            ->filterByCategory($request->category)
            ->filterByStatus($request->status)
            ->filterByTrackingType($request->tracking_type)
            ->latest();

        $assets = $query->get();

        $filename = 'tai-san-' . now()->format('Y-m-d') . '.xlsx';

        return \Response::streamDownload(function () use ($assets) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header
            $headers = ['Mã tài sản', 'Tên tài sản', 'Danh mục', 'Loại TK', 'Serial/Mã', 'Số lượng', 'Còn lại', 'Hãng', 'Ngày mua', 'Giá mua', 'Trạng thái', 'Vị trí', 'Nhân viên đang dùng'];
            foreach ($headers as $i => $h) {
                $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
            }

            $row = 2;
            foreach ($assets as $a) {
                $holder = $a->activeAssignments->map(fn($x) => optional($x->employee)->name)->filter()->join(', ');
                $sheet->setCellValueByColumnAndRow(1, $row, $a->asset_code);
                $sheet->setCellValueByColumnAndRow(2, $row, $a->name);
                $sheet->setCellValueByColumnAndRow(3, $row, $a->category);
                $sheet->setCellValueByColumnAndRow(4, $row, $a->tracking_type_label);
                $sheet->setCellValueByColumnAndRow(5, $row, $a->serial_number ?? '');
                $sheet->setCellValueByColumnAndRow(6, $row, $a->quantity_total);
                $sheet->setCellValueByColumnAndRow(7, $row, $a->quantity_available);
                $sheet->setCellValueByColumnAndRow(8, $row, $a->brand ?? '');
                $sheet->setCellValueByColumnAndRow(9, $row, $a->purchase_date?->format('d/m/Y') ?? '');
                $sheet->setCellValueByColumnAndRow(10, $row, $a->purchase_price ?? '');
                $sheet->setCellValueByColumnAndRow(11, $row, $a->status_label);
                $sheet->setCellValueByColumnAndRow(12, $row, $a->location ?? '');
                $sheet->setCellValueByColumnAndRow(13, $row, $holder ?: '—');
                $row++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
