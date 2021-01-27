<?php

class wp {
    public string $error;
    public bool $usePresets = true;
    public string $pool = "";
    public object $listener;
    public string $lastError = "";
    public function __construct(
        public array $presets = [],
        public string $path = "public/",
        public array $tag = ["{","}"],
        public string $corePath = "core/",
        public string $blocks = "blocks/"
    ){
        $this->listener = (object) [
            "pageNotFound"=>[],
            "pageConstruct"=>[]
        ];
        return self::checkAll();
    }
    public function getBlock(string $name,array $vars=[]): string|false {
        if(!file_exists($this->blocks.$name)) return self::error("Undefined block - ".$name);
        $vars = array_merge($this->presets,$vars);
        $search = array_map(function($i){return$this->tag[0].$i.$this->tag[1];},array_keys($vars));
        $replace = array_values($vars);
        return str_replace($search, $replace, file_get_contents($this->blocks.$name));
    }
    private function checkAll(): bool {
        if(!is_dir($this->corePath)) if(mkdir($this->corePath)==false) return self::error("Unable to create core directory");
        if(!file_exists($this->corePath."presets.json"))file_put_contents($this->corePath."presets.json","{}");
        if(!file_exists($this->corePath."eventListener.json"))file_put_contents($this->corePath."eventListener.json",json_encode($this->listener));
        if(!file_exists(".htaccess")) if($this->pool = self::generateHtaccess()==false) return self::error("Unable to create .htaccess");
        if(!is_dir($this->path)) if(mkdir($this->path)==false) return self::error("Unable to create public directory");
        if(!is_dir($this->blocks)) if(mkdir($this->blocks)==false) return self::error("Unable to create blocks directory");

        if(sizeof($this->presets)==0||$this->usePresets==true)$this->presets = array_merge($this->presets,(array) json_decode(file_get_contents($this->corePath."presets.json")));
        if($this->pool == "") $this->pool = substr(explode(PHP_EOL,file_get_contents(".htaccess"))[0],1);
        $this->listener = json_decode(file_get_contents($this->corePath."eventListener.json"));
        return true;
    }
    public function call(string $eventName): bool {
        if(is_bool(array_search($eventName,array_keys((array)$this->listener))))
            return self::error("Undefined event - ".$eventName);
        foreach($this->listener->$eventName as $script){
            if(!file_exists($script)) continue;
            if(pathinfo($script)["extension"]=="html") echo self::getBlock("../".$script);
            else include $script;
        }
        return true;
    }
    private function generateHtaccess(): string|false {
        $name = self::timeHash();
        if(file_put_contents(".htaccess",
                "#".$name.PHP_EOL.
                "RewriteEngine on".PHP_EOL.
                "RewriteBase /".PHP_EOL.
                "    RewriteCond %{REQUEST_FILENAME} !-f".PHP_EOL.
                "    RewriteCond %{REQUEST_FILENAME} !-d".PHP_EOL.
                "    RewriteRule ^(.*)$ index.php?".$name."=$1 [L,QSA]".
                "    RewriteCond %{REQUEST_FILENAME} -f".PHP_EOL.
                "    RewriteRule ^(.*)$ index.php?".$name."=$1 [L,QSA]".
                "    RewriteCond %{REQUEST_FILENAME} -d".PHP_EOL.
                "    RewriteRule ^(.*)$ index.php?".$name."=$1 [L,QSA]"
            )==false)
            return false;
        return $name;
    }
    private function timeHash(): string {return md5(time());}
    private function error(string $text): bool {
        $this->lastError = $text;
        return false;
    }
}