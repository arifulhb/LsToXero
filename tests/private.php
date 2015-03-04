<?php

//         XEROOAuth
    include dirname(__FILE__) . '/lib/XeroOAuth.php';

    $checkErrors    =0;
//    $XeroOAuth;

    define ( "XRO_APP_TYPE", "Private" );
    define ( "OAUTH_CALLBACK", "oob" );
    define ( 'BASE_PATH', dirname ( __FILE__ ) );


//    define ( 'BASE_PATH', '' );

    //const BASE_PATH ='http://localhost/posios_json/';
    

    function prepare($key_array)
    {
        //print_r($key_array);
        //echo 'prepare...<br>';
        //$this->_ci =&get_instance();
//        @TODO Add UserAgent Name

        $useragent = "XeroOAuth-PHP Private Launchstars App";

        $signatures = array (
                        'consumer_key' => $key_array['consumer_key'],//
                        'shared_secret' => $key_array['shared_secret'],//
                        // API versions
                        'core_version' => '2.0',
                        'payroll_version' => '1.0'
        );


//        @TODO add cert file location here
        //GET CERT KEY FOR SIGNATURE
        if (XRO_APP_TYPE == "Private" || XRO_APP_TYPE == "Partner") {

//            echo "LOADING CERT FILES".PHP_EOL;
//            echo "Base URL: ".BASE_PATH.PHP_EOL;

/*            $signatures ['rsa_private_key']     = realpath(__DIR__ . '/../../certs') . '/privatekey.pem';
            $signatures ['rsa_public_key']      = realpath(__DIR__ . '/../../certs') . '/publickey.cer';*/

            $signatures ['rsa_private_key'] = BASE_PATH . '/../certs/privatekey.pem';
            $signatures ['rsa_public_key'] = BASE_PATH . '/../certs/publickey.cer';
        }


        $XeroOAuth = new XeroOAuth ( array_merge ( array (
                'application_type'  => XRO_APP_TYPE,
                'oauth_callback'    => OAUTH_CALLBACK,
                'user_agent'        => $useragent
        ), $signatures ) );


        $initialCheck = $XeroOAuth->diagnostics();
        /*echo '<br>INITIAL CHECK<BR>';
        print_r($initialCheck);*/
        $checkErrors= count ( $initialCheck );
        //echo('cHECK ERRORS: '.$checkErrors.'<br>');

        return array($checkErrors,$initialCheck,$XeroOAuth);

    }//end construct



    function getAccounts($key_array){

        prepare($key_array);
        
        $_status=XeroOAuthErrorCheck();

        if($_status['status']==true){


            $oauthSession = retrieveSession ();

            if (isset ( $oauthSession ['oauth_token'] )) {


                $XeroOAuth->config ['access_token'] = $oauthSession ['oauth_token'];
                $this->XeroOAuth->config ['access_token_secret'] = $oauthSession ['oauth_token_secret'];

                  if (isset($_REQUEST)){
                        if (!isset($_REQUEST['where'])) $_REQUEST['where'] = "";
                   }

                   if ( isset($oauthSession['oauth_token']) && isset($_REQUEST) ) {

                        $this->XeroOAuth->config['access_token']  = $oauthSession['oauth_token'];
                        $this->XeroOAuth->config['access_token_secret'] = $oauthSession['oauth_token_secret'];
                        $this->XeroOAuth->config['session_handle'] = $oauthSession['oauth_session_handle'];

                        
                        /**
                         * this part is to change in every function
                         */
                        $_result=$this->fetch_xero_accoutns($this->XeroOAuth);

                        //RETURN
                        $_return=array('success'=>TRUE,'result'=>$_result);
                        return $_return;


                    }else{

                        //RETURN                            
                        $_return=array('success'=>FALSE,'result'=>'ERROR: oAuth token not set.');
                        return $_return;
                    }


            }else{
                
                //RETURN                
                $_return=array('success'=>FALSE,'result'=>'Auth session not set');
                return $_return;
            }

        }else{
            //errors
            $_return=array('success'=>FALSE,'result'=>$_status['errors']);
            return $_return;
        }    
    }//end function
    
    /**
     * 
     * CALLED IN customer controller
     * 
     * @param type $xml as invoice xml     
     * @return boolean|string
     */
    function save_to_xero($xml,$action,$key_array){

//        echo 'SAVE TO XERO Action: '.$action.'<br>';
//        print_r($key_array);

        //function constractor
         $_prepare= prepare($key_array);
//        echo 'PREPARE IN save_to_xero fun();<br><pre>';
//            print_r($_prepare);
//        echo '</pre>';
         $error_count   = $_prepare[0];
         $initialCheck  = $_prepare[1];
         $XeroOAuth     = $_prepare[2];

        $_status=XeroOAuthErrorCheck($error_count,$initialCheck,$XeroOAuth);
        /*echo "<pre>STATUS: $_status<br>";
        print_r($_status);
        echo "</pre>";*/
        if($_status['status']==true OR $_status==1){
//            echo "<br>STATUS SUCCESS<BR>";

            $oauthSession = retrieveSession ();

            if (isset ( $oauthSession ['oauth_token'] )) {

//                echo('oAuth token');
                $XeroOAuth->config ['access_token'] = $oauthSession ['oauth_token'];
                $XeroOAuth->config ['access_token_secret'] = $oauthSession ['oauth_token_secret'];


                   if ( isset($oauthSession['oauth_token']) && isset($_REQUEST) ) {

                        $XeroOAuth->config['access_token']  = $oauthSession['oauth_token'];
                        $XeroOAuth->config['access_token_secret'] = $oauthSession['oauth_token_secret'];
                        $XeroOAuth->config['session_handle'] = $oauthSession['oauth_session_handle'];

                        
                        /**
                         * this part is to change in every function
                         */
//                            echo "check action invoice or someting<br>";

                        //RETURN
                        if($action=='invoice'){
//                            echo 'YES! Action is INVOICE:<BR>';
                            //POST INVOICE TO XERO
                            $_result=post_xero_invoice($XeroOAuth,$xml);

                        }elseif($action=='payment'){

                            //POST PAYMENT TO XERO
                            $_result=post_xero_payment($XeroOAuth,$xml);
                        }
                        

                        //RETURN SUCCESS RESULT
                        $_return=array('success'=>TRUE,'result'=>$_result);
                        
                        return $_return;


                    }else{

                        //RETURN                            
                        $_return=array('success'=>FALSE,'result'=>'ERROR: oAuth token not set.');
                        return $_return;
                    }


            }else{
                echo "AUTH IS NOT SET <BR>";
                //RETURN                
                $_return=array('success'=>FALSE,'result'=>'Auth session not set');
                return $_return;
            }

        }else{
            //errors
            $_return=array('success'=>FALSE,'result'=>$_status['errors']);
            return $_return;
        }    
        
    }//end function

    /**
     * 
     * @return boolean
     */
    function XeroOAuthErrorCheck($error_count, $initialCheck, $XeroOAuth){

//        echo "Error Count: ".$error_count.'... ';

        if($error_count>0){
//            ERROR EXISTS
            echo "IF <BR>";
            $_errors=array();
            foreach ( $initialCheck as $check ) {
                array_push($_errors, $check.PHP_EOL);
            }//end foreach

            $_status=array('status'=>FALSE,'errors'=>$_errors);
            print_r($_status);

            return $_status;

        }else{
//            echo('else <br>');
            //print($XeroOAuth);

            $session = persistSession ( array (
			'oauth_token' => $XeroOAuth->config ['consumer_key'],
			'oauth_token_secret' => $XeroOAuth->config ['shared_secret'],
			'oauth_session_handle' => '' ) );
            
            $_status=array('status'=>TRUE);
            return $_status;
        }

    }//end function


     function fetch_xero_accoutns($XeroOAuth){


        if (isset($_REQUEST['accounts'])) {

                $response = $XeroOAuth->request('GET', $XeroOAuth->url('Accounts', 'core'), array('Where' => $_REQUEST['where']));

                if ($XeroOAuth->response['code'] == 200) {
                    $accounts = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                   // echo "There are " . count($accounts->Accounts[0]). " accounts in this Xero organisation, the first one is: </br>";

                    $_result=array('status'=>'ok','result'=>$accounts->Accounts[0]);
                    return $_result;

                } else {

                    //ERROR
                    $_result=array('status'=>'error','result'=>$XeroOAuth);
                    return $_result;
                }
            }//end if
            else{
                $_result=array('status'=>'error','result'=>'accounts $_REQEST is not set.');
                return $_result;

            }//end else

    }//end function
    
    /**
     * 
     * @param type $XeroOAuth
     */
    function post_xero_invoice($XeroOAuth,$xml){
        
       
                $response = $XeroOAuth->request('PUT', $XeroOAuth->url('Invoices', 'core'), array(), $xml);
                /*echo '<PRE> POST_XERO_INVOICE<BR>';
                print_r($XeroOAuth);
                echo '</pre>';*/
                if ($XeroOAuth->response['code'] == 200) {
                    
                    $invoice = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                           
                    
                    $_result=array('status'=>'ok','result'=>$invoice->Invoices[0]);
                    return $_result;

                } else {

                    //ERROR
                    $_result=array('status'=>'error','result'=>$XeroOAuth);
                    return $_result;
                }            
        
    }//end function
    
    
    function post_xero_payment($XeroOAuth,$xml){
        

            $response = $XeroOAuth->request('PUT', $XeroOAuth->url('Payments', 'core'), array(), $xml);

            if ($XeroOAuth->response['code'] == 200) {

                $payments = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);

                $_result=array('status'=>'ok','result'=>$payments->Payments[0]);
                return $_result;

            } else {

                //ERROR
                $_result=array('status'=>'error','result'=>$XeroOAuth);
                return $_result;
            }            
        
    }//end function

       
function persistSession($response)
{
    if (isset($response)) {
        $_SESSION['access_token']       = $response['oauth_token'];
        $_SESSION['oauth_token_secret'] = $response['oauth_token_secret'];
        if(isset($response['oauth_session_handle']))  $_SESSION['session_handle']     = $response['oauth_session_handle'];
    } else {
        return false;
    }

}

/**
 * Retrieve the OAuth access token and session handle
 * In my example I am just using the session, but in real world, this is should be a storage engine
 *
 */
function retrieveSession()
{
    if (isset($_SESSION['access_token'])) {
        $response['oauth_token']            =    $_SESSION['access_token'];
        $response['oauth_token_secret']     =    $_SESSION['oauth_token_secret'];
        $response['oauth_session_handle']   =    $_SESSION['session_handle'];
        return $response;
    } else {
        return false;
    }

}

function outputError($XeroOAuth)
{
    echo 'Error: ' . $XeroOAuth->response['response'] . PHP_EOL;
    pr($XeroOAuth);
}

/**
 * Debug function for printing the content of an object
 *
 * @param mixes $obj
 */
function pr($obj)
{

    if (!is_cli())
        echo '<pre style="word-wrap: break-word">';
    if (is_object($obj))
        print_r($obj);
    elseif (is_array($obj))
        print_r($obj);
    else
        echo $obj;
    if (!is_cli())
        echo '</pre>';
}

function is_cli()
{
    return (PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']));
}
