<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Client\Factory;
class RemoteAuthenticate {
 public function handle($request, Closure $next) {
  if (!$token=$request->bearerToken()) return response()->json(['message'=>'Missing Bearer token'],401);
  if (!$url=config('services.auth.verify_url')) return response()->json(['message'=>'Auth service not configured'],503);
  try {
   $response=app(Factory::class)->withToken($token)->acceptJson()->connectTimeout(2)->timeout(5)->get($url);
  } catch (\Illuminate\Http\Client\ConnectionException $e) {
   return response()->json(['message'=>'Auth service unavailable'],503);
  }
  if (in_array($response->status(),[401,403],true)) return response()->json(['message'=>'Invalid or expired token'],$response->status());
  // Contract: HTTP 200 JSON is returned only for a valid token.
  if ($response->status()!==200 || !is_array($response->json())) return response()->json(['message'=>'Invalid auth service response'],503);
  $request->attributes->set('auth_user',$response->json());
  return $next($request);
 }
}
