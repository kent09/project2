<?php

namespace App\Traits;

use Firebase\JWT\JWT;

trait JsonWebTokenWrapper
{

    use UtilityTrait;

    /**
     * @param array $data
     * @param int $day
     *
     * @return string
     * @throws \Exception
     */
    public static function jwtEncodeWrapper(array $data, int $day) {
        return JWT::encode(static::wrapJWTArray($data, $day), env('JWT_SECRET_KEY'));
    }


    /**
     * @param string $jwt
     *
     * @return object
     */
    public static function jwtDecodeWrapper(string $jwt) {
        JWT::$leeway = 60;
        return JWT::decode($jwt, env('JWT_SECRET_KEY'), array('HS256'));
    }


    /**
     * @param string $authorization
     *
     * @return mixed
     */
    public static function jwtHeaderExtractorWrapper(string $authorization) {
        list($jwt) = sscanf($authorization, '_bearer_token: %s');
        return $jwt;
    }


    /**
     * @param array $data
     * @param int $day
     *
     * @return array
     * @throws \Exception
     */
    protected static function wrapJWTArray(array $data, int $day) {
        $time = time();
        #set the exp claim from 1 to 7 days
        #add if necessary
        switch ($day) {
            case 1:
                $time = $time + (1 * 24 * 60 * 60);
                break;
            case 2:
                $time = $time + (2 * 24 * 60 * 60);
                break;
            case 3:
                $time = $time + (3 * 24 * 60 * 60);
                break;
            case 4:
                $time = $time + (4 * 24 * 60 * 60);
                break;
            case 5:
                $time = $time + (5 * 24 * 60 * 60);
                break;
            case 6:
                $time = $time + (6 * 24 * 60 * 60);
                break;
            case 7:
                $time = $time + (7 * 24 * 60 * 60);
                break;
            default:
                $time = $time + 10 + 60; # default to 1 minute 10 seconds
                break;
        }
        return $data = [
            'iat' => time(),
            'jti' => base64_encode(random_bytes(32)),
            'iss' => static::request()->server('SERVER_NAME'),
            'nbf' => time() + 10,
            'exp' => $time,
            'data' => [
                'user_id' => $data['user_id'],
                'email' => $data['email'],
                'name' => $data['name'],
                'username' => $data['username'],
                'type' => $data['type'],
                'verified' => $data['verified'],
                'ban' => $data['ban'],
                'agreed' => $data['agreed'],
                'membership' => \App\Traits\Manager\UserTrait::userMembership($data['user_id']),
                'limitations' => \App\Traits\Manager\UserTrait::userAccessLimitations($data['user_id'])
            ]
        ];

    }
}