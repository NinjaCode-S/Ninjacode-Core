<?php

namespace Ninjacode\Core\Libs;

class ClientInfo
{
    /**
     * Get requestor IP information
     *
     * @return array
     */
    public static function ipInfo($ip = null)
    {
        if ($ip == null) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $xml = json_decode(file_get_contents('http://www.geoplugin.net/json.gp?ip=' . $ip));

        if (isset($xml)) {
            $data['continentName'] = $xml->geoplugin_continentName ?? null;
            $data['country'] = $xml->geoplugin_countryName ?? null;
            $data['city'] = $xml->geoplugin_city ?? null;
            $data['area'] = $xml->geoplugin_areaCode ?? null;
            $data['code'] = $xml->geoplugin_countryCode ?? null;
            $data['long'] = $xml->geoplugin_longitude ?? null;
            $data['lat'] = $xml->geoplugin_latitude ?? null;
            $data['timezone'] = $xml->geoplugin_timezone ?? null;
            $data['currencyCode'] = $xml->geoplugin_currencyCode ?? null;
            $data['currencySymbol'] = $xml->geoplugin_currencySymbol_UTF8 ?? null;
            $data['inEU'] = $xml->geoplugin_inEU ?? 0;
            $data['ip'] = $ip;
            $data['time'] = date('Y-m-d h:i:s A');
        }
        return $data;
    }

    /**
     * Get requestor operating system information
     *
     * @return array
     */
    public static function osBrowser($add_os = [], $add_browser = [])
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $osPlatform = 'Unknown OS Platform';
        $osArray = [
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile',
            ...$add_os,
        ];
        foreach ($osArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $osPlatform = $value;
            }
        }
        $browser = 'Unknown Browser';
        $browserArray = [
            '/msie/i' => 'Internet Explorer',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/edge/i' => 'Edge',
            '/opera/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i' => 'Handheld Browser',
            ...$add_browser,
        ];
        foreach ($browserArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
            }
        }

        $data['os_platform'] = $osPlatform;
        $data['browser'] = $browser;

        return $data;
    }
}
