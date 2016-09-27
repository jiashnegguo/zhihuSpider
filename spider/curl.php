<?php
/**
 * @Author: huhuaquan
 * @Date:   2015-08-10 18:08:43
 * @Last Modified by:   huhuaquan
 * @Last Modified time: 2016-05-27 18:21:16
 */
require_once './function.php';
class Curl {

	private static $cookie;

	private static $cookie_arr = array(
		'__utma' => '51854390.2066736828.1474956387.1474956387.1474956387.1',
		'__utmb' => '51854390.4.10.1474956387',
		'__utmc' => '51854390',
		'__utmv' => '51854390.100-1|2=registration_date=20150315=1^3=entry_date=20150315=1',
		'__utmz' => '51854390.1474956387.1.1.utmcsr=zhihu.com|utmccn=(referral)|utmcmd=referral|utmcct=/',
		'_xsrf' => 'adcfcf915f4506927b88d87646016dc2',
		'_za' => 'a2889ef9-c598-4e96-8ab5-9ca0a9f42e7e',
		'_zap' => '75fae1e9-9ae5-4800-8f6c-02e6c386dd8a',
		'cap_id' => '"YmU0MjgxNjIwZmUyNDM2NTgxMDBkNWRkMWRiZWMzNGI=|1474956344|cd2a0d6922877cd6e0c5875b4fbdf58e7b5841ab"',
		'd_c0' => '"AHDAjsajmgqPTi_urTYLffnd5uEzsTE1l3E=|1474956389"',
		'l_cap_id' => '"OWMzYzNlODIzMmNhNDVhNjg3MWMxYzY5OWZjZTRhNmI=|1474956344|6915e327d6104e54d4b4c0b6ff6068509b455d8a"',
		'l_n_c' => '1',
		'login' => '"NjYwYTAxZWFlN2U2NDIyZWFhYjMzZjJmODYxYTU5Yzk=|1474956400|ac95515084063c2c5bb4673830cce71f005f5c93"',
		'q_c1' => 'b7e9787a8a6d4c1bb5736a63d1e66cf4|1474956344000|1474956344000',
		's-i' => '6',
		's-q' => '%E6%85%A2%E6%80%A7%E8%83%83%E7%82%8E',
		's-t' => 'autocomplete',
		'sid' => 'e63rlk6q',
		'z_c0' => 'Mi4wQVBBQUlyT0x4d2NBY01DT3hxT2FDaGNBQUFCaEFsVk5jSmtSV0FDQjF6REV6YVVRa016NG5YZUhKTXhNelRmYlln|1474956403|eeb3187067c2f0caf4ad7663750d924b9ecffc22'
	);

	public static function init()
	{
		$cookie = '';
		foreach (self::$cookie_arr as $key => $value) {
			if($key != 'z_c0')
				$cookie .= $key . '=' . $value . ';';
			else
				$cookie .= $key . '=' . $value;
		}

		self::$cookie = $cookie;
	}

	private static function genCookie() {
		return self::$cookie;
	}

	/**
	 * [request 执行一次curl请求]
	 * @param  [string] $method     [请求方法]
	 * @param  [string] $url        [请求的URL]
	 * @param  array  $fields     [执行POST请求时的数据]
	 * @return [stirng]             [请求结果]
	 */
	public static function request($method, $url, $fields = array())
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_COOKIE, self::genCookie());
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if ($method === 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, true );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		}
		$result = curl_exec($ch);
		return $result;
	}

	/**
	 * [getMultiUser 多进程获取用户数据]
	 * @param  [type] $user_list [description]
	 * @return [type]            [description]
	 */
	public static function getMultiUser($user_list)
	{
		$ch_arr = array();
		$text = array();
		$len = count($user_list);
		$max_size = ($len > 5) ? 5 : $len;
		$requestMap = array();

		$mh = curl_multi_init();
		for ($i = 0; $i < $max_size; $i++)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL, 'http://www.zhihu.com/people/' . $user_list[$i] . '/about');
			curl_setopt($ch, CURLOPT_COOKIE, self::genCookie());
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$requestMap[$i] = $ch;
			curl_multi_add_handle($mh, $ch);
		}

		$user_arr = array();
		do {
			while (($cme = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM);
			
			if ($cme != CURLM_OK) {break;}

			while ($done = curl_multi_info_read($mh))
			{
				$info = curl_getinfo($done['handle']);
				$tmp_result = curl_multi_getcontent($done['handle']);
				$error = curl_error($done['handle']);

				$user_arr[] = array_values(getUserInfo($tmp_result));

				//保证同时有$max_size个请求在处理
				if ($i < sizeof($user_list) && isset($user_list[$i]) && $i < count($user_list))
                {
                	$ch = curl_init();
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_URL, 'http://www.zhihu.com/people/' . $user_list[$i] . '/about');
					curl_setopt($ch, CURLOPT_COOKIE, self::genCookie());
					curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					$requestMap[$i] = $ch;
					curl_multi_add_handle($mh, $ch);

                    $i++;
                }

                curl_multi_remove_handle($mh, $done['handle']);
			}

			if ($active)
                curl_multi_select($mh, 10);
		} while ($active);

		curl_multi_close($mh);
		return $user_arr;
	}

}