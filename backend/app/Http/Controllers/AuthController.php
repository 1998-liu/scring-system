<?php
namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required',
            'phone'        => 'required|unique:users',
            'email'        => 'nullable|email|unique:users',
            'password'     => 'required|min:8|max:16|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/',
            'role'         => 'nullable|in:user,admin',
            'company_role' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::create([
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'role'         => $request->role ?? 'user', // 使用请求中的角色值，否则默认为普通用户
            'company_role' => $request->company_role,
        ]);

        // 关联角色
        if ($request->company_role) {
            $role = \App\Role::where('name', $request->company_role)->first();
            if ($role) {
                $user->roles()->attach($role->id);
            }
        }

        $token = JWTAuth::fromUser($user);
        $user->load('roles', 'roles.permissions');

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        // 先检查手机号是否存在
        $user = \App\User::where('phone', $credentials['phone'])->first();
        
        if (!$user) {
            return response()->json(['error' => '手机号不存在'], 401);
        }

        // 再验证密码是否正确
        if (!\Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => '密码错误'], 401);
        }

        // 生成token
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => '登录失败，请稍后重试'], 401);
        }

        $user = auth()->user();
        $user->load('roles', 'roles.permissions');
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function me()
    {
        $user = auth()->user();
        $user->load('roles', 'roles.permissions');
        return response()->json($user);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'newPassword' => 'required|min:8|max:16|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = auth()->user();

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
}
