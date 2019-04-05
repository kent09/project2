<?php

namespace App\Traits;

use Firebase\JWT;

trait AuthenticationTrait
{
    use JsonWebTokenWrapper;

    public function resolveAuthenticatedUser($request_token) {
        $token = $request_token;
        if($token) {
            $token = static::jwtHeaderExtractorWrapper($token);
            try {
                $user_data = [];

                $token = static::jwtDecodeWrapper( $token );
                $token = (array) $token;

                $user_data['user_id'] = $token['data']->user_id;
                $user_data['email'] = $token['data']->email;

                return $user_data;
            } catch (JWT\ExpiredException $e) {
                return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
            } catch (JWT\BeforeValidException $e) {
                return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
            } catch (JWT\SignatureInvalidException $e) {
                return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
            } catch (\Exception $e) {
                return response()->json(['type' => 'error', 'status' => 401, 'message' => $e->getMessage()]);
            }
        } else return response()->json(['type' => 'error', 'status' => 400, 'message' => 'Bad request, token missing in header request']);
    }
}