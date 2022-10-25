<?php


namespace Kaadon\Jwt;


use Firebase\JWT\JWT as BaseJwt;
use think\facade\Config;
use think\facade\Request;


class Jwt
{

    private static $config = [
        // JWT加密算法
        'alg'         => 'HS256',
        //签发者
        'issuer'      => 'kaadon',
        // 非对称需要配置
        'private_key' => <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIBVAIBADANBgkqhkiG9w0BAQEFAASCAT4wggE6AgEAAkEAlp50BaGP0MyE0/45FRKpxh0sDGECrm6cpp6DkOBFTTdvlxSNZCsO47NWjxjpIrmXV7H0XjmU+3hpWceQpW65wQIDAQABAkEAiriVkzoiAuTa0YUrfcUaqGTl1ODkX1Nw4+TKt/xW163zjeCHAy2YEe6HxGyJITYu156UhC7cOtdsBvM+a275oQIhANj5B2S651fbKh5qJCkROlqmsnaJx5m1oSTB89VK+CWDAiEAsbYFvcz5FvRr7kRJ9VBNzRsSx67nlI9rRjqF+duLBGsCID+eRRyz8MFB8ceZN6ES/Bk4Z3t6Spw3NVihxez0Xm4hAiAe/bRQnj9OPn/YBHa1XjTDMRZ8VkcyhDRcAfa9VQkQUwIge9SR0zj/8kj2/x+4e7zC5QnYA7Qn3mTpmJ7uVtOP9m4=
-----END RSA PRIVATE KEY-----
EOD,
        'public_key'  => <<<EOD
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAJaedAWhj9DMhNP+ORUSqcYdLAxhAq5unKaeg5DgRU03b5cUjWQrDuOzVo8Y6SK5l1ex9F45lPt4aVnHkKVuucECAwEAAQ==
-----END PUBLIC KEY-----
EOD,
        // JWT有效时间
        'exp'         => 3600 * 24 * 7,
    ];

    /**
     * token生成
     *
     * @param array $admin_user 用户信息
     *
     * @return string
     */
    public static function create(string $identification, $data = [],$ip = null)
    {
        $config                 = Config::get('jwt.token');
        $config                 = array_merge(self::$config, $config);
        $time                   = time();
        $exp                    = $config['exp'] ?: 60 * 60 * 24 * 7;
        $key                    = $config['private_key'] ?: self::$privateKey;
        $iss                    = $config['issuer'];
        $exp                    = $time + $exp;
        $data['identification'] = $identification;
        $data['ip']             = $ip?:Request::ip();
        $payload                = [
            'iss'  => $iss,
            'iat'  => $time,
            'exp'  => $exp,
            'data' => $data,
        ];
        $token = BaseJwt::encode($payload, $key, $config['alg']);
        self::redis(Config::get('jwt.cache')?:[])->set("cache:JWT:" . $data['identification'], $token, $config['exp'] ?: 60 * 60 * 24 * 7);
        return $token;
    }

    /**
     * token验证
     *
     * @param string $token token
     *
     * @return json
     */
    public static function verify($token = null,$ip = null)
    {

        if (empty($token)) {
            $tokenBearer = Request::header('Authorization');
            if (!$tokenBearer || !is_string($tokenBearer) || strlen($tokenBearer) < 7) {
                throw new JwtException('The token does not exist or is illegal');
            }
            $token = substr($tokenBearer, 7);
            if (!$token) {
                throw new JwtException('Token is required.');
            }
        }
        $config = Config::get('jwt.token');
        $config = array_merge(self::$config, $config);
        $key    = $config['public_key'];
        if (!$key) {
            throw new JwtException('Public key not configured');
        }
        $decoded = BaseJwt::decode($token, $key, array($config['alg']));

        if (!$decoded || !is_object($decoded)) {
            throw new JwtException('Token validation failed.');
        }
        $Oldtoken = self::redis(Config::get('jwt.cache')?:[])->get("cache:JWT:" . $decoded->data->identification);

        if (empty($Oldtoken)) {
            throw new JwtException('You are not logged in or your login has expired!');
        }

        if ($Oldtoken != $token) {
            throw new JwtException('Your account is logged in elsewhere!');
        }

        if (time() > $decoded->exp) {
            throw new JwtException('Login expired, please login again');
        }
        if (empty($ip)){
            $ip = Request::ip();
        }
        if (isset($config['ip']) && !empty($config['ip'] && $decoded->data->ip !== $ip)) {
            throw new JwtException('Your login environment has been switched!');
        }

        return $decoded;
    }

    /**
     * token删除
     *
     * @param string $token token
     *
     * @return json
     */
    public static function delete($identification)
    {
        return self::redis(Config::get('jwt.cache')?:[])->del("cache:JWT:" . $identification);
    }

    public static function redis(array $param)
    {
        $redis = new \Redis();
        $redis->connect($param['host'] ?: '127.0.0.1', $param['port'] ?: 6379);
        if ($param['password']) {
            $redis->auth($param['password']);
        }
        if ($param['select']) {
            $redis->select($param['select']);
        }
        return $redis;
    }


}
