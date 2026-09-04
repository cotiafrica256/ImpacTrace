<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\PublicUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class PublicAuthController extends Controller {
 public function register(Request $r){
  $d=$r->validate(['name'=>'required|string|max:120','email'=>'nullable|email|unique:public_users,email','phone'=>'nullable|string|max:30','password'=>'required|string|min:8|confirmed']);
  $u=PublicUser::create($d); $token=$u->createToken('public-app')->plainTextToken;
  return response()->json(['token'=>$token,'user'=>$u],201);
 }
 public function login(Request $r){
  $d=$r->validate(['email'=>'nullable|email','phone'=>'nullable|string','password'=>'required|string']);
  $u=PublicUser::when($d['email']??null,fn($q)=>$q->where('email',$d['email']))->when(!($d['email']??null),fn($q)=>$q->where('phone',$d['phone']??''))->first();
  if(!$u||!Hash::check($d['password'],$u->password)||!$u->is_active) throw ValidationException::withMessages(['login'=>'Invalid credentials.']);
  return ['token'=>$u->createToken('public-app')->plainTextToken,'user'=>$u];
 }
 public function logout(Request $r){$r->user()->currentAccessToken()?->delete();return ['message'=>'Logged out'];}
}