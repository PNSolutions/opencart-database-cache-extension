<?PHP
/**
OpenCart 1.5.x DB Cache module
PN Solutions http://pnsols.com 2016
*/



class DbCache {
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;
    
    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function isCreated() {
        return static::$instance !== NULL;
    }

    private $cacheChanged = FALSE;

    public function isChanged() {
        return $this->cacheChanged;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        $this->loadCacheFromFile();
    }



    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
	
	private $cacheMap = array();
    const DEFAULT_CACHE_TIMEOUT_SECONDS = 3600;
		
    public function clear() {
        $dbCache = $this;
        $dirPath = $dbCache->getCacheDirPath();
        $files = scandir($dirPath);
        foreach ($files as $filePath) {
            if ($filePath == '.' || $filePath == '..') continue;
            unlink($dirPath.$filePath);
        }
        rmdir($dirPath);
    }

    public function getCacheDirPath() {
        $dirPath = DIR_CACHE.'db_cache/';
        if (!file_exists($dirPath)) mkdir($dirPath);
        return $dirPath;
    }

    private function getCacheMapFilePath() {
        
        $dir = DIR_DOWNLOAD . 'db_cache/';
        $dbCacheFilePath = $dir.'db_cache.dat';
        if (file_exists($dbCacheFilePath)) unlink($dbCacheFilePath);
        if (file_exists($dir)) rmdir($dir);

        $latestPath = $this->getCacheDirPath();
        //$latestPath .= '_index';
        $latestPath .= '_cache';

        return $latestPath;
    }

    public function loadCacheFromFile() {
        $cacheFilePath = $this->getCacheMapFilePath();
        if (!file_exists($cacheFilePath))
            return;
        $handle = fopen($cacheFilePath, "r");
        flock($handle, LOCK_SH);
        $cacheSerialized = fread($handle, filesize($cacheFilePath));
        fclose($handle);
        $this->cacheMap = unserialize($cacheSerialized);
    }

    public function saveCacheToFile() {
        $cacheFilePath = $this->getCacheMapFilePath();
        
        $cacheSerialized = serialize($this->cacheMap);
        
        $handle = fopen($cacheFilePath, 'w');
        flock($handle, LOCK_EX);
        fwrite($handle, $cacheSerialized);
        fflush($handle);
        fclose($handle);
    }

	public function addSelectFetchToCache($queryText, $fetchData) {
        $this->setCacheEntry($queryText, $fetchData);
	}
	
	public function getCachedSelectFetch($queryText) {
        $cachedFetchData = $this->getCachedDataFromCacheMap($queryText);
        //$cachedFetchData = $this->getCacheEntryData($queryText);
        
        return $cachedFetchData;
	}

    private function getCachedDataFromCacheMap($queryText) {
        
		if (isset($this->cacheMap[$queryText]) && $this->cacheMap[$queryText] != null) {
            $cachedEntry = $this->cacheMap[$queryText];
			$cacheTime = $cachedEntry['time'];
			$nowTime = date_create();
			$secondsDiffSpan = date_diff($cacheTime, $nowTime);
            $daysDiffCount = $secondsDiffSpan->format('%a');
			if ($daysDiffCount >= self::DEFAULT_CACHE_TIMEOUT_SECONDS) {
                $this->removeCacheEntry($queryText);
				return null;
			}
			return $cachedEntry['data'];
		}
		return null;
    }
	
	public function processModificationQuery($queryText) {
		$dbTableNamesInQuery = DbCache::extractDbTableNamesFromQueryText($queryText);
		foreach ($this->cacheMap as $queryTextKey => $cacheEntry) {
            foreach ($dbTableNamesInQuery as $dbTableName) { 
                if (stripos($queryTextKey, $dbTableName)) {
                    $this->removeCacheEntry($queryTextKey);
                    //echo 'remove db cache: '.$queryTextKey.'<br />';
                }   
            }
		}
	}

    private function getCacheEntryFilePath($cacheKey) {
        return $this->getCacheDirPath().$this->getCacheEntryFileNameByHash($this->getSelectQueryHash($cacheKey));
    }

    private function getCacheEntryFileNameByHash($hash) {
        return $hash;
    }

    private function getCacheEntryData($cacheKey) {
        if (!file_exists($this->getCacheEntryFilePath($cacheKey))) return NULL;
        $nowTime = date_create();
        
        $timeModified = date_create();
        date_timestamp_set($timeModified, filemtime($this->getCacheEntryFilePath($cacheKey)));
        
		$secondsDiffSpan = date_diff($timeModified, $nowTime);
        $daysDiffCount = $secondsDiffSpan->format('%a');
		if ($daysDiffCount >= self::DEFAULT_CACHE_TIMEOUT_SECONDS) {
            $this->removeCacheEntry($cacheKey);
			return NULL;
		}
		return unserialize(file_get_contents($this->getCacheEntryFilePath($cacheKey)));

    }

    private function removeCacheEntry($queryTextKey) {
        if (isset($this->cacheMap[$queryTextKey])) {
            //$cacheEntryFilePath = $this->getCacheDirPath().$this->getCacheEntryFileNameByHash($this->cacheMap[$queryTextKey]);
            //if (file_exists($cacheEntryFilePath)) unlink($cacheEntryFilePath);
            $this->cacheMap[$queryTextKey] = null;
            unset($this->cacheMap[$queryTextKey]);   
            $this->cacheChanged = TRUE;
        }
    }

    private function getSelectQueryHash($queryText) {
        return md5($queryText);
    }

    private function setCacheEntry($cacheKey, $cacheData) {
		$cachedTime = date_create();
        //file_put_contents($this->getCacheEntryFilePath($cacheKey), serialize($cacheData));
		$this->cacheMap[$cacheKey] = array('time' => $cachedTime, 'data' => $cacheData);
        $this->cacheChanged = TRUE;
        //$this->cacheMap[$cacheKey] = $this->getSelectQueryHash($cacheKey);
    }
	
	public static function isModificationQuery($queryText) {
        //strpos(trim(strtolower($queryText)), "select");
		//$startsWithSelect = $selectStrPos === 0 || $selectStrPos === '0';
        //$isModQuery = $startsWithSelect === FALSE;
        
        //return $isModQuery;
        $arReadQueries = array('select', 'show tables', 'show columns');
        foreach ($arReadQueries as $queryRead) {
            $striposSelect = stripos(trim($queryText), $queryRead);
            if ($striposSelect === 0 || $striposSelect === '0') return FALSE;
        }
        return TRUE;
	}
	
	public static function extractDbTableNamesFromQueryText($queryText) {
		$tableNames = preg_grep('/'.DB_PREFIX.'.+/', explode(' ', $queryText));
        return $tableNames;
	}
	
	public static function processDbQuery($db, $sql) {
		$dbCache = DbCache::getInstance();
        if (DbCache::isModificationQuery($sql)) {
            $dbCache->processModificationQuery($sql);
        } else {
            $cachedFetch = $dbCache->getCachedSelectFetch($sql);
            if ($cachedFetch != null) {
                //echo 'cached select query: '.$sql;
                return $cachedFetch;
            }
        }

		$freshDbFetch = $db->queryNonCache($sql);
        if (!DbCache::isModificationQuery($sql)) {
                //echo 'add cache select query: '.$sql;
            $dbCache->addSelectFetchToCache($sql, $freshDbFetch);   
        }
        return $freshDbFetch;
	}

}   



?>