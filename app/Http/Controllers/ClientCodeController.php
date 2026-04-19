<?php

namespace App\Http\Controllers;

use App\Events\ClientCodeUsed;
use App\Http\Requests\ClientCodeCreateRequest;
use App\Http\Requests\ClientCodeDeleteRequest;
use App\Http\Requests\ClientCodeUpdateRequest;
use App\Http\Requests\ClientCodeUserDetachRequest;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Http\Request;

class ClientCodeController extends Controller
{
    public function store(ClientCodeCreateRequest $request)
    {
        $data = $request->validated();
        $client_code = ClientCode::create($data);

        return [
            'client_code' => $client_code
        ];
    }

    public function update(ClientCodeUpdateRequest $request, ClientCode $client_code)
    {
        $data = $request->validated();
        $client_code->update($data);

        return [
            'client_code' => $client_code
        ];
    }

    public function destroy(ClientCodeDeleteRequest $request)
    {
        $ids = explode(',', $request->client_code_ids);
        $deleted = ClientCode::whereIn('id', $ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function show(string $code)
    {
        $client_code = ClientCode::where('code', $code)->canBeUsed()->first();

        return [
            'client_code' => $client_code
        ];
    }

    public function showById(int $id)
    {
        $client_code = ClientCode::withRelations()->find($id);

        return [
            'client_code' => $client_code
        ];
    }



    public function index(Request $request)
    {
        // Allowed sort columns to prevent SQL injection
        $allowedSortColumns = ['id', 'name', 'code', 'is_active', 'created_at'];
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        // Validate sort column
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        // Prepare eager loads
        $withParam = $request->get('with', 'users');
        $relations = $withParam ? explode(',', $withParam) : [];

        // Build query
        $query = ClientCode::query();

        // Apply search filter (search on name and code)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Apply active status filter (if provided and not 'all')
        if ($request->has('is_active') && $request->get('is_active') !== 'all') {
            $isActive = filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Apply eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Paginate
        $perPage = $request->get('per_page', 15);
        $clientCodes = $query->paginate($perPage);

        return response()->json($clientCodes);
    }

    public function detachUser(ClientCodeUserDetachRequest $request, ClientCode $client_code)
    {
        $user = User::find($request->user_id);
        $performedBy = auth('sanctum')->user();

        $user->client_code_id = null;
        $user->save();

        ClientCodeUsed::dispatch($client_code, $user, 'detach', $performedBy);

        return [
            'message' => 'Client code detached successfully!'
        ];
    }
}
