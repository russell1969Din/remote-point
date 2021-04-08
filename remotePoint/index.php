<?session_start();?>
<?
// ~>X<~ Vzdialený bod poskytujúci konečnému užívateľovi ako klientovi
// ~>X<~ prácu s databázou za určitých bezpečnostných podmienok
// ~>X<~ ====================================================================
// ~>X<~ Vývoj v prostredí PHP 8+
// ~>X<~ Na strane klienta predpokladaný Javascript / REACT / Typescript - Angular a podobne
// ~>X<~ Priamy prístup cez fetch, asynchrónny AJAX atď...
// ~>X<~ ====================================================================
// ~>X<~ Copyright: Rastislav Rehák, 2021
// ~>X<~ Verzia: 20210404
// ~>X<~ Všetky práva zo strany autora vyhradené !

$_SESSION["debugJSON"] = '';

include "otherPHPFunctions.php";

include "classes/topSecret.php";
include "classes/prefix.php";
include "classes/internalSQL.php";
include "classes/temporaryTables.php";
include "classes/admin.php";
include "classes/syntaxSQL.php";

// ~>X<~ Inicializácia konstruktora najvyššej rodičovskej mutódy 
// ~>X<~ vzdialeného bodu
$remotePoint = new remotePoint();

// ~>X<~ Spustenie najvyššej metódy vzdialeného bodu pre koncového užívateľa
$globalJSON = $remotePoint->command();

// ~>X<~ Ak objekt JSON pre debbugovanie nie je prázdny
if(len($_SESSION["debugJSON"])) {
    // ~>X<~ zobrazí ho pre účel napríklad pre console.log
    // ~>X<~ na stane klientskej aplikácie
    echo '['.$_SESSION["debugJSON"].']';
} else {
    // ~>X<~ Inak sa zobrazí JSON s dátami podľa príkazu SQL 
    // ~>X<~ ako je zo strany aplikácie klienta poskytnutý
    // ~>X<~ alebo na základe operáciu s databázou tomuto klientovi poskytnutý
    echo $globalJSON;
}
 
// ~>X<~ Základná metóda najvyššej úrovne projektu 
class remotePoint { // Public source code
    
    // ~>X<~ Pri ladení kódu protect = false, inak sa rovná true
    const protect   = true;
    
    // ~>X<~ Konštatné nastavenie dátumového formátu // eventuálne s formatom času
    const internFormatDate = 'd-m-Y';   // H:i:s

    // ~>X<~ Konštruktor triedy najvyššej úrovne
    function __construct() {
    
        $_POST["protect"] = "protect";

        // ~>X<~ Zistíme parametre pre pripojenie sa ku databáze
        //include('dbAccess.php');
        $this->topSecret = new TopSecret();

        // ~>X<~ Do POST premennej pre celý projekt publikujeme nastavenie konštanty protect
        $_POST["protect"] = $this->topSecret::protect;
   
        // ~>X<~ Zistíme na ktorej doméne alebo subdoméne sa zdrojový kód pre vzdialený bod nachádza 
        $this->URL = 'https://'.$_SERVER['HTTP_HOST']; //'https://vt.abnet.sk'; 
    
        include "systemSets/allowOrigin.php";
        
        // ~>X<~ Ak pri kontakte vzdialeného bodu nie je poskytnutý parameter 
        // ~>X<~ pre formátovanie dátumu
        if(!isSet($_POST['formatDate'])) {
            // ~>X<~ Inicializujeme tento parameter ako prázdny reťazec
            $_POST['formatDate'] = '';
            // ~>X<~ Ak nie sme v stave ladenia interne naformátujeme dátum
            if(!$this->topSecret::protect) {$_POST['formatDate'] = $this->topSecret::internFormatDate;} 
            // ~>X<~ ak parameter nastavenia dátuumového formátu zostane aj naďalej prázdny
            // ~>X<~ formátujeme ho celým formátom a to aj časovým
            if(len($_POST['formatDate'])==0) {$_POST['formatDate'] = 'd-m-Y H:i:s';} 
        }

        if(!isSet($_POST["childrenData"])) {$_POST["childrenData"] = '';}
        
        // ~>X<~ PHP8+ v každom prípade vyžaduje deklaráciu POST premenných aj keď ako prázdny reťazec
        if(!isSet($_POST["protected"])) $_POST["protected"] = '';
        
        
        // ~>X<~ Pripojíme sa na poskytnutú databázu podľa zistených parametrov
        $this->con = @MySQLi_connect($this->topSecret::dbLocal, $this->topSecret::dbLogin, $this->topSecret::dbPass,  $this->topSecret::dbName);

        // ~>X<~ Ak nastala v pripájaní na databázu chyba, a poskytnutá databáza nie je dostupná 
        if (mysqli_connect_errno()) {
            // ~>X<~ vzdialený bod vráti reťazec reprezentujúci objekt s chybovou správou v položke "error"
            $this->con = '[{"error":"'.mysqli_connect_error().'"}]'; 
        } else {
            // ~>X<~ Kontrola či vzdialený účasník má oprávnenie aby prevzal objekt 
            // ~>X<~ s požadovanými  informáciami prevzatými z databázy
        }

        include "systemSets/privateSets.php";
        //include "systemSets/isTester.php";

        //$postKeys = array("user", "pass", "serial", "prefix", "SQL", "protection");
        //foreach($postKeys as $key) if(!isSet($_POST[$key])) die();

        // ~>X<~ Inicializujeme status triedy indikujúci oprávnenie prístupu 
        // ~>X<~ ku tomuto vzdialenému bodu
        $this->userAccess = 0;
        
        $this->admin = new Admin($this->con, $this->topSecret::protect, $this->topSecret::aTableRules);   
        $this->syntaxSQL = new SyntaxSQL($this->con, $this->tableRules);
    }
    
    // ~>X<~ Metóda triedy je určená pre nadčasové zistenie
    // ~>X<~ či SQL príkaz poskytnutý z aplikácie na strane koncového užívateľa
    // ~>X<~ je vôbec pre tento vzdialený bod spracovateľný
    public function control($SQL) {
        
        // ~>X<~ Vyberieme prvý kľúčový výraz z SQL príkazu posktnutého klientom
        $command = trim(strToLower(subStr(trim($SQL), 0, strPos('~'.trim($SQL),' '))));

        // ~>X<~ Do reťazca určeného pre prípadnu chybovú správu
        // ~>X<~ túto správu načítame, ak sa vyskytne
        $errorMsg = match($command) {
            // ~>X<~ Ak je prvým kľúcovým slovom príkaz SELECT
            // ~>X<~ skontrolujeme syntax SQL príkazu pre účely ďalšieho spravovania
            // ~>X<~ zo strany vzdialeného bodu
            'select' => $this->syntaxSQL?->isSelect($SQL),
            // ~>X<~ Ak je kľúčové slovo neznáme, 
            // ~>X<~ metóda o tejto skutočnosti pripraví chybovú správu
            default => "Neznámy SQL príkaz v POST parametri = $command =",
        };
        // ~>X<~ ...a chybovú správu alebo prázdny reťazec poskytne rodičovskej metóde
        return $errorMsg;
    }

    
    // ~>X<~ Podľa SQL príkazu zavoláme príslušnú privátnu metódu triedy na spracovanie SQL
    //class remotePoint
    public function command() {
    
        // ~>X<~ Zavoláme metódu triedy aby skontrolovala existenciu dôležitých POST parametrov 
        $errorPOST = $this->admin?->controlPOST();
        
        // ~>X<~ Ak niektorý z POST parametrov nie je nastavený
        if(len($errorPOST))
            // ~>X<~ Chod vzdialeného bodu je predčasne ukončený s chybovou spravou v JSON
            return errorInObject($errorPOST, __LINE__, __FILE__);
    
        // ~>X<~ Vyskladáme pravidlá pre názvy tabuliek v databáze pre bezpečné použitie
        //$this->tableRules = array($_POST["prefix"], $this->tableRules[1], $this->tableRules[2]);
    
        // ~>X<~ Zisťujem či konečný užívateľ je oprávnený používať tento vzdialený bod
        $userAccess = $this->userAccess();
        
        // ~>X<~ Ak konečný užívateľ nemá oprávnený prístup, vráti chybové hlásenie v objekte
        if($userAccess<1) 
            return errorInObject('Nemáte prístup ku vzdialenému bodu '.$this->URL, __LINE__, __FILE__);
    
        //to do  
        $temporaryTables = new TemporaryTables($this->con, $this->topSecret::protect, $this->tableRules);
        $temporaryTables->create();
        //end to do
        
        
        $errorJSON = $this->control($_POST["SQL"]);
        if(strLen(Trim($errorJSON))>0) return errorInObject($errorJSON, __LINE__, __FILE__);
  
        // ~>X<~ Zavoláme privátnu metódu príslušnú k spracovaniu aktuálneho SQL príkazu
        eval('$JSON = $this->'.$this->isSQLCommand().'();');

        // ~>X<~ Vrátime získaný 
        return $JSON;
    }

    // ~>X<~ Privátna metóda triedy spracuje SQL príkaz SELECT[...]
    //class remotePoint
    private function select() {             
    
        // ~>X<~ Inicializujem reťazec pre vyskladanie JSON objektu
        $JSON = '[]';
        
        // ~>X<~ Inicializujem pole do ktorého načítam štruktúry tabuliek ako ich používa doručené SQL
        $aStructure = array();
        
        // ~>X<~ Listujem tabuľky z doručeného SQL príkazu
        foreach($this->syntaxSQL?->getTables() as $tableName) {
            // ~>X<~ a štruktúry z tabuliek z doručeného SQL príkazu pridávam do poľa $aStructure 
            $aStructure = $this->syntaxSQL?->structure($tableName, $aStructure, $_POST["SQL"]);
            //$aStructure = array_merge($aStructure, $aPartStructure);
        }
        
        // ~>X<~ Načítam obsah tabuliek parametrom poskytnutého SQL príkazu
        if($records = MySQLi_query($this->con, $_POST["SQL"])) {

            // ~>X<~ Začiatok JSON v rámci reťazca
            $JSON = '[';
            // ~>X<~ Listujem v riadkoch tabuliek podľa poskytnntého SQL
            while($line = MySQLi_Fetch_Array($records, MYSQLI_ASSOC))   {
                // ~>X<~ Začiatok vety v JSON v rámci reťazca
                $JSON .= '{';
                foreach($aStructure as $field) {
                    // ~>X<~ Ak je typ stĺpca vkladaného do JSON objektu reťazec alebo dátum
                    // ~>X<~ pridám úvodzovky
                    $quotation = match($field[1]) {
                        'str', 'date'  => '"',
                        default => '',
                    };
                    
                    // ~>X<~ Ak je aktuálný stĺpec dátumového typu
                    if($field[1]=='date') {
                        // ~>X<~ naformátuje ho dátumovým formátom poskytnutým POST parametrom
                        $line[$field[0]] = date($_POST['formatDate'],strtotime(str_replace('/','-',$line[$field[0]])));
                    }

                    if(isSet($line[$field[0]]))
                    // ~>X<~ Vkladám do JSON novú položku s údajom prevzatým z databázy 
                        $JSON .= '"'.$field[0].'":'.$quotation.$line[$field[0]].$quotation.',';

                    // ~>X<~ Ak POST parameter poskytuje reláciu k detským dátam
                    if(strLen(Trim($_POST["childrenData"])) > 0) {
                        // ~>X<~ prekonvertujeme ho na pole, ak by tých relácii bolo viac
                        // ~>X<~ separátor je &
                        $achildrenData = explode(':|:', $_POST["childrenData"]);
                        //_debug(count($achildrenData));
                        //_debug($_POST["childrenData"]);
                        
                        if(isSet($line[$field[0]]))
                        // ~>X<~ Listujeme po jednotlivých reláciách
                        foreach($achildrenData as $childrenData) {
                            // ~>X<~ Načítam detský objekt JSON proslúchajúci relácii
                            $childJSON = $this->syntaxSQL?->selectChildren(  $childrenData, 
                                                                            $field[0], 
                                                                            $line[$field[0]]);
                            
                            if(getType($childJSON)=='array') return errorInObject($childJSON[0], __LINE__, __FILE__);
                            
                            // ~>X<~ Ak sa načítal správne vloží ho do rodičovského JSON
                            // ~>X<~ a to aj keď je prázdny 
                            if(getType($childJSON)=='string') $JSON .= $childJSON.',';
                        }
                    }
                }
                // ~>X<~ Koniec vety v JSON v rámci reťazca s odrezaním poslednej čiarky
                $JSON = subStr($JSON, 0, strLen($JSON)-1);
                $JSON .= '},';
            }
            // ~>X<~ Koniec obsahu JSON objektu v rámci reťazca s odrezaním poslednej čiarky
            // ~>X<~ ak JSON nezostal prázdny
            if(subStr($JSON,strLen(Trim($JSON))-1,1)!='[') $JSON = subStr($JSON, 0, strLen($JSON)-1);
            $JSON .= ']';
        }

        // ~>X<~ JSON objekt s požadovanými údajmi z databázy táto metóda triedy vráti pre odovzdanie 
        // ~>X<~ vzdialenému užívateľovi
        return $JSON;
    }

    // ~>X<~ Privátna metóda zistí, či SQL príkaz je pre vzdialený bod spravovateľný
    private function isSQLCommand() {
    
        // ~>X<~ Ak nie je v POST parametri nastavený SQL príkaz, inicializuje práázdny reťazec
        if(!isSet($_POST["SQL"])) $_POST["SQL"] = '';
        
        $_POST["SQL"] = trim($_POST["SQL"]);
    
        // ~>X<~ Do premennej $param načíta príkaz z SQL
        $param = strToLower(trim(subStr(trim($_POST["SQL"]), 0, strPos($_POST["SQL"], ' '))));
        
        // ~>X<~ Ak zistený príkaz z SQL je vzdialený bod schopný spracovať, vráti názov na príslušnej to privátnej metódy triedy
        $command = match($param) {
            'select', 'insert'  => $param,
            default => 'emptyObject',
        };
        
        // ~>X<~ Vráti názov na príslušnej to privátnej metódy triedy, alebo triedy ktorá vráti prázdny objekt
        return($command);        
    }
    
    // ~>X<~ Ak SQL neobsahuje príkaz akceptovateľný pre vzdialený bod, 
    private function emptyObject() {
        // ~>X<~ táto metóda vráti JSON s chybovou správou 
        return errorInObject("Vzdialený bod nevie spracovať SQL príkaz v parametri", __LINE__, __FILE__);
    }

    // ~>X<~ Metóda triedy pre prácu s prístupovou tabuľkou 
    private function userAccess() {

        // ~>X<~ Zistíme, či prostupujúci končený užívateľ má na tento vzdialený bod povolený prístup
        $this->userAccess = $this->admin?->yesUserAccess($this->topSecret::systemTableUsers);
        
        // ~>X<~ Ak načítanie prebehne v poriadku
        // ~>X<~ Zistím či je vzdialený užívateľ oprávnený použivať tento vzdialený bod
        if($this->userAccess>0) {
            // ~>X<~ Ak áno pri ladení sa zobrazí OK
            if(!$this->topSecret::protect) echo ' OK <br />';
        } else {
            // ~>X<~ Ak nie
            if($this->userAccess==(-1)) {
                // ~>X<~ Pokúsi sa vytvoriť novú systémovú tabuľku s jedným zaznamom
                // ~>X<~ Reprezentúcim práva administrátora
                $isCreate = $this->admin?->createSystemTable($this->systemUsers);
                // ~>X<~ Ak sme v režime ladenia a podarí sa tabuľku vytvoriť,
                if(!$this->topSecret::protect && $isCreate) {
                    // ~>X<~ Túto metódu opätovne spustíme jej rekurziou
                    if($isCreate) $this->userAccess();
                    // ~>X<~ Inak vrátime odpoveď reprezentujúcu existenciu povolenia prístupu ku vzdialenému bodu
                    return $isCreate;
                }
            }
            // ~>X<~ Ak je prístup zamietnutý,  pri ladení sa zobrazí ACCESS DENIED
            if(!$this->topSecret::protect) echo ' ACCESS DENIED<br /> ';
        }
        // ~>X<~ Metoda triedy vráti status povolenia prístupu
        return $this->userAccess;
    }
}

