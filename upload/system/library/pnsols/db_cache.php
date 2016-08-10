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
	private $cacheTimeSpanSeconds = 1;
		
    private function getCacheFilePath() {
        
        $dir = DIR_DOWNLOAD . 'db_cache/';
        if (!file_exists($dir)) mkdir($dir);
        $dbCacheFilePath = $dir.'db_cache.dat';
        return $dbCacheFilePath;
    }

    public function loadCacheFromFile() {
        $cacheFilePath = $this->getCacheFilePath();
        if (!file_exists($cacheFilePath))
            return;
        $cacheSerialized = file_get_contents($cacheFilePath);
        $this->cacheMap = unserialize($cacheSerialized);
    }

    public function saveCacheToFile() {
        $cacheFilePath = $this->getCacheFilePath();
        $cacheSerialized = serialize($this->cacheMap);
        file_put_contents($cacheFilePath, $cacheSerialized);
    }

	public function addSelectFetchToCache($queryText, $fetchData) {
		$cachedTime = date_create();
		$this->cacheMap[$queryText] = array('time' => $cachedTime, 'data' => $fetchData);
	}
	
	public function getCachedSelectFetch($queryText) {
		if (isset($this->cacheMap[$queryText]) && $this->cacheMap[$queryText] != null) {
            $cachedEntry = $this->cacheMap[$queryText];
			$cacheTime = $cachedEntry['time'];
			$nowTime = date_create();
			$secondsDiffSpan = date_diff($cacheTime, $nowTime);
            $daysDiffCount = $secondsDiffSpan->format('%a');
			if ($daysDiffCount >= $this->cacheTimeSpanSeconds) {
				$this->cacheMap[$queryText] = null;
                unset($this->cacheMap[$queryText]);
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
                $posTableName = stripos($queryTextKey, $dbTableName);
                
                if ($posTableName != '') {
                    $this->cacheMap[$queryTextKey] = null;
                    unset($this->cacheMap[$queryTextKey]);
                }   
            }
		}
	}
	
	public static function isModificationQuery($queryText) {
        $selectStrPos = strpos(trim(strtolower($queryText)), "select");
		$startsWithSelect = $selectStrPos === 0 || $selectStrPos === '0';
        $isModQuery = $startsWithSelect === FALSE;
        
        return $isModQuery;
	}
	
	public static function extractDbTableNamesFromQueryText($queryText) {
		$tableNames = preg_grep('/oc_.+/', explode(' ', $queryText));
        return $tableNames;
	}
	
	public static function processDbQuery($db, $sql) {
		$dbCache = DbCache::getInstance();
        if (DbCache::isModificationQuery($sql)) {
            $dbCache->processModificationQuery($sql);
        } else {
            $cachedFetch = $dbCache->getCachedSelectFetch($sql);
            if ($cachedFetch != null) {
                return $cachedFetch;
            }
        }

		$freshDbFetch = $db->queryNonCache($sql);
        if (!DbCache::isModificationQuery($sql)) {
            $dbCache->addSelectFetchToCache($sql, $freshDbFetch);   
        }
        return $freshDbFetch;
	}
}   

?>