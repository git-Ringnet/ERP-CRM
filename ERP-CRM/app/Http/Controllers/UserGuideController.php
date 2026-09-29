<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UserGuideController extends Controller
{
    /**
     * Display the interactive Help Center / User Guide.
     */
    public function index(Request $request)
    {
        $filePath = base_path('Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.docx');
        $fileExists = file_exists($filePath);
        $fileSize = $fileExists ? round(filesize($filePath) / (1024 * 1024), 2) . ' MB' : null;
        $lastModified = $fileExists ? date('d/m/Y H:i', filemtime($filePath)) : null;

        return view('user_guide.index', compact('fileExists', 'fileSize', 'lastModified'));
    }

    /**
     * Download the latest user guide document.
     */
    public function download()
    {
        $filePath = base_path('Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.docx');
        
        if (!file_exists($filePath)) {
            return back()->with('error', 'Tập tin hướng dẫn sử dụng không tồn tại.');
        }

        return response()->download($filePath, 'Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.docx');
    }

    /**
     * Upload a new updated version of the user guide document.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'guide_file' => 'required|file|mimes:docx,pdf|max:51200', // max 50MB
        ], [
            'guide_file.required' => 'Vui lòng chọn tập tin tài liệu để tải lên.',
            'guide_file.mimes' => 'Hệ thống chỉ chấp nhận tập tin định dạng Word (.docx) hoặc PDF (.pdf).',
            'guide_file.max' => 'Dung lượng tập tin không được vượt quá 50MB.',
        ]);

        try {
            $file = $request->file('guide_file');
            $extension = $file->getClientOriginalExtension();
            
            if ($extension === 'docx') {
                $targetPath = base_path('Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.docx');
                $file->move(base_path(), 'Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.docx');
            } elseif ($extension === 'pdf') {
                $targetPath = base_path('Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.pdf');
                $file->move(base_path(), 'Huong_Dan_Su_Dung_ERP_CRM_Theo_Tung_Vai_Tro.pdf');
            }

            return back()->with('success', 'Đã tải lên và cập nhật phiên bản tài liệu hướng dẫn sử dụng thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra khi lưu tập tin: ' . $e->getMessage());
        }
    }
}
