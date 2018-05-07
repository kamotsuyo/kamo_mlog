<?PHP
/**
* MyLogクラス
* 使い方


====開発後の日時監視に使用します。===
指定ディレクトリにログファイルを記述する
$mlog = new Mlog();
$mlog -> write($string , Mylog::OK) 
$mlog -> write($string , Mylog::ERROR) 

=====開発時に使用します======
既定のphp_error.logに書き込む
$mlog = new Mlog();
$log = "hogehoge";
$mlog->php_error_log($log);

var_dump関数の実行内容をvar_dump.logファイルに書き出す
$mlog = new Mlog();
$var = debug_trace();
$mlog->var_dump($var);

debug/debug.logに書き込む
$mlog = new Mlog();
$log = 'hogemoga';
$mlog->debug($log);

=====ログディレクトリを指定する場合====
$dlog = new Mlog(__FILE__);
のように引数にディレクトリを指定してインスタンス化すること。
$dlog->debug('hoge);


**/


//グローバルでインスタンス化しておく。
global $kamlog;
$kamlog = new Mlog();



//既定のphp_error.logのディレクトリ
define('PHP_ERROR_LOG_DIR','/Applications/MAMP/logs');

/**
* このクラスはログ出力とデバッグ管理を行う。
* $kamlog でglobalインスタンス化している。
* 
* @access public
* @param string $parent_dir
* 
* @return 
* 
*/
class Mlog{
    private $DIR;
    
    private $OK_DIR = 'ok_log';//運用時ログ用
    private $ERROR_DIR = 'error_log';//運用時ログ用
    private $DUMP_DIR = 'dump';//開発時のvar_dump用
    private $DEBUG_DIR= 'debug';//開発時のデバッグ用
    const OK = true;
    const ERROR = false;
    
    //コンストラクタ
    function __construct($parent_dir = null){
        //引数$parent_dirがある場合、そディレクトリにログフォルダを作成する
        if($parent_dir != null){
            $this->DIR = $parent_dir . '/logs';
        }else{
            $this->DIR = __DIR__ . '/logs';
        }
        
        $this -> mkLogdir();
    }
    private function mkLogdir(){
        
        
        //ログ格納用のディレクトリを確認・作成する
        //親フォルダの存在確認
        if(file_exists ($this -> DIR)){
            //親フォルダが存在すれば実行
            
            //error用ディレクトリの作成
            $errordir = $this -> DIR . '/' . $this -> ERROR_DIR;
            if(!file_exists($errordir)){
                mkdir($errordir , 0750 ,true);
            }
            //ok用ディレクトリ作成
            $okdir = $this -> DIR . '/' . $this -> OK_DIR;
            if(!file_exists($okdir)){
                mkdir($okdir , 0750 ,true);
            }
            //var_dump用のディレクトリ作成
            $dumpdir = $this -> DIR . '/' . $this -> DUMP_DIR;
            if(!file_exists($dumpdir)){
                mkdir($dumpdir , 0750 ,true);
            }
            //開発時のDEBUG_DIRのディレクトリ作成
            $dir = $this->DIR . '/' . $this->DEBUG_DIR;
            if(!file_exists($dir)){
                mkdir($dir , 0750 , true);
            }
        }else{
            //LOGFILESフォルダが存在しない場合--作成する
            //第二引数はパーミッション
            //第三引数は階層構造のディレクトリ作成の許可
            mkdir($this -> DIR , 0750 ,true);
            
            //もう一度この関数を呼び出して下位のディレクトリを作成する
            $this -> mkLogdir();
        }
    }
    private function getCurrentDate(){
        date_default_timezone_set('Asia/Tokyo');        //date_default_timezone_setでphp.iniの設定をそのスクリプト実行中だけ既定値を上書き
        $date = new DateTime();
        return $date->format('Y-m-d');
    }
    private function getCurrentTime(){
        date_default_timezone_set('Asia/Tokyo');        //date_default_timezone_setでphp.iniの設定をそのスクリプト実行中だけ既定値を上書き
        $date = new DateTime();
        return $date->format('H:i:s');
    }

    public function write($string , $flag=null){
        //呼び出し元の関数名を取得
        $trace = debug_backtrace()[0];
        $trace = $trace['file'] .',line '. $trace['line'];        
        
        $log = $this -> getCurrentTime() .  ',' .$string . ',' . $trace . PHP_EOL;
        
        //フラグの入力がない場合、エラーアラート

        if($flag == null){
            $this->php_error_log("Mlog->write()の第二引数がありません ".$trace);
        }else{
            //書き込みディレクトリの指定
            if($flag===true){
            //成功の場合
                $logDir = $this -> DIR . '/' . $this -> OK_DIR;

            }else if($flag===false){
            //エラーの場合
                $logDir = $this -> DIR . '/' . $this -> ERROR_DIR;
            }

            //ファイルの書き込み
            //ファイル名を「日.log」とする
            $filename = $this -> getCurrentDate().'.log';
            //FILE_APPEND フラグはファイルの最後に追記すること
            //LOCK_EX フラグは他の人が同時にファイルに書き込めないこと
            file_put_contents($logDir.'/'.$filename,$log,FILE_APPEND | LOCK_EX);          
            
        }

    }
    
    //var_dump();の内容をファイルに記述する
    /**
    引数：var_dump()を実行する対象
    */
    public function var_dump($target){
        //準備：ディレクトリと保存ファイルの指定
        $log_dir = $this->DIR . '/' . $this->DUMP_DIR;
        $filename = 'var_dump.log';
        
        //ob_start関数は通常ブラウザに出力される情報をバッファと呼ばれる領域に保存しあとから取り出すことができるようにする関数です。
        //出力を行う前にob_start()を実行し、var_dump($arr)で配列の中身をバッファに出力します。
        //この時点ではブラウザに出力されません。
        //その後にバッファからデータを取り出すことができる関数のob_get_contents()を実行し、結果を$resultに保存します。$resultにはvar_dump($arr)の結果が保存されます。
        //最後にob_end_clean()でバッファの中身を削除します。
        //あとは、通常通り$resultの中身をdump.txtへ書き込みます。
        ob_start();
        var_dump($target);
        $result =ob_get_contents();
        ob_end_clean();
        
        //dumpの結果に「日時」を追記する
        $header = '====================================' . "\n";
        $header = $header . $this->getCurrentDate().  "\n";
        $header = $header . $this->getCurrentTime() . "\n";
        
        //dump結果に呼び出し元の関数名などを追記
        $debug = debug_backtrace()[0];
        $func_file = $debug['file'];
        $func_line = $debug['line'];
        
        
        //header作成
        $header = $header . "ファイル名 : " . $func_file . "\n";
        $header = $header . "行 : " . $func_line . "\n"; 
        $header = $header . '====================================' . "\n";
        
        
        
        //dump結果にfooter追記
        $footer = "\n".'------------- end ---------------' . "\n\n";
        
        $result = $header . $result . $footer;
        
        //ファイルへの書き出し：追記モード
        file_put_contents($log_dir. '/' . $filename,$result  ,FILE_APPEND |  LOCK_EX);
        
        
    }
    /**
    既定のphp_error.logに書き込む
    */
    public function php_error_log($string){
        $php_error_log = PHP_ERROR_LOG_DIR.'/php_error.log';
        if(file_exists($php_error_log)){
            //php_error.logに書き込む
            $datetime = $this->getCurrentDate() .' '. $this->getCurrentTime().': ';
            file_put_contents($php_error_log,$datetime.$string."\n",FILE_APPEND | LOCK_EX); 
        }else{
            trigger_error('php_error.logが指定場所PHP_ERROR_DIR='.PHP_ERROR_DIR.'にありません。確認してください。',E_USER_ERRRO);
        }
    }
    /**
    debugファイルに書き込む　追記モード
    */
    public function debug($string=null){

        //準備：ディレクトリと保存ファイルの指定
        $log_dir = $this->DIR . '/' . $this->DEBUG_DIR;
        $filename = 'debug.log';
        
        //dumpの結果に「日時」を追記する
        $header = '====================================' . "\n";
        $header = $header . $this->getCurrentDate().  "\n";
        $header = $header . $this->getCurrentTime() . "\n";
        
        //dump結果に呼び出し元の関数名などを追記
        $debug = debug_backtrace()[0];
        $func_file = $debug['file'];
        $func_line = $debug['line'];
        
        
        //header作成
        $header = $header . "ファイル名 : " . $func_file . "\n";
        $header = $header . "行 : " . $func_line . "\n";
        $header = $header . '====================================' . "\n";
        
        
        //nullかどうかの判定

        if($string==null){
            $string='null';
        }

        //dump結果にfooter追記
        $footer = "\n".'------------- end ---------------' . "\n\n";
        
        $result = $header . $string . $footer;
        
        //ファイルへの書き出し：追記モード
        file_put_contents($log_dir. '/' . $filename, $result  ,FILE_APPEND| LOCK_EX);
    }
}
