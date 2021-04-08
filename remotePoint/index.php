<?session_start();?>
<?
// ~>X<~ A remote point providing the end user as a client
// ~>X<~ work with the database under certain security conditions
// ~>X<~ ====================================================================
// ~>X<~ Development in PHP 8+ environment 
// ~>X<~ Assumed Javascript / REACT / Typescript - Angular and the like on the client side
// ~>X<~ Direct access via fetch, asynchronous AJAX etc ... 
// ~>X<~ ====================================================================

// ~>X<~ Copyright: Rastislav Rehák, 2021
// ~>X<~ Mail: rasto@abnet.sk
// ~>X<~ Facebook: https://www.facebook.com/rastislav.rehak1
// ~>X<~ LinkedIN: https://www.linkedin.com/in/rastislav-rehák-a14b191b6/

// ~>X<~ Version: 20210404 
// ~>X<~ All rights reserved by the author! 

$_SESSION["debugJSON"] = '';

// ~>X<~ Only public libraries and method classes are available at Github 
include "otherPHPFunctions.php";
// ~>X<~ If you are interested in non-public libraries and class methods, please contact the author 
include "classes/topSecret.php";
include "classes/prefix.php";
include "classes/internalSQL.php";
include "classes/temporaryTables.php";
include "classes/admin.php";
include "classes/syntaxSQL.php";

// ~>X<~ Initialization of the constructor of the highest parent method 
// ~>X<~ remote point
$remotePoint = new remotePoint();

// ~>X<~ Run the highest remote point method for the end user 
$globalJSON = $remotePoint->command();

// ~>X<~ Ak objekt JSON pre debbugovanie nie je prázdny
if(len($_SESSION["debugJSON"])) {
    // ~>X<~ displays it for a purpose such as console.log 
    // ~>X<~ on the client application side 
    echo '['.$_SESSION["debugJSON"].']';
} else {
    // ~>X<~ Otherwise, JSON is displayed with the data according to the SQL statement  
    // ~>X<~ as provided by the client application 
    // ~>X<~ or provided to that client based on a database operation 
    echo $globalJSON;
}
 
// ~>X<~ The basic method of the highest level of the project  
class remotePoint { // Public source code
    
    // ~>X<~ When debugging code protect = false, otherwise it equals true 
    const protect   = true;
    
    // ~>X<~ Constant setting of the date format // possibly with the time format 
    const internFormatDate = 'd-m-Y';   // H:i:s

    // ~>X<~ Top level class constructor
    function __construct() {
    
        $_POST["protect"] = "protect";

        // ~>X<~ We will find out the parameters also and not only for connecting to the database 
        $this->topSecret = new TopSecret();

        // ~>X<~ We publish the setting of the protect constant to the POST variable for the whole project 
        $_POST["protect"] = $this->topSecret::protect;
   
        // ~>X<~ We will find out on which domain or subdomain the source code for the remote point is located 
        $this->URL = 'https://'.$_SERVER['HTTP_HOST']; 
    
        include "systemSets/allowOrigin.php";
        
        // ~>X<~ If no parameter is provided when contacting a remote point 
        // ~>X<~ for date formatting 
        if(!isSet($_POST['formatDate'])) {
            // ~>X<~ We initialize this parameter as an empty string 
            $_POST['formatDate'] = '';
            // ~>X<~ If we are not in the debug state, we will format the date internally 
            if(!$this->topSecret::protect) {$_POST['formatDate'] = $this->topSecret::internFormatDate;} 
            // ~>X<~ if the date format setting parameter remains empty 
            // ~>X<~ we format it with the entire format, including the time format 
            if(len($_POST['formatDate'])==0) {$_POST['formatDate'] = 'd-m-Y H:i:s';} 
        }

        if(!isSet($_POST["childrenData"])) {$_POST["childrenData"] = '';}
        
        // ~>X<~ PHP8 + in any case requires the declaration of POST variables even if as an empty string 
        if(!isSet($_POST["protected"])) $_POST["protected"] = '';
        
        
        // ~>X<~ We will connect to the provided database according to the detected parameters
        $this->con = @MySQLi_connect($this->topSecret::dbLocal, $this->topSecret::dbLogin, $this->topSecret::dbPass,  $this->topSecret::dbName);

        // ~>X<~ If an error occurred while connecting to the database and the provided database is not available
        if (mysqli_connect_errno()) {
            // ~>X<~ the remote point returns a string representing the object with the error message in the "error" entry
            $this->con = '[{"error":"'.mysqli_connect_error().'"}]'; 
        } else {
            // ~>X<~ The control whether the remote participant has the right to take over the object
            // ~>X<~ with the required information retrieved from the database
        }

        include "systemSets/privateSets.php";
        //include "systemSets/isTester.php";

        //$postKeys = array("user", "pass", "serial", "prefix", "SQL", "protection");
        //foreach($postKeys as $key) if(!isSet($_POST[$key])) die();

        // ~>X<~ We initialize the status of the class indicating access authorization 
        // ~>X<~ to this remote point
        $this->userAccess = 0;
        
        $this->admin = new Admin($this->con, $this->topSecret::protect, $this->topSecret::aTableRules);   
        $this->syntaxSQL = new SyntaxSQL($this->con, $this->tableRules);
    }
    
    // ~>X<~ The class method is intended for timeless discovery
    // ~>X<~ or SQL statement provided from the application on the end user side
    // ~>X<~ is at all workable for this remote point
    public function control($SQL) {
        
        // ~>X<~ We will select the first key expression from the SQL statement provided by the client
        $command = trim(strToLower(subStr(trim($SQL), 0, strPos('~'.trim($SQL),' '))));

        // ~>X<~ In the string specified for a possible error message
        // ~>X<~ we will retrieve this message if it occurs
        $errorMsg = match($command) {
            // ~>X<~ If the first keyword is a SELECT statement
            // ~>X<~ we will check the syntax of the SQL statement for the purposes of further administration
            // ~>X<~ from the side of the remote point
            'select' => $this->syntaxSQL?->isSelect($SQL),
            // ~>X<~ If the keyword is unknown, 
            // ~>X<~ the method prepares an error message for this fact
            default => "Neznámy SQL príkaz v POST parametri = $command =",
        };
        // ~>X<~ ...and provide an error message or an empty string to the parent method
        return $errorMsg;
    }

    
    // ~>X<~ According to the SQL statement, we call the appropriate private method of the class for SQL processing
    //class remotePoint
    public function command() {
    
        // ~>X<~ We call the class method to check for the existence of important POST parameters
        $errorPOST = $this->admin?->controlPOST();
        
        // ~>X<~ If any of the POST parameters is not set
        if(len($errorPOST))
            // ~>X<~ Remote point operation is terminated prematurely with an error message in JSON 
            return errorInObject($errorPOST, __LINE__, __FILE__);
    
        // ~>X<~ We compile rules for table names in the database for safe use 
        //$this->tableRules = array($_POST["prefix"], $this->tableRules[1], $this->tableRules[2]);
    
        // ~>X<~ I'm finding out if the end user is authorized to use this remote point 
        $userAccess = $this->userAccess();
        
        // ~>X<~ If the end user does not have authorized access, it returns an error message in the object 
        if($userAccess<1) 
            return errorInObject('Nemáte prístup ku vzdialenému bodu '.$this->URL, __LINE__, __FILE__);
    
        //to do  
        $temporaryTables = new TemporaryTables($this->con, $this->topSecret::protect, $this->tableRules);
        $temporaryTables->create();
        //end to do
        
        
        $errorJSON = $this->control($_POST["SQL"]);
        if(strLen(Trim($errorJSON))>0) return errorInObject($errorJSON, __LINE__, __FILE__);
  
        // ~>X<~ We call the private method appropriate to process the current SQL statement
        eval('$JSON = $this->'.$this->isSQLCommand().'();');

        // ~>X<~ We return the obtained object 
        return $JSON;
    }

    // ~>X<~ The private method of the class processes the SQL SELECT statement [...] 
    //class remotePoint
    private function select() {             
    
        // ~>X<~ Initializing a string to compile a JSON object 
        $JSON = '[]';
        
        // ~>X<~ I initialize an array into which I load table structures as used by the inbox SQL 
        $aStructure = array();
        
        // ~>X<~ I'm browsing the tables from the delivered SQL statement
        foreach($this->syntaxSQL?->getTables() as $tableName) {
            // ~>X<~ and I add structures from tables from the delivered SQL statement to the $ aStructure field 
            $aStructure = $this->syntaxSQL?->structure($tableName, $aStructure, $_POST["SQL"]);
            //$aStructure = array_merge($aStructure, $aPartStructure);
        }
        
        // ~>X<~ Loading the contents of the tables with the parameters of the provided SQL statement 
        if($records = MySQLi_query($this->con, $_POST["SQL"])) {

            // ~>X<~ The beginning of JSON within the string 
            $JSON = '[';
            // ~>X<~ I browse the rows of tables according to the provided SQL 
            while($line = MySQLi_Fetch_Array($records, MYSQLI_ASSOC))   {
                // ~>X<~ Start of record in JSON within the string 
                $JSON .= '{';
                foreach($aStructure as $field) {
                    // ~>X<~ If the type of column inserted into the JSON object is a string or a date 
                    // ~>X<~ I will add quotes 
                    $quotation = match($field[1]) {
                        'str', 'date'  => '"',
                        default => '',
                    };
                    
                    // ~>X<~ If the current column is a date type 
                    if($field[1]=='date') {
                        // ~>X<~ formats it with the date format provided by the POST parameter 
                        $line[$field[0]] = date($_POST['formatDate'],strtotime(str_replace('/','-',$line[$field[0]])));
                    }

                    if(isSet($line[$field[0]]))
                    // ~>X<~ I'm inserting a new item into JSON with the data downloaded from the database  
                        $JSON .= '"'.$field[0].'":'.$quotation.$line[$field[0]].$quotation.',';

                    // ~>X<~ If the POST parameter provides a session to the child data 
                    if(strLen(Trim($_POST["childrenData"])) > 0) {
                        // ~>X<~ we will convert it to an array if there were more of those sessions
                        $achildrenData = explode(':|:', $_POST["childrenData"]);
                        //_debug(count($achildrenData));
                        //_debug($_POST["childrenData"]);
                        
                        if(isSet($line[$field[0]]))
                        // ~>X<~ We browse the individual sessions 
                        foreach($achildrenData as $childrenData) {
                            // ~>X<~ Loading JSON child object belonging to session 
                            $childJSON = $this->syntaxSQL?->selectChildren(  $childrenData, 
                                                                            $field[0], 
                                                                            $line[$field[0]]);
                            
                            if(getType($childJSON)=='array') return errorInObject($childJSON[0], __LINE__, __FILE__);
                            
                            // ~>X<~ If loaded correctly, it will insert it into the parent JSON 
                            // ~>X<~ even if it is empty 
                            if(getType($childJSON)=='string') $JSON .= $childJSON.',';
                        }
                    }
                }
                // ~>X<~ The end of the sentence in JSON within the string with the last comma truncated
                $JSON = subStr($JSON, 0, strLen($JSON)-1);
                $JSON .= '},';
            }
            // ~>X<~ The end of the JSON object content within the last comma-truncated string
            // ~>X<~ if JSON did not remain empty
            if(subStr($JSON,strLen(Trim($JSON))-1,1)!='[') $JSON = subStr($JSON, 0, strLen($JSON)-1);
            $JSON .= ']';
        }

        // ~>X<~ A JSON object with the required data from the database is returned by this class method for upload 
        // ~>X<~ remote user
        return $JSON;
    }

    // ~>X<~ The private method determines if the SQL statement is processable for the remote point 
    private function isSQLCommand() {
    
        // ~>X<~ Ak nie je v POST parametri nastavený SQL príkaz, inicializuje prázdny reťazec
        if(!isSet($_POST["SQL"])) $_POST["SQL"] = '';
        
        $_POST["SQL"] = trim($_POST["SQL"]);
    
        // ~>X<~ Loads a statement from SQL into the $ param variable
        $param = strToLower(trim(subStr(trim($_POST["SQL"]), 0, strPos($_POST["SQL"], ' '))));
        
        // ~>X<~ If the detected statement from SQL is a remote point able to process, it returns the name on the appropriate private method of the class
        $command = match($param) {
            'select', 'insert'  => $param,
            default => 'emptyObject',
        };
        
        // ~>X<~ Returns the name of the appropriate private method class, or class that returns an empty object 
        return($command);        
    }
    
    // ~>X<~ If the SQL does not contain a statement acceptable to the remote point, 
    private function emptyObject() {
        // ~>X<~ this method returns JSON with an error message 
        return errorInObject("Vzdialený bod nevie spracovať SQL príkaz v parametri", __LINE__, __FILE__);
    }

    // ~>X<~ Class method for working with access table 
    private function userAccess() {

        // ~>X<~ We will determine if the accessing end user is allowed access to this remote point
        $this->userAccess = $this->admin?->yesUserAccess($this->topSecret::systemTableUsers);
        
        // ~>X<~ If the download is successful 
        // ~>X<~ I will find out if the remote user is authorized to use this remote point 
        if($this->userAccess>0) {
            // ~>X<~ If so during tuning, OK is displayed 
            if(!$this->topSecret::protect) echo ' OK <br />';
        } else {
            // ~>X<~ unless
            if($this->userAccess==(-1)) {
                // ~>X<~ It will try to create a new system table with one record 
                // ~>X<~ Representing the rights of the administrator 
                $isCreate = $this->admin?->createSystemTable($this->systemUsers);
                // ~>X<~ If we are in debug mode and can create a table, 
                if(!$this->topSecret::protect && $isCreate) {
                    // ~>X<~ We run this method again by recursing it 
                    if($isCreate) $this->userAccess();
                    // ~>X<~ Otherwise, we return a response representing the existence of permission to access the remote point 
                    return $isCreate;
                }
            }
            // ~>X<~ If access is denied, ACCESS DENIED is displayed during debugging 
            if(!$this->topSecret::protect) echo ' ACCESS DENIED<br /> ';
        }
        // ~>X<~ The class method returns access access status 
        return $this->userAccess;
    }
}

