<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of user groups.
     */
    public function index(Request $request)
    {
        $query = UserGroup::with(['leader', 'members']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('leader', function ($lq) use ($search) {
                      $lq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $groups = $query->orderBy('name')->paginate(15)->withQueryString();
        $departments = UserGroup::whereNotNull('department')->where('department', '!=', '')->distinct()->pluck('department');

        return view('user-groups.index', compact('groups', 'departments'));
    }

    /**
     * Show the form for creating a new user group.
     */
    public function create()
    {
        $users = User::where('status', 'active')->orderBy('name')->get();
        return view('user-groups.create', compact('users'));
    }

    /**
     * Store a newly created user group in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:user_groups,code',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $group = UserGroup::create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'leader_id' => $validated['leader_id'] ?? null,
                'department' => $validated['department'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            $memberIds = $request->input('members', []);
            if (!empty($validated['leader_id']) && !in_array($validated['leader_id'], $memberIds)) {
                $memberIds[] = $validated['leader_id'];
            }

            if (!empty($memberIds)) {
                $syncData = [];
                foreach ($memberIds as $mId) {
                    $syncData[$mId] = [
                        'role' => ($mId == ($validated['leader_id'] ?? 0)) ? 'leader' : 'member',
                    ];
                }
                $group->members()->sync($syncData);
            }

            DB::commit();
            return redirect()->route('user-groups.index')->with('success', 'Tạo nhóm người dùng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified user group.
     */
    public function edit(UserGroup $userGroup)
    {
        $users = User::where('status', 'active')->orderBy('name')->get();
        $userGroup->load('members');
        return view('user-groups.edit', compact('userGroup', 'users'));
    }

    /**
     * Update the specified user group in storage.
     */
    public function update(Request $request, UserGroup $userGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:user_groups,code,' . $userGroup->id,
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $userGroup->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'leader_id' => $validated['leader_id'] ?? null,
                'department' => $validated['department'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            $memberIds = $request->input('members', []);
            if (!empty($validated['leader_id']) && !in_array($validated['leader_id'], $memberIds)) {
                $memberIds[] = $validated['leader_id'];
            }

            $syncData = [];
            foreach ($memberIds as $mId) {
                $syncData[$mId] = [
                    'role' => ($mId == ($validated['leader_id'] ?? 0)) ? 'leader' : 'member',
                ];
            }
            $userGroup->members()->sync($syncData);

            DB::commit();
            return redirect()->route('user-groups.index')->with('success', 'Cập nhật nhóm người dùng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user group from storage.
     */
    public function destroy(UserGroup $userGroup)
    {
        $userGroup->members()->detach();
        $userGroup->delete();

        return redirect()->route('user-groups.index')->with('success', 'Đã xóa nhóm người dùng thành công!');
    }

    /**
     * AJAX endpoint: Get eligible engineers/members based on selected lead IDs or group IDs.
     */
    public function getMembersByLead(Request $request)
    {
        $leadIds = array_filter((array)$request->input('lead_ids', []));
        $groupIds = array_filter((array)$request->input('group_ids', []));

        $userIds = [];
        $primaryGroupId = null;

        if (!empty($leadIds)) {
            // Find groups where these users are leaders OR members
            $leaderGroups = UserGroup::whereIn('leader_id', $leadIds)->get();
            $leaderGroupIds = $leaderGroups->pluck('id')->toArray();
            
            if (!empty($leaderGroupIds)) {
                $primaryGroupId = $leaderGroupIds[0];
            }

            $memberGroupIds = DB::table('user_group_members')
                ->whereIn('user_id', $leadIds)
                ->pluck('user_group_id')
                ->toArray();

            if (!$primaryGroupId && !empty($memberGroupIds)) {
                $primaryGroupId = $memberGroupIds[0];
            }

            $groupIds = array_unique(array_merge($groupIds, $leaderGroupIds, $memberGroupIds));
            $userIds = array_merge($userIds, $leadIds);
        }

        if (!empty($groupIds)) {
            $memberIds = DB::table('user_group_members')
                ->whereIn('user_group_id', $groupIds)
                ->pluck('user_id')
                ->toArray();
            $userIds = array_merge($userIds, $memberIds);
        }

        $userIds = array_unique(array_filter($userIds));

        // If no lead/group filter provided or no members found in group, return all active users with role/groups
        $allUsers = User::where('status', 'active')
            ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department']);

        return response()->json([
            'success' => true,
            'allowed_user_ids' => array_values($userIds),
            'primary_group_id' => $primaryGroupId,
            'has_group_filter' => !empty($userIds),
            'all_users' => $allUsers,
        ]);
    }
}
