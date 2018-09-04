<?php
class YNSIRCacheHelper
{
    const YNSIR_CACHE_TIME = 86400;


    /**
     * Set data to cache
     * @param :
     *            $arData : data set to cache
     *            $sCacheTime : time cache data available (3600 second)
     *            $sCacheID : cache id (ex. user_id)
     *            $sCachePath : cache path (ex. /ynsirecruitment/candidate/)
     * $result :
     *            true : set data to cache successful
     *            false : set data to cache error
     */
    public static function SetCached($arData = array(), $sCacheTime = YNSIR_CACHE_TIME, $sCacheID, $sCachePath)
    {
        $bResult = false;
        try {
            $cache = new CPHPCache();
            if ($sCacheTime > 0 && $cache->InitCache($sCacheTime, $sCacheID, $sCachePath)) {
                $res = $cache->GetVars();
                if (is_array($res["DATA"]) && (count($res["DATA"]) > 0)) {
                    static::ClearCached($sCacheID, $sCachePath);
                }
            }
            if (!empty($arData)) {
                if ($sCacheTime > 0) {
                    $cache->StartDataCache($sCacheTime, $sCacheID, $sCachePath);
                    $cache->EndDataCache(array("DATA" => $arData));
                    $bResult = true;
                }
            }
        } catch (Exception $e) {
            // TODO
        }
        return $bResult;
    }


    /**
     * Get data from cache
     * @param :
     *            $sCacheTime : time cache data available (3600 second)
     *            $sCacheID : cache id (ex. user_id)
     *            $sCachePath : cache path (ex. /ynsirecruitment/candidate/)
     * $result : Data get from cache
     *            Note : Please check error result
     */
    public static function GetCached($sCacheTime = ynsir_CACHE_TIME, $sCacheID, $sCachePath)
    {
        $arResult = array('ERROR' => true, 'DATA' => array());
        try {
            $cache = new CPHPCache();
            if ($sCacheTime > 0 && $cache->InitCache($sCacheTime, $sCacheID, $sCachePath)) {
                $res = $cache->GetVars();
                if (is_array($res["DATA"]) && (count($res["DATA"]) > 0)) {
                    $arResult['ERROR'] = false;
                    $arResult['DATA'] = $res["DATA"];
                }
            }
        } catch (Exception $e) {
            // TODO
        }
        return $arResult;
    }

    /**
     * Clean data cache
     * @param :
     *            $sCacheID : cache id (ex. candidate_id)
     *            $sCachePath : cache path (ex. /ynsirecruitment/candidate/)
     */
    public static function ClearCached($sCacheID, $sCachePath)
    {
        $obCache = new CPHPCache;
        if (strlen($sCacheID) > 0)
            $obCache->Clean($sCacheID, $sCachePath);
        else
            $obCache->CleanDir($sCachePath, "cache");
    }

}
