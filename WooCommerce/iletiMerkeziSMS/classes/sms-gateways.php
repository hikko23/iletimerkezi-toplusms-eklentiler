<?php

/**
 * SMS Gateway handler class
 *
 * @author satosms
 */
class SatSMS_SMS_Gateways {

    private static $_instance;

    public static function init() {
        if ( !self::$_instance ) {
            self::$_instance = new SatSMS_SMS_Gateways();
        }

        return self::$_instance;
    }

    function talkwithtext( $sms_data ) {
        $username = satosms_get_option( 'talkwithtext_username', 'satosms_gateway', '' ); 
        $password = satosms_get_option( 'talkwithtext_password', 'satosms_gateway', '' ); 
        $originator = satosms_get_option( 'talkwithtext_originator', 'satosms_gateway', '' ); 
        $admin_phone = str_replace( '+', '', $sms_data['number'] );

        if( empty( $username ) || empty( $password ) ) {
            return;
        }

        require_once dirname( __FILE__ ) . '/../lib/sms.php';
        $sol4mob_sms=   new sms();     
        $sol4mob_sms->username= $username;
        $sol4mob_sms->password= $password;
        $sol4mob_sms->originator= $originator;
        $sol4mob_sms->msgtext= $sms_data['sms_body']; 
        $sol4mob_sms->phone= $admin_phone;
        $response = $sol4mob_sms->send();  
        
        if( $response == 'OK' ) {
            return true;
        } else {
            return false;
        }
    }

    function iletimerkezi( $sms_data ) {

        $username = satosms_get_option( 'iletimerkezi_username', 'satosms_gateway', '' ); 
        $password = satosms_get_option( 'iletimerkezi_password', 'satosms_gateway', '' ); 
        $originator = satosms_get_option( 'iletimerkezi_originator', 'satosms_gateway', '' ); 
        $admin_phone = $sms_data['number'];
        $admin_phone = preg_replace('/\D/','',$admin_phone);
        $admin_phone = substr($admin_phone, -10);
        //die($admin_phone);

        // die(var_export($sms_data,1)." -- ".$username." --- ".$password." --- ".$originator);

        if( empty( $username ) || empty( $password ) ) {
            return;
        }

        require_once dirname( __FILE__ ) . '/../lib/sms.php';

        $sol4mob_sms=   new sms();     
        // $sol4mob_sms->username= $username;
        // $sol4mob_sms->password= $password;
        // $sol4mob_sms->originator= ;
        // $sol4mob_sms->msgtext= $sms_data['sms_body']; 
        // $sol4mob_sms->phone= $admin_phone;

        $msg = $sms_data['sms_body'];

        $xml = <<<EOS
                    <request>
                            <authentication>
                                    <username>{$username}</username>
                                    <password>{$password}</password>
                            </authentication>
                            <order>
                                <sender>{$originator}</sender>
                                <sendDateTime></sendDateTime>
                                <message>
                                    <text><![CDATA[{$msg}]]></text>
                                    <receipents>
                                        <number>{$admin_phone}</number>
                                    </receipents>
                                </message>
                            </order>
                    </request>
EOS;

        $response = $sol4mob_sms->sendRequest('http://api.iletimerkezi.com/v1/send-sms',$xml,array('Content-Type: text/xml'));
        
        preg_match_all('|\<code\>.*\<\/code\>|U', $response, $matches,PREG_PATTERN_ORDER);
        
        if(isset($matches[0])&&isset($matches[0][0])) {
             if($matches[0][0]==200) {
                return true;
             } 
        }

        return false;
       
    }

    /**
     * Sends SMS via Clickatell api
     *
     * @param type $sms_data
     * @return boolean
     */
    function clickatell( $sms_data ) {

        $response = false;
        $username = satosms_get_option( 'clickatell_name', 'satosms_gateway' );
        $password = satosms_get_option( 'clickatell_password', 'satosms_gateway' );
        $api_key = satosms_get_option( 'clickatell_api', 'satosms_gateway' );
        $phone = str_replace( '+', '', $sms_data['number'] );
        $text = urlencode( $sms_data['sms_body'] );
        //bail out if nothing provided
        if ( empty( $username ) || empty( $password ) || empty( $api_key ) ) {
            return $response;
        }

        // auth call
        $baseurl = "http://api.clickatell.com";
        $url = sprintf( '%s/http/auth?user=%s&password=%s&api_id=%s', $baseurl, $username, $password, $api_key );

        // do auth call
        $ret = file( $url );

        // explode our response. return string is on first line of the data returned
        $sess = explode( ":", $ret[0] );
        if ( $sess[0] == "OK" ) {

            $sess_id = trim( $sess[1] ); // remove any whitespace
            $url = sprintf( '%s/http/sendmsg?session_id=%s&to=%s&text=%s', $baseurl, $sess_id, $phone, $text );

            // do sendmsg call
            $ret = file( $url );
            $send = explode( ":", $ret[0] );

            if ( $send[0] == "ID" ) {
                $response = true;
            }
        }

        return $response;
    }

}
