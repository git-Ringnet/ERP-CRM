<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AttendanceController extends Controller
{
    /**
     * Display the attendance interface for the employee
     */
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        $locations = WorkLocation::where('is_active', true)->get();

        return view('attendance.index', compact('attendance', 'locations'));
    }

    /**
     * Handle the check-in request
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        if ($attendance && $attendance->check_in_time) {
            return response()->json(['success' => false, 'message' => 'Bạn đã check-in hôm nay rồi.']);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;
        $user = Auth::user();

        $matchedLocation = $this->findMatchingWorkLocation($lat, $lng);

        if (!$matchedLocation && $user->timekeeping_type != 'irregular') {
            return response()->json([
                'success' => false, 
                'message' => 'Chấm công thất bại: Vị trí của bạn không nằm trong bán kính cho phép của bất kỳ văn phòng/công trường nào.'
            ]);
        }

        $now = Carbon::now();
        // Define standard start time to judge lateness could be added later
        $status = 'on_time';

        if (!$attendance) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'check_in_time' => $now->format('H:i:s'),
                'check_in_latitude' => $lat,
                'check_in_longitude' => $lng,
                'work_location_id' => $matchedLocation ? $matchedLocation->id : null,
                'status' => $status,
                'device_info' => $request->header('User-Agent'),
                'is_biometrically_verified' => false, // Set false for now
            ]);
        } else {
            $attendance->update([
                'check_in_time' => $now->format('H:i:s'),
                'check_in_latitude' => $lat,
                'check_in_longitude' => $lng,
                'work_location_id' => $matchedLocation ? $matchedLocation->id : null,
                'status' => $status,
            ]);
        }

        $locationName = $matchedLocation ? $matchedLocation->name : 'Vị trí lưu động (Bản đồ)';
        return response()->json([
            'success' => true, 
            'message' => 'Check-in thành công tại ' . $locationName,
            'time' => $now->format('H:i:s')
        ]);
    }

    /**
     * Handle the check-out request
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = $request->latitude;
        $lng = $request->longitude;
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json(['success' => false, 'message' => 'Bạn chưa check-in hôm nay nên không thể check-out.']);
        }

        if ($attendance->check_out_time) {
            return response()->json(['success' => false, 'message' => 'Bạn đã check-out hôm nay rồi.']);
        }

        $matchedLocation = $this->findMatchingWorkLocation($lat, $lng);

        if (!$matchedLocation && $user->timekeeping_type != 'irregular') {
            return response()->json([
                'success' => false, 
                'message' => 'Chấm công thất bại: Bạn không nằm trong bán kính cho phép của bất kỳ văn phòng nào.'
            ]);
        }

        $now = Carbon::now();
        $attendance->update([
            'check_out_time' => $now->format('H:i:s'),
            'check_out_latitude' => $lat,
            'check_out_longitude' => $lng,
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Check-out thành công.',
            'time' => $now->format('H:i:s')
        ]);
    }

    /**
     * Find if current lat/lng is within radius of any active WorkLocation
     * Using Haversine formula
     */
    private function findMatchingWorkLocation($lat, $lng)
    {
        $locations = WorkLocation::where('is_active', true)->get();

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $lat, $lng,
                $location->latitude, $location->longitude
            );

            // Using standard radius mapping
            if ($distance <= $location->radius) {
                return $location;
            }
        }

        return null;
    }

    /**
     * Calculate distance between two points in meters
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Display all attendances for HR/manager
     */
    public function manage(Request $request)
    {
        $this->authorize('viewAny', User::class); // Using User policy to restrict access

        $query = Attendance::with(['user', 'workLocation']);

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        } else {
            // Default to today if no date specified
            $query->where('date', Carbon::today()->toDateString());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $attendances = $query->orderBy('date', 'desc')->orderBy('check_in_time', 'desc')->paginate(15);
        $summaryDate = $request->filled('date') ? Carbon::parse($request->date)->format('d/m/Y') : Carbon::today()->format('d/m/Y');

        return view('attendance.manage', compact('attendances', 'summaryDate'));
    }
    /**
     * Resolve Google Maps short URL to coordinates
     */
    public function resolveLocation(Request $request)
    {
        $url = $request->url;
        if (!$url || !str_starts_with($url, 'http')) {
            return response()->json(['success' => false, 'message' => 'URL không hợp lệ']);
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $response = curl_exec($ch);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            // 1. Try searching for !3dLAT!4dLNG format (The most accurate pin location)
            if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $effectiveUrl, $matches)) {
                return response()->json([
                    'success' => true,
                    'lat' => $matches[1],
                    'lng' => $matches[2]
                ]);
            }

            // 2. Try searching for q=lat,lng or ll=lat,lng (Search query or dropped pin)
            if (preg_match('/[?&](?:q|ll|query)=(-?\d+\.\d+),(-?\d+\.\d+)/', $effectiveUrl, $matches)) {
                return response()->json([
                    'success' => true,
                    'lat' => $matches[1],
                    'lng' => $matches[2]
                ]);
            }

            // 3. Try to find @lat,lng (The map center/viewport - least accurate for markers)
            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $effectiveUrl, $matches)) {
                return response()->json([
                    'success' => true,
                    'lat' => $matches[1],
                    'lng' => $matches[2]
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Không tìm thấy tọa độ trong nội dung liên kết này.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi xử lý liên kết: ' . $e->getMessage()]);
        }
    }
}
