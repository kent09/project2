<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT;
use App\Traits\JsonWebTokenWrapper;

class JWTTokenMiddleware
{
    use JsonWebTokenWrapper;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        $response = $next($request);

        // Post-Middleware Action
        $authorization = $request->header('authorization');
        if($authorization) {
            $jwt = static::jwtHeaderExtractorWrapper($authorization);
            if($jwt) {
                try {
                    $token = static::jwtDecodeWrapper( $jwt );
                    $token = (array) $token;
                    if(env('APP_ENV') === 'live' OR env('APP_ENV') === 'production') {
                        if($token['iss'] === env('APP_HOST')) { # only the jwt issuer claim were filter, add if necessary.
                            return $response;
                        }
                    } else return $response;
                } catch (JWT\ExpiredException $e) {
                    return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
                } catch (JWT\BeforeValidException $e) {
                    return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
                } catch (JWT\SignatureInvalidException $e) {
                    return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
                } catch (\Exception $e) {
                    return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
                }
            } else return response()->json(['type' => 'error', 'status' => 400, 'message' => 'Bad request, unable to extract the authorization in header request']);
        } else return response()->json(['type' => 'error', 'status' => 400, 'message' => 'Bad request, token missing in header request']);
    }
}
